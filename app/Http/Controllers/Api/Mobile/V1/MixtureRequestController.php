<?php

namespace App\Http\Controllers\Api\Mobile\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Doctor\DoctorPortalController;
use App\Models\ProviderRequest;
use App\Services\Integrations\Cbta\CbtaCatalogClient;
use App\Services\Platform\PlatformAuditService;
use App\Support\MobileApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Throwable;

class MixtureRequestController extends Controller
{
    public function catalog(Request $request, string $type, CbtaCatalogClient $client): JsonResponse
    {
        abort_unless(in_array($type, ['npt', 'oncology'], true), 404);
        $doctor = $request->user()->doctor()->with('medicalUnit')->firstOrFail();
        $unitCode = $doctor->medicalUnit?->cbta_external_code;
        if (! $unitCode) {
            return MobileApiResponse::error('cbta_unit_missing', 'El médico no tiene una unidad vinculada con Mezclas.', 422);
        }

        try {
            $catalog = $type === 'npt' ? $client->nptCatalog($unitCode) : $client->oncologyCatalog($unitCode);
        } catch (Throwable $exception) {
            report($exception);

            return MobileApiResponse::error('catalog_unavailable', 'No fue posible consultar el catálogo de Mezclas.', 503);
        }

        return MobileApiResponse::success([
            'catalog' => $catalog,
            'doctor' => ['name' => $doctor->full_name, 'professional_license' => $doctor->professional_license],
            'medical_unit' => ['name' => $doctor->medicalUnit?->name, 'external_code' => $unitCode],
        ]);
    }

    public function prevalidate(Request $request, CbtaCatalogClient $client): JsonResponse
    {
        $doctor = $request->user()->doctor()->with('medicalUnit')->firstOrFail();
        $data = $request->validate([
            'catalog_type' => ['required', 'in:npt,oncology'],
            'catalog_version' => ['nullable', 'string', 'max:100'],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.product_code' => ['required', 'string', 'max:100'],
            'items.*.presentation_code' => ['required', 'string', 'max:100'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit' => ['required', 'in:ml,mg,unit'],
        ]);
        $unitCode = $doctor->medicalUnit?->cbta_external_code;
        if (! $unitCode) {
            return MobileApiResponse::error('cbta_unit_missing', 'El médico no tiene una unidad vinculada con Mezclas.', 422);
        }

        try {
            $result = $client->prevalidateMixture([
                'medical_unit_code' => $unitCode,
                ...$data,
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return MobileApiResponse::error('prevalidation_unavailable', 'Mezclas no está disponible para prevalidar la solicitud.', 503);
        }

        return MobileApiResponse::success(['prevalidation' => $result]);
    }

    public function store(
        Request $request,
        DoctorPortalController $webController,
        PlatformAuditService $audit,
        CbtaCatalogClient $client,
    ): JsonResponse {
        $idempotencyKey = trim((string) $request->header('Idempotency-Key'));
        if ($idempotencyKey === '' || mb_strlen($idempotencyKey) > 100) {
            throw ValidationException::withMessages(['idempotency_key' => 'Envía una clave de idempotencia válida.']);
        }
        $cacheKey = 'mobile-mixture:'.$request->user()->id.':'.hash('sha256', $idempotencyKey);
        $existing = Cache::get($cacheKey);
        if (is_int($existing)) {
            return MobileApiResponse::success(['request' => $this->requestData(ProviderRequest::query()->findOrFail($existing)), 'duplicate' => true]);
        }
        if (! Cache::add($cacheKey, 'processing', now()->addMinutes(5))) {
            return MobileApiResponse::error('request_in_progress', 'La solicitud ya se está procesando.', 409);
        }

        $startedAt = now()->subSecond();
        try {
            $webController->storeServiceRequest($request, $audit, $client);
            $doctorId = $request->user()->doctor()->value('id');
            $providerRequest = ProviderRequest::query()
                ->where('payload->doctor_id', $doctorId)
                ->where('created_at', '>=', $startedAt)
                ->latest('id')
                ->firstOrFail();
            Cache::put($cacheKey, $providerRequest->id, now()->addDay());

            return MobileApiResponse::success(['request' => $this->requestData($providerRequest), 'duplicate' => false], 201);
        } catch (Throwable $exception) {
            Cache::forget($cacheKey);
            throw $exception;
        }
    }

    public function show(Request $request, int $providerRequest): JsonResponse
    {
        $doctorId = $request->user()->doctor()->value('id');
        $model = ProviderRequest::query()
            ->with(['patient:id,full_name,platform_number', 'mixtureIntegration', 'statusEvents' => fn ($query) => $query->latest('occurred_at')])
            ->where('payload->doctor_id', $doctorId)
            ->findOrFail($providerRequest);

        return MobileApiResponse::success(['request' => $this->requestData($model)]);
    }

    private function requestData(ProviderRequest $request): array
    {
        $request->loadMissing(['patient:id,full_name,platform_number', 'mixtureIntegration', 'statusEvents']);

        return [
            'id' => $request->id,
            'external_id' => $request->external_id,
            'type' => $request->request_type,
            'status' => $request->status,
            'requested_at' => $request->requested_at?->toIso8601String(),
            'required_at' => $request->required_at?->toIso8601String(),
            'service' => data_get($request->payload, 'service'),
            'priority' => data_get($request->payload, 'priority'),
            'notes' => data_get($request->payload, 'notes'),
            'authorizations' => data_get($request->payload, 'authorizations', []),
            'integration' => $request->mixtureIntegration ? [
                'sync_status' => $request->mixtureIntegration->sync_status,
                'remote_status' => $request->mixtureIntegration->remote_status,
                'last_error' => $request->mixtureIntegration->last_error,
            ] : null,
            'patient' => $request->patient ? ['id' => $request->patient->id, 'full_name' => $request->patient->full_name, 'platform_number' => $request->patient->platform_number] : null,
            'events' => $request->statusEvents->map(fn ($event): array => ['status' => $event->status, 'actor' => $event->actor, 'occurred_at' => $event->occurred_at?->toIso8601String()])->values(),
        ];
    }
}
