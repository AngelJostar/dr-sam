<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\MedicationCatalogItem;
use App\Services\MedicationSkuSequence;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MedicationCatalogController extends Controller
{
    public function updateStatus(Request $request, MedicationCatalogItem $medication)
    {
        abort_unless($request->user()?->role === 'superadmin' && $request->user()->status === 'active', 403);
        $data = $request->validateWithBag('medicationStatus', ['status' => ['required', Rule::in(array_keys(MedicationCatalogItem::STATUS_LABELS))]]);
        $medication->update($data);
        return back()->with('status', 'Estatus actualizado: '.$medication->statusLabel().'.');
    }

    public function publication(Request $request, MedicationCatalogItem $medication)
    {
        abort_unless($request->user()?->role === 'superadmin' && $request->user()->status === 'active', 403);
        $data = $request->validate(['digital_pharmacy_enabled' => 'required|boolean']);
        // Explicit desired state makes retries idempotent. Offers remain linked when hidden.
        $medication->update($data);
        $message = $medication->digital_pharmacy_enabled ? 'Publicación habilitada. Los productos activos aparecen en farmacia digital y en el catálogo de distribuidores.' : 'Producto oculto de la farmacia digital. Se conservaron las ofertas de los distribuidores.';
        if ($request->expectsJson()) return response()->json(['enabled' => $medication->digital_pharmacy_enabled, 'message' => $message]);
        return back()->with('status', $message);
    }

    public function store(Request $request, MedicationSkuSequence $sequence)
    {
        abort_unless($request->user()?->role === 'superadmin' && $request->user()->status === 'active', 403);
        $data = $request->validateWithBag('newMedication', [
            'name' => 'required|string|max:255',
            'generic_name' => 'nullable|string|max:255',
            'cnis' => ['nullable', 'string', 'regex:/\A[0-9]{3}\.[0-9]{3}\.[0-9]{4}\.[0-9]{2}\z/'],
            'description' => 'required|string|max:6000',
            'presentation' => 'required|string|max:255',
            'therapeutic_group' => 'nullable|string|max:255',
            'distinctive_name' => 'nullable|string|max:255',
            'catalog' => 'nullable|string|max:255',
            'mobile_units' => ['required', Rule::in(['Si', 'No', 'No especificado'])],
            'basic_units' => ['required', Rule::in(['Si', 'No', 'No especificado'])],
            'cessa' => ['required', Rule::in(['Si', 'No', 'No especificado'])],
            'status' => ['required', Rule::in(array_keys(MedicationCatalogItem::STATUS_LABELS))],
            'barcode' => ['nullable', 'string', 'regex:/\A[0-9]{8,32}\z/'],
            'barcode_type' => ['nullable', 'required_with:barcode', Rule::in(['internal', 'commercial'])],
            'catalog_context' => ['required', Rule::in(['medications', 'pharmacy'])],
        ], [
            'name.required' => 'Escribe el nombre del medicamento.',
            'description.required' => 'Escribe la descripción del producto.',
            'presentation.required' => 'Indica la presentación del producto.',
            'cnis.regex' => 'Usa el formato de clave CNIS 010.000.0000.00.',
            'barcode.regex' => 'El código de barras debe contener entre 8 y 32 dígitos.',
        ]);

        $medication = DB::transaction(function () use ($data, $request, $sequence) {
            return MedicationCatalogItem::create([
                'sku' => $sequence->next(),
                'cnis' => $data['cnis'] ?? null,
                'name' => $data['name'], 'generic_name' => $data['generic_name'] ?? null,
                'description' => $data['description'], 'presentation' => $data['presentation'],
                'therapeutic_group' => $data['therapeutic_group'] ?? null, 'status' => $data['status'],
                'barcode' => $data['barcode'] ?? null,
                'barcode_type' => filled($data['barcode'] ?? null) ? $data['barcode_type'] : null,
                'metadata' => [
                    'distinctive_name' => $data['distinctive_name'] ?? null,
                    'catalog' => $data['catalog'] ?? 'Captura manual',
                    'mobile_units' => $data['mobile_units'], 'basic_units' => $data['basic_units'], 'cessa' => $data['cessa'],
                    'created_from' => 'superadmin_catalog', 'created_by' => $request->user()->id,
                ],
            ]);
        }, 3);

        $route = $data['catalog_context'] === 'pharmacy' ? 'superadmin.digital-pharmacy' : 'superadmin.catalog';
        $parameters = $data['catalog_context'] === 'pharmacy' ? ['section' => 'catalog'] : ['section' => 'medications'];

        return redirect()->route($route, $parameters + ['q' => $medication->sku])
            ->with('status', 'Producto agregado al catálogo con SKU '.$medication->sku.'.');
    }
}
