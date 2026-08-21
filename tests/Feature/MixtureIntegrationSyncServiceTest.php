<?php

namespace Tests\Feature;

use App\Models\MixtureIntegration;
use App\Models\ProviderRequest;
use App\Services\Integrations\Cbta\MixtureIntegrationSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class MixtureIntegrationSyncServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_preserves_a_remote_processing_error_for_visual_follow_up(): void
    {
        config()->set('cbta.base_url', 'https://cbta.test');
        config()->set('cbta.token', 'service-token');
        Http::fake([
            'https://cbta.test/api/internal/v1/mixture-requests/CBTA-ERROR-1' => Http::response([
                'data' => [
                    'request_id' => 'CBTA-ERROR-1',
                    'status' => 'materialization_failed',
                    'integration_stage' => 'materialization',
                    'status_message' => 'La Bolsa EVA configurada no tiene existencia disponible.',
                    'has_error' => true,
                    'catalog_version' => 'npt-v1',
                    'status_details' => null,
                    'documents' => [],
                ],
            ]),
        ]);
        $request = ProviderRequest::query()->create([
            'request_type' => 'nutrition',
            'status' => 'requested',
            'requested_at' => now(),
            'payload' => [],
        ]);
        $integration = MixtureIntegration::query()->create([
            'provider_request_id' => $request->id,
            'local_external_id' => (string) Str::uuid(),
            'cbta_request_id' => 'CBTA-ERROR-1',
            'remote_status' => 'pending',
            'sync_status' => 'synced',
            'catalog_version' => 'npt-v1',
            'metadata' => ['catalog_type' => 'npt'],
        ]);

        $this->assertTrue(app(MixtureIntegrationSyncService::class)->sync($integration));

        $integration->refresh();
        $this->assertSame('materialization_failed', $integration->remote_status);
        $this->assertSame('La Bolsa EVA configurada no tiene existencia disponible.', $integration->last_error);
        $this->assertTrue($integration->metadata['remote_has_error']);
        $this->assertSame('materialization', $integration->metadata['remote_integration_stage']);
    }

    public function test_it_reconciles_the_remote_lifecycle_without_duplicating_status_events(): void
    {
        config()->set('cbta.base_url', 'https://cbta.test');
        config()->set('cbta.token', 'service-token');
        Http::fake([
            'https://cbta.test/api/internal/v1/mixture-requests/CBTA-REQ-1' => Http::response([
                'data' => [
                    'request_id' => 'CBTA-REQ-1',
                    'status' => 'ready',
                    'catalog_version' => 'oncology-v1',
                    'status_details' => [
                        'domain' => 'oncology',
                        'source_status' => 'enproceso',
                        'mixture_statuses' => ['revisada' => 1],
                        'inventory' => [
                            'policy' => 'consume_on_operational_approval',
                            'stage' => 'consumed',
                            'validated' => true,
                            'reserved' => false,
                            'consumed' => true,
                            'movement_count' => 1,
                            'consumed_quantity' => 1,
                            'quantity_unit' => 'units',
                        ],
                    ],
                    'status_checked_at' => '2026-08-05T12:00:00-06:00',
                    'remission' => [
                        'available' => true,
                        'number' => 'REM-ONCO-1001',
                        'document_type' => 'delivery_remission',
                        'issued_at' => '2026-08-05T11:55:00-06:00',
                    ],
                    'documents' => [[
                        'type' => 'authorization',
                        'name' => 'autorizacion.pdf',
                        'mime_type' => 'application/pdf',
                        'size' => 1500,
                    ]],
                ],
            ]),
        ]);
        $request = ProviderRequest::query()->create([
            'request_type' => 'chemo',
            'status' => 'requested',
            'requested_at' => now(),
            'payload' => [],
        ]);
        $integration = MixtureIntegration::query()->create([
            'provider_request_id' => $request->id,
            'local_external_id' => (string) Str::uuid(),
            'cbta_request_id' => 'CBTA-REQ-1',
            'remote_status' => 'pending',
            'sync_status' => 'synced',
            'catalog_version' => 'oncology-v1',
            'metadata' => ['catalog_type' => 'oncology'],
        ]);

        $service = app(MixtureIntegrationSyncService::class);
        $this->assertTrue($service->sync($integration));
        $this->assertTrue($service->sync($integration->fresh()));

        $this->assertDatabaseHas('provider_requests', ['id' => $request->id, 'status' => 'ready']);
        $this->assertDatabaseHas('mixture_integrations', [
            'id' => $integration->id,
            'remote_status' => 'ready',
            'sync_status' => 'synced',
        ]);
        $this->assertDatabaseCount('provider_request_status_events', 1);
        $this->assertSame(1, $integration->fresh()->metadata['remote_status_details']['mixture_statuses']['revisada']);
        $this->assertSame('consumed', $integration->fresh()->metadata['remote_status_details']['inventory']['stage']);
        $this->assertSame('REM-ONCO-1001', $request->fresh()->payload['cbta']['remission']['number']);
        $this->assertSame('autorizacion.pdf', $request->fresh()->payload['cbta']['documents'][0]['name']);
    }

    public function test_signed_cbta_webhook_triggers_an_authoritative_status_sync(): void
    {
        config()->set('cbta.base_url', 'https://cbta.test');
        config()->set('cbta.token', 'service-token');
        config()->set('cbta.webhook_secret', 'webhook-secret');
        Http::fake([
            'https://cbta.test/api/internal/v1/mixture-requests/CBTA-WEBHOOK-1' => Http::response([
                'data' => [
                    'request_id' => 'CBTA-WEBHOOK-1',
                    'status' => 'preparing',
                    'catalog_version' => 'npt-v1',
                    'status_details' => ['domain' => 'npt', 'inventory' => ['stage' => 'consumed']],
                    'documents' => [],
                ],
            ]),
        ]);
        $request = ProviderRequest::query()->create([
            'request_type' => 'nutrition',
            'status' => 'requested',
            'requested_at' => now(),
            'payload' => [],
        ]);
        MixtureIntegration::query()->create([
            'provider_request_id' => $request->id,
            'local_external_id' => (string) Str::uuid(),
            'cbta_request_id' => 'CBTA-WEBHOOK-1',
            'remote_status' => 'pending',
            'sync_status' => 'synced',
            'catalog_version' => 'npt-v1',
            'metadata' => ['catalog_type' => 'npt'],
        ]);
        $payload = json_encode([
            'event_id' => '49e4cc40-3eba-45cd-a905-99373024e24c',
            'event_type' => 'mixture.status_changed',
            'request_id' => 'CBTA-WEBHOOK-1',
            'status' => 'preparing',
            'event_at' => now()->toIso8601String(),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $timestamp = now()->timestamp;

        $this->call('POST', '/api/integrations/cbta/mixture-status', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_CBTA_TIMESTAMP' => (string) $timestamp,
            'HTTP_X_CBTA_SIGNATURE' => hash_hmac('sha256', $timestamp.'.'.$payload, 'webhook-secret'),
        ], $payload)->assertOk()->assertJson(['accepted' => true, 'synchronized' => true]);

        $this->assertSame('preparing', $request->fresh()->status);
        $this->assertSame(
            '49e4cc40-3eba-45cd-a905-99373024e24c',
            MixtureIntegration::query()->where('cbta_request_id', 'CBTA-WEBHOOK-1')->firstOrFail()->metadata['last_webhook_event_id']
        );
    }

    public function test_cbta_webhook_rejects_an_invalid_signature(): void
    {
        config()->set('cbta.webhook_secret', 'webhook-secret');
        $payload = json_encode([
            'request_id' => 'CBTA-UNKNOWN',
            'status' => 'ready',
            'event_at' => now()->toIso8601String(),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $this->call('POST', '/api/integrations/cbta/mixture-status', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_CBTA_TIMESTAMP' => (string) now()->timestamp,
            'HTTP_X_CBTA_SIGNATURE' => 'invalid',
        ], $payload)->assertUnauthorized();
    }

    public function test_a_temporary_sync_failure_uses_exponential_retry_tracking(): void
    {
        config()->set('cbta.base_url', 'https://cbta.test');
        config()->set('cbta.token', 'service-token');
        $available = false;
        Http::fake(function () use (&$available) {
            return $available
                ? Http::response(['data' => [
                    'request_id' => 'CBTA-RETRY-1',
                    'status' => 'ready',
                    'catalog_version' => 'npt-v1',
                    'documents' => [],
                ]])
                : Http::response(['message' => 'unavailable'], 503);
        });
        $request = ProviderRequest::query()->create([
            'request_type' => 'nutrition',
            'status' => 'requested',
            'requested_at' => now(),
            'payload' => [],
        ]);
        $integration = MixtureIntegration::query()->create([
            'provider_request_id' => $request->id,
            'local_external_id' => (string) Str::uuid(),
            'cbta_request_id' => 'CBTA-RETRY-1',
            'remote_status' => 'pending',
            'sync_status' => 'synced',
            'catalog_version' => 'npt-v1',
            'metadata' => ['catalog_type' => 'npt'],
        ]);

        $this->assertFalse(app(MixtureIntegrationSyncService::class)->sync($integration));

        $integration->refresh();
        $this->assertSame('failed', $integration->sync_status);
        $this->assertSame(1, $integration->sync_attempts);
        $this->assertNotNull($integration->next_retry_at);
        $this->assertTrue($integration->next_retry_at->isFuture());

        $available = true;
        $this->artisan('cbta:sync-mixtures')->expectsOutput('Integraciones procesadas: 0; fallidas: 0.')->assertSuccessful();
        $integration->update(['next_retry_at' => now()->subSecond()]);
        $synchronized = app(MixtureIntegrationSyncService::class)->sync($integration->fresh());
        $this->assertTrue($synchronized, (string) $integration->fresh()->last_error);
        $this->assertSame(0, $integration->fresh()->sync_attempts);
        $this->assertNull($integration->fresh()->next_retry_at);
    }
}
