<?php

namespace App\Http\Controllers\Insurance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Insurance\StoreMedicationDeliveryRequest;
use App\Models\MedicationDelivery;
use App\Models\Patient;
use App\Services\Insurance\InsuranceAuditService;
use App\Services\Insurance\InsurancePermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MedicationDeliveryController extends Controller
{
    public function index(Request $request, InsurancePermissionService $permissions): View
    {
        $permissions->assert($request->user(), 'view');

        $deliveries = MedicationDelivery::query()
            ->with(['patient', 'treatment', 'provider'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->when($request->filled('patient'), fn ($query) => $query->whereHas('patient', fn ($patient) => $patient->where('full_name', 'like', '%'.$request->input('patient').'%')))
            ->orderBy('scheduled_delivery_date')
            ->paginate(20)
            ->withQueryString();

        return view('insurance.deliveries.index', [
            'deliveries' => $deliveries,
            'abilities' => $permissions->abilitiesFor($request->user()),
        ]);
    }

    public function store(StoreMedicationDeliveryRequest $request, InsuranceAuditService $audit): RedirectResponse
    {
        $data = $request->validated();

        $delivery = MedicationDelivery::query()->create($data + [
            'created_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
        ]);

        $audit->record($request, 'insurance.delivery.created', $delivery, ['patient_id' => $delivery->patient_id]);

        return redirect()->route('insurance.patients.show', $delivery->patient_id)->with('status', 'Entrega programada.');
    }

    public function updateStatus(
        Request $request,
        MedicationDelivery $delivery,
        InsurancePermissionService $permissions,
        InsuranceAuditService $audit,
    ): RedirectResponse {
        $permissions->assert($request->user(), 'manage_deliveries');

        $data = $request->validate([
            'status' => ['required', Rule::in(['pending', 'in_route', 'delivered', 'not_delivered', 'rescheduled', 'cancelled'])],
            'actual_delivery_date' => ['nullable', 'date'],
            'patient_acceptance' => ['nullable', 'string', 'max:255'],
            'observations' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($data['status'] === 'delivered' && blank($data['actual_delivery_date'] ?? null)) {
            $data['actual_delivery_date'] = now()->toDateString();
        }

        $delivery->update($data + ['updated_by' => $request->user()?->id]);
        $audit->record($request, 'insurance.delivery.status_updated', $delivery, ['patient_id' => $delivery->patient_id]);

        return back()->with('status', 'Estatus de entrega actualizado.');
    }
}
