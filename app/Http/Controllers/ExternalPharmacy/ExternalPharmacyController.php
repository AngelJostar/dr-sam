<?php

namespace App\Http\Controllers\ExternalPharmacy;

use App\Http\Controllers\Controller;
use App\Models\DeliveryRoute;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\MessengerProfile;
use App\Models\OperationalProfile;
use App\Models\PatientOrder;
use App\Models\PatientOrderItem;
use App\Models\PharmacyProduct;
use App\Models\PharmacyWarehouse;
use App\Services\Platform\PlatformAuditService;
use App\Services\Platform\DomainStateTransitionService;
use App\Services\PrescriptionPharmacyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ExternalPharmacyController extends Controller
{
    public function index(Request $request): View
    {
        $section = $request->string('section')->toString() ?: 'pending';
        $allowedSections = ['pending', 'filled', 'prescription', 'inventory', 'movements', 'warehouses', 'catalog'];

        if (! in_array($section, $allowedSections, true)) {
            $section = 'pending';
        }

        $profile = OperationalProfile::query()
            ->with(['medicalUnit.institution', 'area', 'user'])
            ->where('user_id', $request->user()?->id)
            ->first();

        $unitId = $profile?->medical_unit_id;
        $pharmacySearch = trim($request->string('search')->toString());
        $pharmacyStatus = $request->string('status')->toString();

        $orders = PatientOrder::query()
            ->with(['patient', 'items.product', 'deliveryRoutes.messenger.user'])
            ->when($unitId, fn ($query) => $query->where('metadata->medical_unit_id', $unitId))
            ->latest('ordered_at')
            ->paginate(10)
            ->withQueryString();

        $pendingOrders = PatientOrder::query()
            ->with(['patient', 'items.product'])
            ->whereNotIn('status', ['delivered', 'cancelled'])
            ->when($unitId, fn ($query) => $query->where('metadata->medical_unit_id', $unitId))
            ->when($pharmacyStatus && $pharmacyStatus !== 'all', fn ($query) => $query->where('status', $pharmacyStatus))
            ->when($pharmacySearch, fn ($query) => $query->where(function ($scope) use ($pharmacySearch): void {
                $scope->where('order_number', 'like', "%{$pharmacySearch}%")
                    ->orWhere('metadata->doctor', 'like', "%{$pharmacySearch}%")
                    ->orWhereHas('patient', fn ($patient) => $patient->where('full_name', 'like', "%{$pharmacySearch}%"))
                    ->orWhereHas('items', fn ($items) => $items->where('product_name', 'like', "%{$pharmacySearch}%"));
            }))
            ->latest('ordered_at')
            ->limit(10)
            ->get();

        $filledOrders = PatientOrder::query()
            ->with(['patient', 'items.product'])
            ->whereIn('status', ['in_route', 'delivered'])
            ->when($unitId, fn ($query) => $query->where('metadata->medical_unit_id', $unitId))
            ->latest('ordered_at')
            ->limit(10)
            ->get();

        $inventory = InventoryItem::query()
            ->with(['product', 'medicalUnit'])
            ->when($unitId, fn ($query) => $query->where('medical_unit_id', $unitId))
            ->latest()
            ->limit(14)
            ->get();

        $products = PharmacyProduct::query()
            ->withSum(['inventories as available_stock' => fn ($query) => $query->where('status', 'available')], 'quantity')
            ->orderBy('name')
            ->limit(14)
            ->get();

        $movements = InventoryMovement::query()
            ->with(['inventoryItem.product', 'user'])
            ->when($unitId, fn ($query) => $query->where('medical_unit_id', $unitId))
            ->latest()
            ->limit(30)
            ->get();

        $warehouses = PharmacyWarehouse::query()
            ->when($unitId, fn ($query) => $query->where('medical_unit_id', $unitId))
            ->orderByDesc('status')
            ->orderBy('name')
            ->get();

        return view('external_pharmacy.dashboard', [
            'section' => $section,
            'profile' => $profile,
            'orders' => $orders,
            'pendingOrders' => $pendingOrders,
            'filledOrders' => $filledOrders,
            'inventory' => $inventory,
            'movements' => $movements,
            'warehouses' => $warehouses,
            'products' => $products,
            'metrics' => [
                'Pedidos abiertos' => PatientOrder::query()->whereNotIn('status', ['delivered', 'cancelled'])->count(),
                'Pedidos en ruta' => PatientOrder::query()->where('status', 'in_route')->count(),
                'Pedidos entregados' => PatientOrder::query()->where('status', 'delivered')->count(),
                'Inventario disponible' => InventoryItem::query()
                    ->when($unitId, fn ($query) => $query->where('medical_unit_id', $unitId))
                    ->where('status', 'available')
                    ->sum('quantity'),
            ],
        ]);
    }

    public function storeMovement(Request $request, InventoryItem $inventoryItem, PlatformAuditService $audit): RedirectResponse
    {
        $profile = OperationalProfile::query()->where('user_id', $request->user()?->id)->first();
        $this->authorizeInventoryItem($inventoryItem, $profile);

        $data = $request->validate([
            'type' => ['required', Rule::in(['entry', 'exit', 'adjustment'])],
            'quantity' => ['required', 'integer', 'min:0', 'max:1000000'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($request, $inventoryItem, $profile, $data, $audit): void {
            $lockedItem = InventoryItem::query()->lockForUpdate()->findOrFail($inventoryItem->id);
            $before = (int) $lockedItem->quantity;
            $quantity = (int) $data['quantity'];
            $after = match ($data['type']) {
                'entry' => $before + $quantity,
                'exit' => $before - $quantity,
                'adjustment' => $quantity,
            };

            if ($after < 0) {
                abort(422, 'La salida supera la existencia disponible.');
            }

            $lockedItem->update([
                'quantity' => $after,
                'status' => $after > 0 ? 'available' : 'out_of_stock',
            ]);

            $movement = InventoryMovement::query()->create([
                'inventory_item_id' => $lockedItem->id,
                'medical_unit_id' => $profile?->medical_unit_id ?: $lockedItem->medical_unit_id,
                'user_id' => $request->user()?->id,
                'type' => $data['type'],
                'quantity' => $quantity,
                'stock_before' => $before,
                'stock_after' => $after,
                'source' => 'Operacion manual',
                'notes' => $data['notes'] ?? null,
            ]);

            $audit->record($request, 'external_pharmacy.inventory.movement_created', $movement, 'external_pharmacy', [
                'inventory_item_id' => $lockedItem->id,
                'type' => $data['type'],
                'stock_before' => $before,
                'stock_after' => $after,
            ]);
        });

        return redirect()->route('external-pharmacy.dashboard', ['section' => 'movements'])
            ->with('status', 'Movimiento de inventario registrado.');
    }

    public function storeWarehouse(Request $request, PlatformAuditService $audit): RedirectResponse
    {
        $profile = OperationalProfile::query()->where('user_id', $request->user()?->id)->first();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'type' => ['required', Rule::in(['general', 'dispensing', 'controlled', 'cold_chain'])],
            'responsible' => ['nullable', 'string', 'max:150'],
        ]);

        $warehouse = PharmacyWarehouse::query()->create([
            'medical_unit_id' => $profile?->medical_unit_id,
            ...$data,
            'status' => 'active',
        ]);

        $audit->record($request, 'external_pharmacy.warehouse.created', $warehouse, 'external_pharmacy');

        return redirect()->route('external-pharmacy.dashboard', ['section' => 'warehouses'])
            ->with('status', 'Almacen registrado.');
    }

    public function updateWarehouseStatus(Request $request, PharmacyWarehouse $warehouse, PlatformAuditService $audit): RedirectResponse
    {
        $profile = OperationalProfile::query()->where('user_id', $request->user()?->id)->first();
        abort_unless(! $profile?->medical_unit_id || $warehouse->medical_unit_id === $profile->medical_unit_id, 404);

        $data = $request->validate(['status' => ['required', Rule::in(['active', 'inactive'])]]);
        $warehouse->update(['status' => $data['status']]);
        $audit->record($request, 'external_pharmacy.warehouse.status_updated', $warehouse, 'external_pharmacy', $data);

        return back()->with('status', 'Estatus del almacen actualizado.');
    }

    public function updateOrderStatus(Request $request, PatientOrder $order, PlatformAuditService $audit, DomainStateTransitionService $transitions): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['received', 'preparing', 'in_route', 'delivered', 'cancelled'])],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $transitions->assertAllowed('patient_order', $order->status, $data['status']);

        $metadata = $order->metadata ?? [];
        $metadata['external_pharmacy_note'] = $data['notes'] ?? null;
        $metadata['external_pharmacy_updated_by'] = $request->user()?->id;
        $metadata['external_pharmacy_updated_at'] = now()->toISOString();

        $order->update([
            'status' => $data['status'],
            'metadata' => $metadata,
        ]);

        $audit->record($request, 'external_pharmacy.order.status_updated', $order, 'external_pharmacy', [
            'order_number' => $order->order_number,
            'status' => $order->status,
        ]);

        if ($data['status'] === 'in_route') {
            $route = $this->createDeliveryRouteFor($order, $request);
            $audit->record($request, 'external_pharmacy.delivery_route.assigned', $route, 'external_pharmacy', [
                'order_number' => $order->order_number,
            ]);
        }

        return back()->with('status', "Pedido {$order->order_number} actualizado por farmacia externa.");
    }

    public function dispenseItem(Request $request, PatientOrderItem $item, PrescriptionPharmacyService $pharmacy, PlatformAuditService $audit): RedirectResponse
    {
        $profile = OperationalProfile::query()->where('user_id', $request->user()?->id)->first();
        $unitId = $profile?->medical_unit_id ?: (int) data_get($item->order?->metadata, 'medical_unit_id');
        abort_unless(! $profile?->medical_unit_id || (int) data_get($item->order?->metadata, 'medical_unit_id') === $profile->medical_unit_id, 404);
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:1']]);
        [$order, $prescription] = $pharmacy->dispense($item, (int) $data['quantity'], $unitId ?: null);
        $audit->record($request, 'external_pharmacy.prescription_item.dispensed', $item, 'external_pharmacy', ['quantity' => (int) $data['quantity'], 'order_id' => $order->id, 'prescription_id' => $prescription?->id]);

        return back()->with('status', $order->status === 'delivered' ? 'Receta surtida completamente.' : 'Surtimiento parcial registrado.');
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
                'route_code' => 'RUTA-EXT-'.($order->order_number ?? 'PEDIDO-'.$order->id),
                'origin' => 'Farmacia Externa',
                'destination' => $order->patient?->full_name ?? 'Paciente sin nombre',
                'status' => 'assigned',
                'scheduled_at' => now(),
                'metadata' => [
                    'source' => 'external_pharmacy',
                    'created_by' => $request->user()?->id,
                ],
            ],
        );
    }

    private function authorizeInventoryItem(InventoryItem $inventoryItem, ?OperationalProfile $profile): void
    {
        abort_unless(! $profile?->medical_unit_id || $inventoryItem->medical_unit_id === $profile->medical_unit_id, 404);
    }
}
