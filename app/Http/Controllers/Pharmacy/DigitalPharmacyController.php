<?php

namespace App\Http\Controllers\Pharmacy;

use App\Http\Controllers\Controller;
use App\Models\DeliveryReport;
use App\Models\DeliveryRoute;
use App\Models\InventoryItem;
use App\Models\MessengerProfile;
use App\Models\OperationalProfile;
use App\Models\PatientOrder;
use App\Models\PharmacyProduct;
use App\Models\Service;
use App\Services\Platform\PlatformAuditService;
use App\Services\Platform\DomainStateTransitionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DigitalPharmacyController extends Controller
{
    public function index(Request $request): View
    {
        $section = $request->string('section')->toString() ?: 'dashboard';
        if (! in_array($section, ['dashboard', 'patient-home', 'catalog', 'specialties', 'inventory', 'missing', 'orders', 'messengers', 'traceability', 'deliveries', 'cold-chain', 'controlled', 'prescription', 'reports'], true)) {
            $section = 'dashboard';
        }

        $profile = OperationalProfile::query()
            ->with(['medicalUnit', 'area'])
            ->where('user_id', $request->user()?->id)
            ->first();

        $products = PharmacyProduct::query()
            ->withSum(['inventories as available_stock' => fn ($query) => $query->where('status', 'available')], 'quantity')
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search')->toString();
                $query->where(function ($inner) use ($search): void {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('generic_name', 'like', "%{$search}%")
                        ->orWhere('cnis', 'like', "%{$search}%")
                        ->orWhere('barcode', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        $allProducts = PharmacyProduct::query()
            ->withSum(['inventories as available_stock' => fn ($query) => $query->where('status', 'available')], 'quantity')
            ->orderBy('name')
            ->limit(14)
            ->get();

        $inventory = InventoryItem::query()
            ->with(['product', 'medicalUnit'])
            ->when($profile?->medical_unit_id, fn ($query) => $query->where('medical_unit_id', $profile->medical_unit_id))
            ->latest()
            ->limit(20)
            ->get();

        $orders = PatientOrder::query()
            ->with(['patient', 'items.product', 'deliveryRoutes.messenger.user', 'deliveryRoutes.reports'])
            ->latest('ordered_at')
            ->paginate(10, ['*'], 'orders_page')
            ->withQueryString();

        $routes = DeliveryRoute::query()
            ->with(['messenger.user', 'patientOrder.patient', 'patientOrder.items.product', 'reports'])
            ->latest('scheduled_at')
            ->latest()
            ->limit(12)
            ->get();

        $messengers = MessengerProfile::query()
            ->with(['user', 'deliveryRoutes.patientOrder.patient', 'deliveryRoutes.reports'])
            ->orderBy('id')
            ->limit(8)
            ->get();

        $deliveryReports = DeliveryReport::query()
            ->with(['route.patientOrder.patient', 'route.patientOrder.items.product'])
            ->latest('reported_at')
            ->latest()
            ->limit(12)
            ->get();

        $specialties = Service::query()
            ->whereNotNull('specialty')
            ->select('specialty')
            ->distinct()
            ->orderBy('specialty')
            ->limit(35)
            ->pluck('specialty');

        return view('pharmacy.dashboard', [
            'section' => $section,
            'profile' => $profile,
            'products' => $products,
            'allProducts' => $allProducts,
            'inventory' => $inventory,
            'orders' => $orders,
            'routes' => $routes,
            'messengers' => $messengers,
            'deliveryReports' => $deliveryReports,
            'specialties' => $specialties,
            'metrics' => [
                'Productos activos' => PharmacyProduct::query()->where('status', 'active')->count(),
                'Inventario disponible' => InventoryItem::query()->where('status', 'available')->sum('quantity'),
                'Pedidos abiertos' => PatientOrder::query()->whereNotIn('status', ['delivered', 'cancelled'])->count(),
                'Pedidos entregados' => PatientOrder::query()->where('status', 'delivered')->count(),
            ],
        ]);
    }

    public function updateOrderStatus(Request $request, PatientOrder $order, PlatformAuditService $audit, DomainStateTransitionService $transitions): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['created', 'received', 'preparing', 'in_route', 'delivered', 'cancelled'])],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $transitions->assertAllowed('patient_order', $order->status, $data['status']);

        $metadata = $order->metadata ?? [];
        $metadata['last_status_note'] = $data['notes'] ?? null;
        $metadata['last_status_changed_by'] = $request->user()?->id;
        $metadata['last_status_changed_at'] = now()->toISOString();

        $order->update([
            'status' => $data['status'],
            'metadata' => $metadata,
        ]);

        $audit->record($request, 'pharmacy.order.status_updated', $order, 'digital_pharmacy', [
            'order_number' => $order->order_number,
            'status' => $order->status,
        ]);

        if ($data['status'] === 'in_route') {
            $route = $this->createDeliveryRouteFor($order, $request);
            $audit->record($request, 'pharmacy.delivery_route.assigned', $route, 'digital_pharmacy', [
                'order_number' => $order->order_number,
            ]);
        }

        return back()->with('status', "Pedido {$order->order_number} actualizado.");
    }

    private function createDeliveryRouteFor(PatientOrder $order, Request $request): DeliveryRoute
    {
        $messenger = MessengerProfile::query()
            ->where('status', 'active')
            ->orderBy('id')
            ->first();

        return DeliveryRoute::query()->updateOrCreate(
            ['patient_order_id' => $order->id],
            [
                'messenger_profile_id' => $messenger?->id,
                'route_code' => 'RUTA-'.($order->order_number ?? 'PEDIDO-'.$order->id),
                'origin' => 'Farmacia Digital',
                'destination' => $order->patient?->full_name ?? 'Paciente sin nombre',
                'status' => 'assigned',
                'scheduled_at' => now(),
                'metadata' => [
                    'source' => 'digital_pharmacy',
                    'created_by' => $request->user()?->id,
                    'last_note' => $order->metadata['last_status_note'] ?? null,
                ],
            ],
        );
    }
}
