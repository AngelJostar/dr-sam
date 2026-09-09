<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\DeliveryRoute;
use App\Models\DeliveryReport;
use App\Models\MessengerProfile;
use App\Models\Provider;
use App\Models\ProviderRequest;
use App\Models\ProviderRequestStatusEvent;
use App\Services\Platform\PlatformAuditService;
use App\Services\Platform\DomainStateTransitionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProviderPortalController extends Controller
{
    private const TYPES = [
        'npt' => [
            'label' => 'Proveedor NPT',
            'module' => 'provider_npt',
            'request_types' => ['npt'],
            'provider_types' => ['npt'],
        ],
        'chemotherapy' => [
            'label' => 'Proveedor Quimioterapias',
            'module' => 'provider_chemo',
            'request_types' => ['chemotherapy', 'chemo'],
            'provider_types' => ['chemotherapy', 'chemo'],
        ],
        'import' => [
            'label' => 'Proveedor Importacion',
            'module' => 'provider_import',
            'request_types' => ['import'],
            'provider_types' => ['import'],
        ],
        'medicines' => [
            'label' => 'Distribuidor de medicamentos',
            'module' => 'provider_medicines',
            'request_types' => ['medicines', 'medication'],
            'provider_types' => ['medicines', 'medication', 'distributor'],
        ],
        'clinical-labs' => [
            'label' => 'Proveedor analisis clinicos',
            'module' => 'provider_clinical_labs',
            'request_types' => ['clinical_labs', 'clinical-labs', 'laboratory'],
            'provider_types' => ['clinical_labs', 'clinical-labs', 'laboratory'],
        ],
    ];

    public function index(Request $request, string $type): View
    {
        abort_unless(isset(self::TYPES[$type]), 404);

        $provider = $this->resolveProvider($request, $type);
        $typeConfig = self::TYPES[$type];
        $requestTypes = $typeConfig['request_types'];

        $requestsQuery = ProviderRequest::query()
            ->with([
                'patient',
                'medicalUnit.institution',
                'provider',
                'mixtureIntegration',
                'statusEvents' => fn ($query) => $query->latest('occurred_at')->latest(),
                'deliveryRoutes.messenger.user',
            ])
            ->where(function ($query) use ($provider, $requestTypes): void {
                $query->whereIn('request_type', $requestTypes);

                if ($provider) {
                    $query->orWhere('provider_id', $provider->id);
                }
            })
            ->when($provider, fn ($query) => $query->where('provider_id', $provider->id))
            ->latest('requested_at')
            ->latest();

        $requests = $requestsQuery
            ->paginate(10)
            ->withQueryString();

        $deliveryRoutes = DeliveryRoute::query()
            ->with(['messenger.user', 'providerRequest.medicalUnit.institution'])
            ->whereHas('providerRequest', function ($query) use ($provider, $requestTypes): void {
                $query->whereIn('request_type', $requestTypes)
                    ->when($provider, fn ($providerQuery) => $providerQuery->where('provider_id', $provider->id));
            })
            ->latest('scheduled_at')
            ->latest()
            ->get();

        $messengers = MessengerProfile::query()
            ->with(['user', 'deliveryRoutes' => function ($query) use ($provider, $requestTypes): void {
                $query->with('providerRequest.medicalUnit.institution')
                    ->whereHas('providerRequest', function ($requestQuery) use ($provider, $requestTypes): void {
                        $requestQuery->whereIn('request_type', $requestTypes)
                            ->when($provider, fn ($providerQuery) => $providerQuery->where('provider_id', $provider->id));
                    })
                    ->latest('scheduled_at');
            }])
            ->whereHas('deliveryRoutes.providerRequest', function ($query) use ($provider, $requestTypes): void {
                $query->whereIn('request_type', $requestTypes)
                    ->when($provider, fn ($providerQuery) => $providerQuery->where('provider_id', $provider->id));
            })
            ->orderBy('id')
            ->get();

        return view('provider.dashboard', [
            'type' => $type,
            'typeConfig' => $typeConfig,
            'provider' => $provider,
            'requests' => $requests,
            'deliveryRoutes' => $deliveryRoutes,
            'messengers' => $messengers,
            'metrics' => [
                'Solicitudes' => (clone $requestsQuery)->count(),
                'Abiertas' => (clone $requestsQuery)->whereNotIn('status', ['delivered', 'cancelled', 'rejected'])->count(),
                'En ruta' => (clone $requestsQuery)->where('status', 'in_route')->count(),
                'Entregadas' => (clone $requestsQuery)->where('status', 'delivered')->count(),
            ],
        ]);
    }

    public function updateRequestStatus(Request $request, string $type, ProviderRequest $providerRequest, PlatformAuditService $audit, DomainStateTransitionService $transitions): RedirectResponse
    {
        abort_unless(isset(self::TYPES[$type]), 404);

        $provider = $this->resolveProvider($request, $type);

        if ($provider) {
            abort_unless((int) $providerRequest->provider_id === (int) $provider->id, 403);
        }

        $data = $request->validate([
            'status' => ['required', Rule::in(['requested', 'accepted', 'dispensed', 'preparing', 'ready', 'in_route', 'delivered', 'rejected', 'cancelled'])],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $transitions->assertAllowed('provider_request', $providerRequest->status, $data['status']);

        $providerRequest->update([
            'status' => $data['status'],
        ]);

        $event = ProviderRequestStatusEvent::query()->create([
            'provider_request_id' => $providerRequest->id,
            'status' => $data['status'],
            'actor' => $request->user()?->name,
            'notes' => $data['notes'] ?? null,
            'occurred_at' => now(),
            'metadata' => [
                'source' => 'provider_portal',
                'provider_type' => $type,
                'user_id' => $request->user()?->id,
            ],
        ]);

        $audit->record($request, 'provider.request.status_updated', $providerRequest, 'provider', [
            'provider_type' => $type,
            'status_event_id' => $event->id,
            'status' => $providerRequest->status,
        ]);

        if ($data['status'] === 'in_route') {
            $route = $this->createDeliveryRouteFor($providerRequest, $type, $request);
            $audit->record($request, 'provider.delivery_route.assigned', $route, 'provider', [
                'provider_request_id' => $providerRequest->id,
                'provider_type' => $type,
            ]);
        }

        return back()->with('status', 'Solicitud actualizada por proveedor.');
    }

    public function updateDeliveryRouteStatus(Request $request, string $type, DeliveryRoute $deliveryRoute, PlatformAuditService $audit, DomainStateTransitionService $transitions): RedirectResponse
    {
        abort_unless(isset(self::TYPES[$type]), 404);

        $provider = $this->resolveProvider($request, $type);
        $deliveryRoute->loadMissing('providerRequest.provider');
        $providerRequest = $deliveryRoute->providerRequest;

        abort_unless($providerRequest, 404);
        abort_unless(in_array($providerRequest->request_type, self::TYPES[$type]['request_types'], true), 403);
        if ($provider) {
            abort_unless((int) $providerRequest->provider_id === (int) $provider->id, 403);
        }

        $data = $request->validate([
            'status' => ['required', Rule::in(['picked_up', 'in_route', 'delivered', 'failed'])],
            'notes' => ['nullable', 'string', 'max:1000'],
            'received_by' => ['nullable', 'string', 'max:150'],
        ]);

        $transitions->assertAllowed('delivery_route', $deliveryRoute->status, $data['status']);
        $deliveryRoute->update(['status' => $data['status']]);

        $report = DeliveryReport::query()->create([
            'delivery_route_id' => $deliveryRoute->id,
            'status' => $data['status'],
            'notes' => $data['notes'] ?? null,
            'payload' => [
                'source' => 'provider_portal',
                'provider_type' => $type,
                'reported_by' => $request->user()?->id,
                'received_by' => $data['received_by'] ?? null,
            ],
            'reported_at' => now(),
        ]);

        $requestStatus = $data['status'] === 'delivered' ? 'delivered' : 'in_route';
        if ($providerRequest->status !== $requestStatus) {
            $transitions->assertAllowed('provider_request', $providerRequest->status, $requestStatus);
        }

        $payload = $providerRequest->payload ?? [];
        if ($data['status'] === 'delivered') {
            $payload['remission'] = [
                'route_code' => $deliveryRoute->route_code,
                'delivered_at' => now()->toIso8601String(),
                'received_by' => $data['received_by'] ?? null,
                'notes' => $data['notes'] ?? null,
                'report_id' => $report->id,
            ];
        }

        $providerRequest->update([
            'status' => $requestStatus,
            'payload' => $payload,
        ]);

        ProviderRequestStatusEvent::query()->create([
            'provider_request_id' => $providerRequest->id,
            'status' => $requestStatus,
            'actor' => $request->user()?->name,
            'notes' => $data['notes'] ?? null,
            'occurred_at' => now(),
            'metadata' => ['source' => 'provider_route', 'delivery_route_id' => $deliveryRoute->id],
        ]);

        $audit->record($request, 'provider.delivery_route.status_updated', $deliveryRoute, 'provider', [
            'provider_request_id' => $providerRequest->id,
            'delivery_report_id' => $report->id,
            'status' => $data['status'],
        ]);

        return back()->with('status', $data['status'] === 'delivered' ? 'Entrega y remision registradas.' : 'Ruta actualizada.');
    }

    private function resolveProvider(Request $request, string $type): ?Provider
    {
        $user = $request->user();

        $typeConfig = self::TYPES[$type];

        return Provider::query()
            ->where('user_id', $user?->id)
            ->whereIn('provider_type', $typeConfig['provider_types'])
            ->first();
    }

    private function createDeliveryRouteFor(ProviderRequest $providerRequest, string $type, Request $request): DeliveryRoute
    {
        $messenger = MessengerProfile::query()
            ->where('status', 'active')
            ->orderBy('id')
            ->first();

        return DeliveryRoute::query()->updateOrCreate(
            ['provider_request_id' => $providerRequest->id],
            [
                'messenger_profile_id' => $messenger?->id,
                'route_code' => 'RUTA-PROV-'.$providerRequest->id,
                'origin' => $providerRequest->provider?->name ?? self::TYPES[$type]['label'],
                'destination' => $providerRequest->medicalUnit?->name ?? $providerRequest->patient?->full_name ?? 'Destino pendiente',
                'status' => 'assigned',
                'scheduled_at' => now(),
                'metadata' => [
                    'source' => 'provider_portal',
                    'provider_type' => $type,
                    'created_by' => $request->user()?->id,
                ],
            ],
        );
    }
}
