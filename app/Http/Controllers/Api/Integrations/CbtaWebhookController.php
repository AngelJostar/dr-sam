<?php

namespace App\Http\Controllers\Api\Integrations;

use App\Http\Controllers\Controller;
use App\Models\MixtureIntegration;
use App\Services\Integrations\Cbta\MixtureIntegrationSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CbtaWebhookController extends Controller
{
    public function __invoke(Request $request, MixtureIntegrationSyncService $sync): JsonResponse
    {
        $secret = (string) config('cbta.webhook_secret');
        abort_if($secret === '', 503, 'El webhook CBTA no esta configurado.');

        $timestamp = (int) $request->header('X-CBTA-Timestamp');
        $signature = (string) $request->header('X-CBTA-Signature');
        $tolerance = (int) config('cbta.webhook_tolerance', 300);
        abort_if($timestamp <= 0 || abs(now()->timestamp - $timestamp) > $tolerance, 401, 'Webhook expirado.');

        $expected = hash_hmac('sha256', $timestamp.'.'.$request->getContent(), $secret);
        abort_unless(hash_equals($expected, $signature), 401, 'Firma de webhook invalida.');

        $validated = $request->validate([
            'event_id' => ['nullable', 'uuid'],
            'event_type' => ['nullable', 'in:mixture.status_changed'],
            'request_id' => ['required', 'string', 'max:100'],
            'status' => ['required', 'string', 'max:50'],
            'event_at' => ['required', 'date'],
        ]);

        $integration = MixtureIntegration::query()
            ->where('cbta_request_id', $validated['request_id'])
            ->firstOrFail();

        $synchronized = $sync->sync($integration);

        $integration->refresh();
        $integration->update([
            'metadata' => array_merge($integration->metadata ?? [], [
                'last_webhook_event_id' => $validated['event_id'] ?? null,
                'last_webhook_event_type' => $validated['event_type'] ?? 'mixture.status_changed',
                'last_webhook_status' => $validated['status'],
                'last_webhook_received_at' => now()->toIso8601String(),
            ]),
        ]);

        return response()->json([
            'accepted' => true,
            'synchronized' => $synchronized,
            'request_id' => $validated['request_id'],
            'event_id' => $validated['event_id'] ?? null,
        ], $synchronized ? 200 : 202);
    }
}
