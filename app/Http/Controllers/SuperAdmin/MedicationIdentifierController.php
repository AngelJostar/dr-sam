<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\MedicationCatalogItem;
use App\Services\NumericBarcode;
use App\Services\MedicationSkuSequence;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MedicationIdentifierController extends Controller
{
    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()?->role === 'superadmin' && $request->user()->status === 'active', 403);
    }

    public function update(Request $request, MedicationCatalogItem $medication)
    {
        $this->authorizeAdmin($request);
        DB::transaction(function () use ($request, $medication) {
            app(MedicationSkuSequence::class)->lock();
            $data = $request->validate([
                'sku' => ['sometimes', 'required', 'string', 'regex:/\A1[0-9]{8}\z/', Rule::unique('medication_catalog_items', 'sku')->ignore($medication->id)],
                'barcode' => ['nullable', 'string', 'regex:/\A[0-9]{8,32}\z/'],
                'barcode_type' => ['nullable', 'required_with:barcode', Rule::in(['internal', 'commercial'])],
            ], [
                'sku.unique' => 'Este SKU ya pertenece a otro medicamento.',
                'sku.regex' => 'El SKU interno debe tener nueve dígitos y comenzar con 1.',
                'barcode.regex' => 'El código de barras debe contener entre 8 y 32 dígitos.',
            ]);
            if (array_key_exists('barcode', $data)) {
                $data['barcode_type'] = filled($data['barcode']) ? $data['barcode_type'] : null;
            }
            $medication->update($data);
        }, 3);

        return back()->with('status', 'Identificadores actualizados en el catálogo universal y en Farmacia digital.');
    }

    public function barcode(Request $request, MedicationCatalogItem $medication, NumericBarcode $barcode)
    {
        $this->authorizeAdmin($request);
        abort_unless(preg_match('/\A[0-9]{8,32}\z/', $medication->barcode ?? ''), 404);

        return response($barcode->svg($medication->barcode), 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; style-src 'unsafe-inline'; sandbox",
        ]);
    }
}
