<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;

/** Column filters run on already distributor-scoped rows, before grouping or pagination. */
class DistributorTableFilters
{
    public function universalCatalog(Request $request, Collection $rows): array
    {
        return $this->apply($request, $rows, [
            'sku' => fn ($item) => [$item->sku ?: 'Sin SKU'],
            'product' => fn ($item) => [$item->name],
            'category' => fn ($item) => [$item->pharmacyCategory()],
            'price' => fn ($item) => [$item->pharmacyOffers->first() ? '$'.number_format((float)$item->pharmacyOffers->first()->price, 2) : 'Por registrar'],
            'stock' => fn ($item) => [$item->pharmacyOffers->first() ? $item->pharmacyOffers->first()->stock.' ud.' : 'Sin oferta'],
            'selection' => fn ($item) => [$item->pharmacyOffers->first()?->status === 'active' ? 'Seleccionado' : 'No seleccionado'],
        ]);
    }

    public function products(Request $request, Collection $rows, string $section, DistributorWorkspace $workspace, ?Collection $visibleIds = null): array
    {
        $columns = [
            'product' => fn ($product) => [$workspace->productName($product).' · '.($product->presentation ?: $workspace->presentation($product))],
            'price' => fn ($product) => ['$'.number_format((float) $product->price, 2)],
            'stock' => fn ($product) => [$product->stock === 0 ? '0 · Agotado' : $product->stock.' ud.'],
            'status' => fn ($product) => [$product->status === 'active' ? 'Activo' : 'Inactivo'],
        ];
        if ($section === 'inventory') {
            unset($columns['price']);
            $columns['updated'] = fn ($product) => [$product->updated_at?->format('d/m/Y · H:i') ?? '(Sin fecha)'];
        } elseif ($section === 'store') {
            $columns = ['sku' => fn ($product) => [$product->catalogItem?->sku ?: ($product->sku ?: 'Sin SKU')]] + $columns;
            $columns['visibility'] = fn ($product) => [$visibleIds?->contains($product->id) ? 'Visible' : 'No visible'];
        }

        return $this->apply($request, $rows, $columns);
    }

    public function orders(Request $request, Collection $rows): array
    {
        return $this->apply($request, $rows, [
            'reference' => fn ($order) => ['#'.$order['id'].' · '.($order['created_at']?->format('d/m/Y · H:i') ?? '(Sin fecha)')],
            'products' => fn ($order) => collect($order['items'])->pluck('name')->map(fn ($name) => $name ?: 'Producto')->all(),
            'subtotal' => fn ($order) => ['$'.$order['subtotal']],
            'order_status' => fn ($order) => [DistributorWorkspace::ORDER_STATUSES[$order['status']] ?? 'En seguimiento'],
        ]);
    }

    private function apply(Request $request, Collection $rows, array $columns): array
    {
        $request->validate(['columns' => 'nullable|string|json|max:24000']);
        $selected = json_decode($request->input('columns', '{}') ?: '{}', true);
        $selected = Validator::make(['columns' => $selected], [
            'columns' => ['present', 'array:'.implode(',', array_keys($columns))],
            'columns.*' => 'array|max:1000',
            'columns.*.*' => ['string', 'regex:/^[a-f0-9]{16}$/D'],
        ])->validate()['columns'];

        $options = [];
        $rowValues = [];
        foreach ($rows as $index => $row) {
            foreach ($columns as $key => $extract) {
                foreach ($extract($row) as $label) {
                    $value = substr(hash('sha256', $label), 0, 16);
                    $options[$key][$value] = ['value' => $value, 'label' => $label];
                    $rowValues[$index][$key][] = $value;
                }
            }
        }
        $filtered = $rows->filter(function ($row, $index) use ($selected, $rowValues) {
            foreach ($selected as $key => $values) {
                if (!array_intersect($values, $rowValues[$index][$key] ?? [])) {
                    return false;
                }
            }
            return true;
        })->values();
        $config = [];
        foreach ($columns as $key => $extract) {
            $config[$key] = [
                'options' => collect($options[$key] ?? [])->sort(fn ($a, $b) => strnatcasecmp($a['label'], $b['label']))->values()->all(),
                'selected' => $selected[$key] ?? null,
            ];
        }

        return ['rows' => $filtered, 'columns' => $config, 'selected' => $selected];
    }
}
