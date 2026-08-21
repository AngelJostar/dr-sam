<?php

namespace App\Services\Integrations\Cbta;

use App\Models\MixtureIntegration;
use App\Models\ProviderRequest;
use App\Models\ProviderRequestStatusEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class MixtureIntegrationSyncService
{
    public function __construct(private CbtaCatalogClient $client)
    {
    }

    public function sync(MixtureIntegration $integration): bool
    {
        $integration->loadMissing('providerRequest.patient', 'providerRequest.medicalUnit');

        if (! $integration->cbta_request_id && ! $this->hasRequiredAuthorizations($integration)) {
            $integration->update([
                'sync_status' => 'awaiting_authorizations',
                'last_error' => null,
                'sync_attempts' => 0,
                'next_retry_at' => null,
            ]);

            return true;
        }

        try {
            if (! $integration->cbta_request_id) {
                $this->prevalidateBeforeCreation($integration);
                $integration->refresh();
            }

            $remote = $integration->cbta_request_id
                ? $this->client->mixtureRequest($integration->cbta_request_id)
                : $this->client->createMixtureRequest($this->payload($integration));
            $remote = $this->syncAttachment($integration, $remote);
            $remoteHasError = ($remote['has_error'] ?? false)
                || in_array($remote['status'] ?? null, ['materialization_failed', 'rejected', 'cancelled'], true);
            $remoteMessage = filled($remote['status_message'] ?? null)
                ? mb_substr((string) $remote['status_message'], 0, 4000)
                : (filled($remote['last_error'] ?? null) ? mb_substr((string) $remote['last_error'], 0, 4000) : null);

            DB::transaction(function () use ($integration, $remote, $remoteHasError, $remoteMessage): void {
                $integration->update([
                    'cbta_request_id' => $remote['request_id'],
                    'remote_status' => $remote['status'],
                    'sync_status' => 'synced',
                    'catalog_version' => $remote['catalog_version'] ?? $integration->catalog_version,
                    'last_error' => $remoteHasError ? ($remoteMessage ?: 'CBTA reportó un error durante el procesamiento de la solicitud.') : null,
                    'last_synced_at' => now(),
                    'sync_attempts' => 0,
                    'next_retry_at' => null,
                    'metadata' => array_merge($integration->metadata ?? [], [
                        'remote_status_details' => $remote['status_details'] ?? null,
                        'remote_status_checked_at' => $remote['status_checked_at'] ?? null,
                        'remote_integration_stage' => $remote['integration_stage'] ?? null,
                        'remote_status_message' => $remoteMessage,
                        'remote_has_error' => $remoteHasError,
                        'remote_remission' => $remote['remission'] ?? null,
                        'remote_documents' => $remote['documents'] ?? [],
                    ]),
                ]);

                $this->applyRemoteDocuments($integration, $remote);
                $this->applyProviderStatus($integration, $remote['status'], $remoteMessage);
            });

            return true;
        } catch (Throwable $exception) {
            $attempts = (int) $integration->sync_attempts + 1;
            $maxDelay = max(1, (int) config('cbta.max_retry_delay_minutes', 60));
            $delay = min($maxDelay, (int) 2 ** max(0, $attempts - 1));
            $integration->update([
                'sync_status' => 'failed',
                'last_error' => mb_substr($exception->getMessage(), 0, 4000),
                'last_synced_at' => now(),
                'sync_attempts' => $attempts,
                'next_retry_at' => $attempts >= (int) config('cbta.max_sync_attempts', 10)
                    ? null
                    : now()->addMinutes($delay),
            ]);

            return false;
        }
    }

    public function hasRequiredAuthorizations(MixtureIntegration $integration): bool
    {
        $request = $integration->providerRequest;
        if (! $request) {
            return false;
        }

        $payload = $request->payload ?? [];
        $required = $payload['authorization_requirements']
            ?? ($request->request_type === 'chemo' ? ['oncology', 'pharmacy'] : ['operational', 'pharmacy']);
        $authorizations = $payload['authorizations'] ?? [];

        return $required !== [] && collect($required)
            ->every(fn (string $area): bool => ($authorizations[$area] ?? 'pending') === 'approved');
    }

    private function prevalidateBeforeCreation(MixtureIntegration $integration): void
    {
        $request = $integration->providerRequest;
        $payload = $request?->payload ?? [];
        $prevalidationPayload = [
            'medical_unit_code' => $request?->medicalUnit?->cbta_external_code,
            'catalog_type' => $integration->metadata['catalog_type'] ?? null,
            'catalog_version' => $integration->catalog_version,
            'items' => $payload['integration_items'] ?? [],
        ];

        $result = $this->client->prevalidateMixture($prevalidationPayload);
        if (! $result['valid']) {
            $message = collect($result['errors'] ?? [])->pluck('message')->filter()->unique()->join(' ');

            throw new \RuntimeException($message ?: 'Mezclas rechazo la prevalidacion final antes de crear la solicitud.');
        }

        $integration->update([
            'sync_status' => 'prevalidated',
            'catalog_version' => $result['catalog_version'],
            'payload_hash' => hash('sha256', json_encode($prevalidationPayload, JSON_THROW_ON_ERROR)),
            'metadata' => array_merge($integration->metadata ?? [], [
                'final_prevalidation_at' => now()->toIso8601String(),
                'final_prevalidation_valid' => true,
            ]),
        ]);
    }

    private function syncAttachment(MixtureIntegration $integration, array $remote): array
    {
        $attachment = data_get($integration->providerRequest?->payload, 'attachment');
        if (! $attachment || empty($attachment['path'])) {
            return $remote;
        }

        $disk = $attachment['disk'] ?? 'local';
        if (! Storage::disk($disk)->exists($attachment['path'])) {
            throw new \RuntimeException('El documento local de autorizacion ya no esta disponible.');
        }

        $knownHashes = collect($remote['documents'] ?? [])->pluck('sha256')->filter();
        $contents = Storage::disk($disk)->get($attachment['path']);
        $sha256 = hash('sha256', $contents);
        if ($knownHashes->contains($sha256)) {
            return $remote;
        }

        $document = $this->client->uploadMixtureDocument($remote['request_id'], [
            'type' => $attachment['type'] ?? 'authorization',
            'name' => $attachment['original_name'] ?? basename($attachment['path']),
            'contents' => $contents,
        ]);
        $remote['documents'] = collect($remote['documents'] ?? [])->push($document)->values()->all();

        return $remote;
    }

    private function applyProviderStatus(MixtureIntegration $integration, string $remoteStatus, ?string $remoteMessage = null): void
    {
        $status = match ($remoteStatus) {
            'pending', 'received', 'materialized' => 'requested',
            'authorized' => 'accepted',
            'preparing' => 'preparing',
            'ready' => 'ready',
            'delivered' => 'delivered',
            'cancelled' => 'cancelled',
            'rejected' => 'rejected',
            default => null,
        };
        $requestId = $integration->provider_request_id;

        if (! $status || ! $requestId) {
            return;
        }

        // El webhook y la conciliacion programada pueden llegar al mismo tiempo.
        // El bloqueo evita guardar dos eventos para una sola transicion remota.
        $request = ProviderRequest::query()->lockForUpdate()->find($requestId);
        if (! $request || $request->status === $status) {
            return;
        }

        $request->update(['status' => $status]);
        ProviderRequestStatusEvent::query()->create([
            'provider_request_id' => $request->id,
            'status' => $status,
            'actor' => 'cbta.integration',
            'notes' => $remoteMessage ?: 'Estado conciliado automáticamente desde CBTA.',
            'occurred_at' => now(),
            'metadata' => ['remote_status' => $remoteStatus],
        ]);
    }

    private function applyRemoteDocuments(MixtureIntegration $integration, array $remote): void
    {
        $request = $integration->providerRequest;
        if (! $request) {
            return;
        }

        $payload = $request->payload ?? [];
        $payload['cbta'] = array_merge($payload['cbta'] ?? [], [
            'remission' => $remote['remission'] ?? null,
            'documents' => $remote['documents'] ?? [],
            'status_checked_at' => $remote['status_checked_at'] ?? null,
        ]);
        $request->update(['payload' => $payload]);
    }

    public function payload(MixtureIntegration $integration): array
    {
        $request = $integration->providerRequest;
        $payload = $request->payload ?? [];

        return [
            'local_external_id' => $integration->local_external_id,
            'external_id' => $request->external_id,
            'medical_unit_code' => $request->medicalUnit?->cbta_external_code,
            'catalog_type' => $integration->metadata['catalog_type'],
            'catalog_version' => $integration->catalog_version,
            'patient' => [
                'external_id' => $request->patient?->platform_number,
                'name' => $request->patient?->full_name,
            ],
            'clinical' => [
                'service' => $payload['service'] ?? null,
                'diagnosis' => $payload['diagnosis'] ?? null,
                'notes' => $payload['notes'] ?? null,
                'priority' => $payload['priority'] ?? 'routine',
                'required_at' => $request->required_at?->toIso8601String(),
                'format' => $payload['clinical_format'] ?? [],
                'doctor' => $payload['doctor'] ?? null,
            ],
            'items' => $payload['integration_items'] ?? [],
            'documents' => collect([$payload['attachment'] ?? null])
                ->filter()
                ->map(fn ($attachment) => [
                    'type' => $attachment['type'] ?? 'authorization',
                    'name' => $attachment['original_name'] ?? 'Documento de autorizacion',
                    'mime_type' => $attachment['mime'] ?? null,
                    'size' => $attachment['size'] ?? null,
                ])
                ->values()
                ->all(),
        ];
    }
}
