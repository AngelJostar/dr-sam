<?php

namespace App\Http\Controllers\Pharmacy;

use App\Http\Controllers\Controller;
use App\Models\{MedicationCatalogItem, PharmacyProduct, Provider};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DistributorCatalogController extends Controller
{
    private function provider(Request $request): Provider
    {
        abort_unless($request->user()?->role === 'provider' && $request->user()->status === 'active', 403);
        return Provider::where('user_id', $request->user()->id)->whereIn('provider_type', ['medicines', 'medication', 'distributor'])->where('status', 'active')->firstOrFail();
    }

    public function index(Request $request)
    {
        $provider = $this->provider($request);
        $filters = $request->validate(['q' => 'nullable|string|max:160', 'page' => 'nullable|integer|min:1', 'selection' => ['nullable', Rule::in(['all', 'selected', 'unselected'])]]);
        $query = MedicationCatalogItem::publishedInPharmacy()->with(['pharmacyOffers' => fn ($offers) => $offers->where('provider_id', $provider->id)]);
        if (filled($filters['q'] ?? null)) {
            $query->where(fn ($q) => $q->where('name', 'like', '%'.$filters['q'].'%')->orWhere('sku', 'like', '%'.$filters['q'].'%')->orWhere('description', 'like', '%'.$filters['q'].'%')->orWhere('therapeutic_group', 'like', '%'.$filters['q'].'%'));
        }
        $selected = fn ($q) => $q->where('provider_id', $provider->id)->where('status', 'active');
        if (($filters['selection'] ?? '') === 'selected') $query->whereHas('pharmacyOffers', $selected);
        if (($filters['selection'] ?? '') === 'unselected') $query->whereDoesntHave('pharmacyOffers', $selected);
        $columnFilters = app(\App\Services\DistributorTableFilters::class)->universalCatalog($request, $query->orderBy('name')->orderBy('id')->get());
        $rows = $columnFilters['rows'];
        $page = $request->integer('page', 1);
        $records = new \Illuminate\Pagination\LengthAwarePaginator($rows->forPage($page, 50)->values(), $rows->count(), 50, $page, ['path' => $request->url(), 'query' => $request->query()]);
        return view('pharmacy-store.distributor.universal-catalog', ['provider' => $provider, 'section' => 'pharmacy-catalog', 'records' => $records, 'columnFilters' => $columnFilters]);
    }

    public function offer(Request $request, MedicationCatalogItem $medication)
    {
        $provider = $this->provider($request);
        $data = $request->validateWithBag('catalogOffer', [
            'price' => 'required|numeric|min:0.01|max:999999.99|decimal:0,2',
            'stock' => 'required|integer|min:0|max:1000000',
            'laboratory' => 'nullable|string|max:200',
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);
        DB::transaction(function () use ($provider, $medication, $data) {
            $item = MedicationCatalogItem::publishedInPharmacy()->lockForUpdate()->findOrFail($medication->id);
            if ($item->sku && PharmacyProduct::where('provider_id', $provider->id)->where('sku', $item->sku)->where(fn ($q) => $q->whereNull('medication_catalog_item_id')->orWhere('medication_catalog_item_id', '!=', $item->id))->exists()) {
                throw \Illuminate\Validation\ValidationException::withMessages(['price' => 'Ya tienes otro producto con este SKU. Corrige su identificador antes de seleccionar este medicamento.'])->errorBag('catalogOffer');
            }
            PharmacyProduct::updateOrCreate(['provider_id' => $provider->id, 'medication_catalog_item_id' => $item->id], $data + [
                'name' => $item->name, 'sku' => $item->sku, 'cnis' => $item->cnis,
                'generic_name' => $item->generic_name, 'description' => $item->description,
                'presentation' => $item->presentation, 'category' => $item->pharmacyCategory(),
                'commercial_name' => data_get($item->metadata, 'distinctive_name'),
                'barcode' => $item->barcode, 'requires_prescription' => $item->requires_prescription,
                'controlled' => $item->controlled, 'cold_chain' => $item->cold_chain,
            ]);
        }, 3);
        return back()->with('status', 'Oferta guardada en tu catálogo. Su publicación respeta el estatus del producto y la autorización de tu distribuidor.');
    }
}
