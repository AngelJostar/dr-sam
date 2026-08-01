<?php

namespace App\Http\Controllers\Insurance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Insurance\StoreHospitalizationRequest;
use App\Models\Doctor;
use App\Models\Hospital;
use App\Models\Hospitalization;
use App\Models\Patient;
use App\Services\Insurance\InsuranceAuditService;
use App\Services\Insurance\InsurancePermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class HospitalizationController extends Controller
{
    public function index(Request $request, InsurancePermissionService $permissions): View
    {
        $permissions->assert($request->user(), 'view');

        $hospitalizations = Hospitalization::query()
            ->with(['patient', 'hospital', 'doctor'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->when($request->filled('hospital'), fn ($query) => $query->where('hospital_name', 'like', '%'.$request->input('hospital').'%'))
            ->latest('admitted_at')
            ->paginate(20)
            ->withQueryString();

        return view('insurance.hospitalizations.index', [
            'hospitalizations' => $hospitalizations,
            'abilities' => $permissions->abilitiesFor($request->user()),
            'patients' => Patient::query()->orderBy('full_name')->limit(200)->get(),
            'hospitals' => Hospital::query()->orderBy('name')->get(),
            'doctors' => Doctor::query()->orderBy('full_name')->get(),
        ]);
    }

    public function store(StoreHospitalizationRequest $request, InsuranceAuditService $audit): RedirectResponse
    {
        $data = $request->validated();
        $data['stay_days'] = $this->stayDays($data['admitted_at'] ?? null, $data['discharged_at'] ?? null);

        if (filled($data['hospital_id'] ?? null) && blank($data['hospital_name'] ?? null)) {
            $data['hospital_name'] = Hospital::query()->find($data['hospital_id'])?->name;
        }

        $hospitalization = Hospitalization::query()->create($data + [
            'created_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
        ]);

        $audit->record($request, 'insurance.hospitalization.created', $hospitalization, ['patient_id' => $hospitalization->patient_id]);

        return redirect()->route('insurance.hospitalizations.show', $hospitalization)->with('status', 'Hospitalizacion registrada.');
    }

    public function show(Request $request, Hospitalization $hospitalization, InsurancePermissionService $permissions): View
    {
        $permissions->assert($request->user(), 'view');

        $hospitalization->load([
            'patient',
            'hospital',
            'doctor',
            'dailyNotes.capturedBy',
            'procedures',
            'invoices.items',
            'authorizations',
            'documents',
        ]);

        return view('insurance.hospitalizations.show', [
            'hospitalization' => $hospitalization,
            'abilities' => $permissions->abilitiesFor($request->user()),
        ]);
    }

    private function stayDays(?string $admittedAt, ?string $dischargedAt): int
    {
        if (! $admittedAt) {
            return 0;
        }

        $start = Carbon::parse($admittedAt)->startOfDay();
        $end = $dischargedAt ? Carbon::parse($dischargedAt)->startOfDay() : now()->startOfDay();

        return (int) max(1, $start->diffInDays($end) + 1);
    }
}
