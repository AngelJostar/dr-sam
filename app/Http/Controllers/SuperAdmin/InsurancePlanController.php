<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\InsuranceCarrier;
use App\Models\InsurancePlan;
use App\Models\InsurancePlanDocument;
use App\Services\Insurance\InsurancePlanCatalog;
use App\Services\Platform\PlatformAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InsurancePlanController extends Controller
{
    public function index(Request $request): View
    {
        $category = $request->string('view')->toString();
        abort_unless(isset(InsurancePlanCatalog::CATEGORIES[$category]), 404);
        return view('superadmin.catalog', [
            'section' => 'insurance-carriers', 'catalog' => ['title' => InsurancePlanCatalog::CATEGORIES[$category]],
            'records' => InsurancePlanCatalog::query($request)->paginate(10)->withQueryString(),
            'insuranceCategory' => $category, 'insuranceCarriers' => InsuranceCarrier::query()->orderBy('name')->get(),
            'selectedInsuranceCarrier' => InsuranceCarrier::query()->find($request->integer('carrier')),
            'insuranceEditingPlan' => InsurancePlan::query()->with('documents')->find($request->integer('edit_plan')),
            'filters' => ['q' => $request->string('q')->toString(), 'status' => $request->string('status')->toString(), 'category' => '', 'module' => ''],
        ]);
    }

    public function store(Request $request, PlatformAuditService $audit): RedirectResponse
    {
        $data = $this->validated($request);
        $plan = DB::transaction(function () use ($data, $request, $audit) {
            $plan = InsurancePlan::query()->create($data);
            $audit->record($request, 'superadmin.insurance_plan.created', $plan, 'superadmin');
            return $plan;
        });
        return redirect()->route('superadmin.catalog', ['section' => 'insurance-carriers', 'view' => $plan->category])->with('status', 'Seguro creado. Puedes vincularlo a Mi seguro desde su fila.');
    }

    public function update(Request $request, InsurancePlan $plan, PlatformAuditService $audit): RedirectResponse
    {
        $data = $this->validated($request);
        DB::transaction(function () use ($data, $plan, $request, $audit) {
            $plan->update($data);
            $audit->record($request, 'superadmin.insurance_plan.updated', $plan, 'superadmin');
        });
        return redirect()->route('superadmin.catalog', ['section' => 'insurance-carriers', 'view' => $plan->category])->with('status', 'Información del seguro actualizada.');
    }

    private function validated(Request $request): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:180'], 'category' => ['required', Rule::in(array_keys(InsurancePlanCatalog::CATEGORIES))],
            'insurance_carrier_id' => ['nullable', 'integer', 'exists:insurance_carriers,id'],
            'monthly_premium' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'insured_amount' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
            'deductible' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'coinsurance' => ['nullable', 'numeric', 'between:0,100'],
            'status' => ['required', Rule::in(['active', 'inactive'])], 'is_demo' => ['required', 'boolean'],
            'details' => ['nullable', 'array:'.implode(',', array_keys(InsurancePlanCatalog::DETAIL_FIELDS)).',addons'],
            'details.addons' => ['nullable', 'array:dental,vision,travel'],
            'details.addons.*' => ['array:name,price'],
            'details.addons.*.name' => ['nullable', 'string', 'max:100'],
            'details.addons.*.price' => ['nullable', 'numeric', 'min:0', 'max:999999'],
        ];
        foreach (InsurancePlanCatalog::DETAIL_FIELDS as $key => $label) $rules['details.'.$key] = ['nullable', 'string', 'max:5000'];
        $data = $request->validate($rules);
        $data['details']['addons'] = collect(data_get($data, 'details.addons', []))->filter(fn ($addon) => filled($addon['name'] ?? null) && isset($addon['price']))->all();
        if (! $data['is_demo'] && empty($data['insurance_carrier_id'])) {
            throw ValidationException::withMessages(['insurance_carrier_id' => 'Asigna una aseguradora a los planes que no son de demostración.']);
        }
        return $data;
    }

    public function link(Request $request, InsurancePlan $plan, PlatformAuditService $audit): RedirectResponse
    {
        $data = $request->validate(['is_linked' => ['required', 'boolean']]);
        if ($data['is_linked'] && ($plan->status !== 'active' || ($plan->carrier && $plan->carrier->status !== 'active'))) {
            throw ValidationException::withMessages(['is_linked' => 'Activa el seguro y su aseguradora antes de vincularlo a Mi seguro.']);
        }
        DB::transaction(function () use ($data, $plan, $request, $audit) {
            $plan->update(['is_linked' => (bool) $data['is_linked']]);
            $audit->record($request, 'superadmin.insurance_plan.linked', $plan, 'superadmin', ['is_linked' => $plan->is_linked]);
        });
        return back()->with('status', $plan->is_linked ? 'El seguro ya está disponible en Mi seguro del paciente.' : 'El seguro dejó de mostrarse en Mi seguro.');
    }

    public function upload(Request $request, InsurancePlan $plan, PlatformAuditService $audit): RedirectResponse
    {
        $request->validate(['documents' => ['required', 'array', 'min:1', 'max:5'], 'documents.*' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240']]);
        $paths = [];
        try {
            DB::transaction(function () use ($request, $plan, $audit, &$paths) {
                foreach ($request->file('documents') as $file) {
                    $path = $file->store('insurance-plans/'.$plan->id, 'local');
                    if (! $path) throw new \RuntimeException('No se pudo guardar el documento.');
                    $paths[] = $path;
                    $document = $plan->documents()->create(['path' => $path, 'name' => $file->getClientOriginalName(), 'mime_type' => $file->getMimeType(), 'size' => $file->getSize()]);
                    $audit->record($request, 'superadmin.insurance_plan.document.created', $document, 'superadmin');
                }
            });
        } catch (\Throwable $exception) {
            foreach ($paths as $path) Storage::disk('local')->delete($path);
            throw $exception;
        }
        return back()->with('status', 'Documentos agregados al seguro.');
    }

    public function deleteDocument(Request $request, InsurancePlanDocument $document, PlatformAuditService $audit): RedirectResponse
    {
        DB::transaction(function () use ($request, $document, $audit) {
            $audit->record($request, 'superadmin.insurance_plan.document.deleted', $document, 'superadmin');
            $document->delete();
        });
        Storage::disk('local')->delete($document->path);
        return back()->with('status', 'Documento eliminado del catálogo.');
    }

    public function export(Request $request): StreamedResponse
    {
        $plans = InsurancePlanCatalog::query($request)->limit(1000)->get();
        return response()->streamDownload(function () use ($plans) {
            $out = fopen('php://output', 'w');
            fputcsv($out, array_merge(['Plan', 'Aseguradora', 'Prima mensual MXN', 'Suma asegurada', 'Deducible', 'Coaseguro %'], array_values(InsurancePlanCatalog::DETAIL_FIELDS), ['Complementos', 'Documentos', 'Mi seguro', 'Estatus', 'Demostración']));
            $safe = fn ($value) => preg_match('/^[=+@\-\t\r]/u', (string) $value) ? "'".$value : $value;
            foreach ($plans as $plan) {
                $row = [$plan->name, $plan->carrier?->name ?? 'Por asignar', $plan->monthly_premium, $plan->insured_amount, $plan->deductible, $plan->coinsurance];
                foreach (InsurancePlanCatalog::DETAIL_FIELDS as $key => $label) $row[] = data_get($plan->details, $key, '');
                $row = array_merge($row, [collect(data_get($plan->details, 'addons', []))->map(fn ($a) => $a['name'].': '.$a['price'].' MXN/mes')->implode('; '), $plan->documents->pluck('name')->implode('; '), $plan->is_linked ? 'Vinculado' : 'No vinculado', $plan->status, $plan->is_demo ? 'Sí' : 'No']);
                fputcsv($out, array_map($safe, $row));
            }
            fclose($out);
        }, 'seguros-'.$request->input('view').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
