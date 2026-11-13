<?php

namespace App\Services;

use App\Models\{PharmacyProduct, PharmacyStoreOrder, Provider};
use Illuminate\Support\Collection;

class DistributorWorkspace
{
    public const ORDER_STATUSES = [
        'submitted' => 'Solicitud recibida', 'reviewing' => 'En revisión',
        'ready' => 'Listo para seguimiento', 'cancelled' => 'Cancelado',
    ];

    public function products(Provider $provider): Collection
    {
        return PharmacyProduct::with('catalogItem')->where('provider_id', $provider->id)->orderByDesc('id')->get();
    }

    public function productName(PharmacyProduct $product): string
    {
        // Older catalog entries encode the pack in the name. Keep other names intact.
        return preg_replace('/\s+·\s+Paquete de \d+$/u', '', $product->name);
    }

    public function presentation(PharmacyProduct $product): string
    {
        return preg_match('/·\s+(Paquete de \d+)$/u', $product->name, $matches)
            ? $matches[1] : ($product->presentation ?: 'Presentación única');
    }

    public function groups(Collection $products): Collection
    {
        return $products->groupBy(fn ($product) => json_encode([
            $this->productName($product), $product->laboratory, $product->cnis,
            $product->dose, $product->dosage_form,
        ]))->map(fn ($items) => [
            'name' => $this->productName($items->first()),
            'laboratory' => $items->first()->laboratory,
            'items' => $items->sortByDesc(fn ($product) => (float) $product->price)->values(),
        ])->values();
    }

    public function filter(Collection $products, array $filters): Collection
    {
        $search = mb_strtolower(trim($filters['q'] ?? ''));

        return $products->filter(function ($product) use ($filters, $search) {
            return ($search === '' || str_contains(mb_strtolower($product->name.' '.$product->presentation.' '.$product->laboratory), $search))
                && (($filters['status'] ?? 'all') === 'all' || $product->status === $filters['status'])
                && match ($filters['stock'] ?? 'all') {
                    'available' => $product->stock > 0,
                    'empty' => $product->stock === 0,
                    'low' => $product->stock > 0 && $product->stock <= 5,
                    default => true,
                };
        })->values();
    }

    public function metrics(Collection $products): array
    {
        return [
            'products' => $this->groups($products)->count(), 'presentations' => $products->count(),
            'empty' => $products->where('stock', 0)->count(),
            'low' => $products->filter(fn ($product) => $product->stock > 0 && $product->stock <= 5)->count(),
            'units' => $products->sum('stock'), 'active' => $products->where('status', 'active')->count(),
            'value' => $products->sum(fn ($product) => (int) round((float) $product->price * 100) * $product->stock) / 100,
        ];
    }

    public function orders(Provider $provider): Collection
    {
        $rows = collect();
        // Order snapshots may contain several suppliers. Never expose other lines or patient data.
        foreach (PharmacyStoreOrder::select(['id', 'status', 'created_at', 'items'])->latest()->orderByDesc('id')->cursor() as $order) {
            $items = collect($order->items ?? [])->filter(fn ($item) => is_array($item) && (int) ($item['provider_id'] ?? 0) === (int) $provider->id)
                ->map(fn ($item) => array_intersect_key($item, array_flip(['product_id', 'name', 'quantity', 'unit_price', 'total'])))->values()->all();
            if (!$items) {
                continue;
            }
            $subtotal = collect($items)->sum(fn ($item) => (int) round((float) ($item['unit_price'] ?? 0) * 100) * (int) ($item['quantity'] ?? 0));
            $rows->push(['id' => $order->id, 'status' => $order->status, 'created_at' => $order->created_at, 'items' => $items, 'subtotal' => number_format($subtotal / 100, 2, '.', '')]);
        }

        return $rows;
    }
}
