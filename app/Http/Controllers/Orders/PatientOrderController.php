<?php

namespace App\Http\Controllers\Orders;

use App\Http\Controllers\Controller;
use App\Http\Requests\Orders\StorePatientOrderRequest;
use App\Models\Patient;
use App\Models\PatientOrder;
use App\Models\PatientOrderItem;
use App\Models\PharmacyProduct;
use App\Services\Platform\PlatformAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PatientOrderController extends Controller
{
    public function index(Request $request): View
    {
        $patient = $this->resolvePatient($request);

        $products = PharmacyProduct::query()
            ->withSum(['inventories as available_stock' => fn ($query) => $query->where('status', 'available')], 'quantity')
            ->where('status', 'active')
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search')->toString();
                $query->where(function ($inner) use ($search): void {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('generic_name', 'like', "%{$search}%")
                        ->orWhere('cnis', 'like', "%{$search}%")
                        ->orWhere('presentation', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        $orders = PatientOrder::query()
            ->with(['items.product'])
            ->where('patient_id', $patient?->id)
            ->latest('ordered_at')
            ->paginate(8, ['*'], 'orders_page')
            ->withQueryString();

        return view('orders.index', [
            'patient' => $patient,
            'products' => $products,
            'orders' => $orders,
        ]);
    }

    public function store(StorePatientOrderRequest $request, PlatformAuditService $audit): RedirectResponse
    {
        $patient = $this->resolvePatient($request);
        $product = PharmacyProduct::query()->findOrFail($request->integer('pharmacy_product_id'));
        $quantity = $request->integer('quantity');
        $subtotal = (float) $product->price * $quantity;

        $order = PatientOrder::query()->create([
            'patient_id' => $patient?->id,
            'order_number' => $this->nextOrderNumber(),
            'channel' => 'digital',
            'status' => 'created',
            'subtotal' => $subtotal,
            'total' => $subtotal,
            'ordered_at' => now(),
            'metadata' => [
                'delivery_mode' => $request->validated('delivery_mode'),
                'payment_method' => $request->validated('payment_method'),
                'source' => 'orders_blade',
            ],
        ]);

        PatientOrderItem::query()->create([
            'patient_order_id' => $order->id,
            'pharmacy_product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => $quantity,
            'unit_price' => $product->price,
            'total' => $subtotal,
            'metadata' => [
                'cnis' => $product->cnis,
                'presentation' => $product->presentation,
            ],
        ]);

        $audit->record($request, 'orders.patient_order.created', $order, 'orders', [
            'order_number' => $order->order_number,
            'total' => $order->total,
        ]);

        return redirect()
            ->route('orders.index')
            ->with('status', "Pedido {$order->order_number} creado correctamente.");
    }

    private function resolvePatient(Request $request): ?Patient
    {
        $user = $request->user();

        if ($user?->patient) {
            return $user->patient;
        }

        abort_unless(in_array($user?->role, ['superadmin', 'admin', 'operational'], true), 403);

        return Patient::query()->orderBy('full_name')->first();
    }

    private function nextOrderNumber(): string
    {
        return 'PED-'.now()->format('ymd').'-'.Str::upper(Str::random(5));
    }
}
