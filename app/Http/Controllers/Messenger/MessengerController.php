<?php

namespace App\Http\Controllers\Messenger;

use App\Http\Controllers\Controller;
use App\Models\DeliveryReport;
use App\Models\DeliveryRoute;
use App\Models\MessengerProfile;
use App\Models\Prescription;
use App\Services\Platform\PlatformAuditService;
use App\Services\Platform\DomainStateTransitionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MessengerController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $profile = MessengerProfile::query()
            ->with('user')
            ->where('user_id', $user?->id)
            ->first();

        if (! $profile && $user?->role === 'messenger') {
            $profile = MessengerProfile::query()->create([
                'user_id' => $user->id,
                'phone' => $user->metadata['phone'] ?? null,
                'vehicle' => 'Unidad por asignar',
                'status' => 'active',
                'metadata' => ['source' => 'native_messenger'],
            ]);
        }

        $routesQuery = DeliveryRoute::query()
            ->with([
                'messenger.user',
                'patientOrder.patient',
                'patientOrder.items.product',
                'providerRequest.provider',
                'providerRequest.patient',
                'providerRequest.medicalUnit',
                'reports' => fn ($query) => $query->latest('reported_at')->latest(),
            ])
            ->when($profile, fn ($query) => $query->where('messenger_profile_id', $profile->id))
            ->latest('scheduled_at')
            ->latest();

        $routeIds = (clone $routesQuery)->pluck('id');

        $metrics = [
            'Rutas asignadas' => (clone $routesQuery)->count(),
            'Activas' => (clone $routesQuery)->whereIn('status', ['planned', 'assigned', 'picked_up', 'in_route'])->count(),
            'Entregadas' => (clone $routesQuery)->where('status', 'delivered')->count(),
            'Reportes' => DeliveryReport::query()->whereIn('delivery_route_id', $routeIds)->count(),
        ];

        $routes = $routesQuery
            ->paginate(8)
            ->withQueryString();

        return view('messenger.dashboard', [
            'profile' => $profile,
            'routes' => $routes,
            'metrics' => $metrics,
            'recentReports' => DeliveryReport::query()
                ->with('route.patientOrder.patient')
                ->whereIn('delivery_route_id', $routeIds)
                ->latest('reported_at')
                ->latest()
                ->limit(8)
                ->get(),
        ]);
    }

    public function updateRouteStatus(Request $request, DeliveryRoute $route, PlatformAuditService $audit, DomainStateTransitionService $transitions): RedirectResponse
    {
        $this->authorizeRouteOwnership($request, $route);

        $data = $request->validate([
            'status' => ['required', Rule::in(['planned', 'assigned', 'picked_up', 'in_route', 'delivered', 'failed', 'cancelled'])],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $transitions->assertAllowed('delivery_route', $route->status, $data['status']);

        $metadata = $route->metadata ?? [];
        $metadata['last_status_changed_by'] = $request->user()?->id;
        $metadata['last_status_changed_at'] = now()->toISOString();
        $metadata['last_status_note'] = $data['notes'] ?? null;

        $route->update([
            'status' => $data['status'],
            'metadata' => $metadata,
        ]);

        $report = DeliveryReport::query()->create([
            'delivery_route_id' => $route->id,
            'status' => $data['status'],
            'notes' => $data['notes'] ?? null,
            'reported_at' => now(),
            'payload' => [
                'reported_by' => $request->user()?->id,
                'patient_order_id' => $route->patient_order_id,
                'provider_request_id' => $route->provider_request_id,
            ],
        ]);

        $audit->record($request, 'messenger.route.status_updated', $route, 'messenger', [
            'route_code' => $route->route_code,
            'status' => $route->status,
        ]);

        $audit->record($request, 'messenger.delivery_report.created', $report, 'messenger', [
            'route_id' => $route->id,
            'status' => $report->status,
        ]);

        if ($route->patientOrder && $data['status'] === 'delivered') {
            $orderMetadata = $route->patientOrder->metadata ?? [];
            $orderMetadata['delivered_by_route_id'] = $route->id;
            $orderMetadata['delivered_at'] = now()->toISOString();

            $route->patientOrder->update([
                'status' => 'delivered',
                'metadata' => $orderMetadata,
            ]);

            $audit->record($request, 'messenger.patient_order.delivered', $route->patientOrder, 'messenger', [
                'route_code' => $route->route_code,
            ]);
        }

        $this->syncDeliveryTrace($route, $report, $request, $data['status']);

        return back()->with('status', "Ruta {$route->route_code} actualizada.");
    }

    private function syncDeliveryTrace(DeliveryRoute $route, DeliveryReport $report, Request $request, string $status): void
    {
        $route->loadMissing('patientOrder');
        $order = $route->patientOrder;

        if (! $order) {
            return;
        }

        $timestamp = now()->toISOString();
        $orderMetadata = $order->metadata ?? [];
        $orderMetadata['messenger_route_id'] = $route->id;
        $orderMetadata['messenger_route_code'] = $route->route_code;
        $orderMetadata['messenger_route_status'] = $status;
        $orderMetadata['messenger_report_id'] = $report->id;
        $orderMetadata['messenger_reported_by'] = $request->user()?->id;
        $orderMetadata['messenger_status_at'] = $timestamp;
        $orderMetadata['messenger_last_note'] = $report->notes;

        if ($status === 'delivered') {
            $orderMetadata['messenger_delivered_at'] = $timestamp;
        }

        $order->update(['metadata' => $orderMetadata]);

        $prescriptionId = data_get($orderMetadata, 'prescription_id');
        if (! $prescriptionId) {
            return;
        }

        $prescription = Prescription::query()->find($prescriptionId);
        if (! $prescription) {
            return;
        }

        $prescriptionMetadata = $prescription->metadata ?? [];
        $prescriptionMetadata['messenger_patient_order_id'] = $order->id;
        $prescriptionMetadata['messenger_order_number'] = $order->order_number;
        $prescriptionMetadata['messenger_route_id'] = $route->id;
        $prescriptionMetadata['messenger_route_code'] = $route->route_code;
        $prescriptionMetadata['messenger_route_status'] = $status;
        $prescriptionMetadata['messenger_report_id'] = $report->id;
        $prescriptionMetadata['messenger_status_at'] = $timestamp;
        $prescriptionMetadata['messenger_last_note'] = $report->notes;

        if ($status === 'delivered') {
            $prescriptionMetadata['messenger_delivered_at'] = $timestamp;
        }

        $prescription->update(['metadata' => $prescriptionMetadata]);
    }

    private function authorizeRouteOwnership(Request $request, DeliveryRoute $route): void
    {
        $user = $request->user();

        if (in_array($user?->role, ['superadmin', 'admin'], true)) {
            return;
        }

        abort_unless(
            $user?->messengerProfile && (int) $route->messenger_profile_id === (int) $user->messengerProfile->id,
            403,
            'No puedes modificar rutas de otro mensajero.',
        );
    }
}
