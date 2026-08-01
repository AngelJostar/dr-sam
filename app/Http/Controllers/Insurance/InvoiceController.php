<?php

namespace App\Http\Controllers\Insurance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Insurance\StoreInvoiceRequest;
use App\Models\Hospital;
use App\Models\Hospitalization;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Provider;
use App\Services\Insurance\InsuranceAuditService;
use App\Services\Insurance\InsurancePermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function index(Request $request, InsurancePermissionService $permissions): View
    {
        $permissions->assert($request->user(), 'view');

        $invoices = Invoice::query()
            ->with(['hospitalization.patient', 'hospital', 'provider'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->when($request->filled('provider'), fn ($query) => $query->where('provider_name', 'like', '%'.$request->input('provider').'%'))
            ->latest('invoice_date')
            ->paginate(20)
            ->withQueryString();

        return view('insurance.invoices.index', [
            'invoices' => $invoices,
            'abilities' => $permissions->abilitiesFor($request->user()),
            'hospitalizations' => Hospitalization::query()->with('patient')->latest('admitted_at')->limit(200)->get(),
            'hospitals' => Hospital::query()->orderBy('name')->get(),
            'providers' => Provider::query()->orderBy('name')->get(),
        ]);
    }

    public function store(StoreInvoiceRequest $request, InsuranceAuditService $audit): RedirectResponse
    {
        $data = $request->validated();
        $invoiceData = $data;
        unset($invoiceData['concept_type']);

        $invoice = Invoice::query()->create($invoiceData + [
            'vat' => $data['vat'] ?? 0,
            'withholdings' => $data['withholdings'] ?? 0,
            'created_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
        ]);

        InvoiceItem::query()->create([
            'invoice_id' => $invoice->id,
            'concept_type' => $data['concept_type'] ?? 'other',
            'description' => $data['concept'] ?? null,
            'quantity' => 1,
            'unit_price' => $invoice->subtotal,
            'subtotal' => $invoice->subtotal,
            'total' => $invoice->total,
            'created_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
        ]);

        $audit->record($request, 'insurance.invoice.created', $invoice, [
            'hospitalization_id' => $invoice->hospitalization_id,
        ]);

        return back()->with('status', 'Factura hospitalaria registrada.');
    }
}
