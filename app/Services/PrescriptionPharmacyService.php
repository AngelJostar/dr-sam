<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\PatientOrder;
use App\Models\PatientOrderItem;
use App\Models\PharmacyProduct;
use App\Models\Prescription;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PrescriptionPharmacyService
{
    public function sync(Prescription $prescription): PatientOrder
    {
        return DB::transaction(function () use ($prescription): PatientOrder {
            $prescription->loadMissing(['items', 'doctor']);
            $order = PatientOrder::query()->where('metadata->prescription_id', $prescription->id)->first();
            $order ??= new PatientOrder();
            $order->fill([
                'patient_id' => $prescription->patient_id,
                'order_number' => $prescription->code,
                'channel' => 'prescription',
                'status' => $order->exists ? $order->status : 'received',
                'ordered_at' => $prescription->issued_at,
                'metadata' => [...($order->metadata ?? []), 'prescription_id' => $prescription->id, 'medical_unit_id' => data_get($prescription->metadata, 'medical_unit_id'), 'doctor' => $prescription->doctor?->full_name, 'service' => 'Consulta externa'],
            ])->save();

            $existing = $order->items()->get()->keyBy(fn ($item) => (int) data_get($item->metadata, 'prescription_item_id'));
            $kept = [];
            foreach ($prescription->items as $prescriptionItem) {
                $product = $this->productFor($prescriptionItem->medication_name, data_get($prescriptionItem->metadata, 'cnis'));
                $item = $existing->get($prescriptionItem->id) ?? new PatientOrderItem(['patient_order_id' => $order->id]);
                $quantity = max(1, (int) filter_var((string) data_get($prescriptionItem->metadata, 'quantity', 1), FILTER_SANITIZE_NUMBER_INT));
                $filled = min($quantity, (int) data_get($item->metadata, 'filled_quantity', 0));
                $item->fill(['pharmacy_product_id' => $product?->id, 'product_name' => $prescriptionItem->medication_name, 'quantity' => $quantity, 'unit_price' => $product?->price ?? 0, 'total' => ($product?->price ?? 0) * $quantity, 'metadata' => [...($item->metadata ?? []), 'prescription_item_id' => $prescriptionItem->id, 'prescribed_quantity' => $quantity, 'filled_quantity' => $filled]])->save();
                $kept[] = $item->id;
            }
            $order->items()->whereNotIn('id', $kept)->delete();
            $order->update(['subtotal' => $order->items()->sum('total'), 'total' => $order->items()->sum('total')]);
            $this->syncPrescriptionTrace($prescription, $order, 'received', ['pharmacy_synced_at' => now()->toDateTimeString()]);

            return $order->fresh('items');
        });
    }

    public function dispense(PatientOrderItem $item, int $quantity, ?int $unitId): array
    {
        return DB::transaction(function () use ($item, $quantity, $unitId): array {
            $item->refresh();
            $prescribed = (int) data_get($item->metadata, 'prescribed_quantity', $item->quantity);
            $filled = (int) data_get($item->metadata, 'filled_quantity', 0);
            $remaining = max(0, $prescribed - $filled);
            if ($quantity < 1 || $quantity > $remaining) throw ValidationException::withMessages(['quantity' => "Solo quedan {$remaining} piezas por surtir."]);
            if (! $item->pharmacy_product_id) throw ValidationException::withMessages(['quantity' => 'El medicamento no estÃ¡ vinculado al catÃ¡logo de farmacia.']);

            $lots = InventoryItem::query()->where('pharmacy_product_id', $item->pharmacy_product_id)
                ->when($unitId, fn ($query) => $query->where(fn ($scope) => $scope->where('medical_unit_id', $unitId)->orWhereNull('medical_unit_id')))
                ->where('status', 'available')->where('quantity', '>', 0)->orderBy('expires_at')->lockForUpdate()->get();
            if ($lots->sum('quantity') < $quantity) throw ValidationException::withMessages(['quantity' => 'Inventario insuficiente para surtir esta cantidad.']);
            $pending = $quantity; $movements = [];
            foreach ($lots as $lot) {
                if ($pending === 0) break;
                $taken = min($pending, $lot->quantity);
                $stockBefore = (int) $lot->quantity;
                $lot->decrement('quantity', $taken);
                $metadata = $lot->metadata ?? [];
                $metadata['movements'][] = ['type' => 'prescription_dispense', 'quantity' => -$taken, 'patient_order_item_id' => $item->id, 'at' => now()->toISOString()];
                $lot->update(['metadata' => $metadata, 'status' => $lot->fresh()->quantity > 0 ? 'available' : 'depleted']);
                InventoryMovement::query()->create([
                    'inventory_item_id' => $lot->id,
                    'medical_unit_id' => $unitId ?: $lot->medical_unit_id,
                    'type' => 'exit',
                    'quantity' => $taken,
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockBefore - $taken,
                    'source' => 'Receta surtida',
                    'notes' => 'Surtimiento de '.$item->product_name,
                    'metadata' => ['patient_order_item_id' => $item->id],
                ]);
                $movements[] = ['inventory_item_id' => $lot->id, 'quantity' => $taken];
                $pending -= $taken;
            }
            $metadata = $item->metadata ?? [];
            $metadata['filled_quantity'] = $filled + $quantity;
            $metadata['dispensations'][] = ['quantity' => $quantity, 'at' => now()->toISOString(), 'movements' => $movements];
            $item->update(['metadata' => $metadata]);

            $order = $item->order()->with('items')->firstOrFail();
            $complete = $order->items->every(fn ($row) => (int) data_get($row->metadata, 'filled_quantity', 0) >= (int) data_get($row->metadata, 'prescribed_quantity', $row->quantity));
            $order->update(['status' => $complete ? 'delivered' : 'preparing']);
            $prescription = Prescription::query()->find(data_get($order->metadata, 'prescription_id'));
            if ($prescription) {
                $this->syncPrescriptionTrace($prescription, $order, $complete ? 'filled' : 'partial', [
                    $complete ? 'pharmacy_filled_at' : 'pharmacy_last_dispensed_at' => now()->toDateTimeString(),
                ]);
                $prescription->update(['status' => $complete ? 'filled' : 'pending']);
            }

            return [$order->fresh('items'), $prescription];
        });
    }

    private function syncPrescriptionTrace(Prescription $prescription, PatientOrder $order, string $status, array $extra = []): void
    {
        $metadata = $prescription->metadata ?? [];
        $metadata = array_merge($metadata, [
            'pharmacy_order_id' => $order->id,
            'pharmacy_order_number' => $order->order_number,
            'pharmacy_order_status' => $order->status,
            'pharmacy_status' => $status,
        ], $extra);

        $prescription->update(['metadata' => $metadata]);
    }

    private function productFor(string $name, ?string $cnis): ?PharmacyProduct
    {
        return PharmacyProduct::query()->when($cnis, fn ($query) => $query->where('cnis', $cnis))->first()
            ?? PharmacyProduct::query()->where(fn ($query) => $query->where('name', $name)->orWhere('generic_name', $name))->first();
    }
}


