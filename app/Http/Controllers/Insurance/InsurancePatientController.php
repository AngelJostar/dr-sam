<?php

namespace App\Http\Controllers\Insurance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Insurance\StoreInsurancePatientRequest;
use App\Http\Requests\Insurance\UpdateInsurancePatientRequest;
use App\Models\ChronicCondition;
use App\Models\Doctor;
use App\Models\Hospital;
use App\Models\InsurancePolicy;
use App\Models\Medication;
use App\Models\Patient;
use App\Models\Provider;
use App\Models\User;
use App\Services\Insurance\InsuranceAuditService;
use App\Services\Insurance\InsurancePermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class InsurancePatientController extends Controller
{
    public function index(Request $request, InsurancePermissionService $permissions): View
    {
        $permissions->assert($request->user(), 'view');

        $patients = Patient::query()
            ->with(['user', 'primaryDoctor', 'insurancePolicies' => fn ($query) => $query->latest()])
            ->withCount(['diagnoses', 'treatments', 'medicationDeliveries', 'hospitalizations'])
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search')->toString();
                $query->where(function ($inner) use ($search): void {
                    $inner->where('full_name', 'like', "%{$search}%")
                        ->orWhere('curp', 'like', "%{$search}%")
                        ->orWhere('rfc', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhereHas('insurancePolicies', fn ($policyQuery) => $policyQuery->where('policy_number', 'like', "%{$search}%"));
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->when($request->filled('risk_level'), fn ($query) => $query->where('risk_level', $request->input('risk_level')))
            ->when($request->filled('diagnosis'), fn ($query) => $query->whereHas('diagnoses', fn ($diagnosis) => $diagnosis->where('condition_name', 'like', '%'.$request->input('diagnosis').'%')))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('insurance.patients.index', [
            'patients' => $patients,
            'abilities' => $permissions->abilitiesFor($request->user()),
            'filters' => $request->only(['search', 'status', 'risk_level', 'diagnosis']),
        ]);
    }

    public function create(Request $request, InsurancePermissionService $permissions): View
    {
        $permissions->assert($request->user(), 'manage_patients');

        return view('insurance.patients.form', [
            'patient' => new Patient(['status' => 'active', 'risk_level' => 'medium']),
            'policy' => new InsurancePolicy(['status' => 'active']),
            'doctors' => Doctor::query()->orderBy('full_name')->get(),
            'mode' => 'create',
        ]);
    }

    public function store(StoreInsurancePatientRequest $request, InsuranceAuditService $audit): RedirectResponse
    {
        $data = $request->validated();
        $user = $this->patientUser($data);

        $patient = Patient::query()->create([
            'user_id' => $user?->id,
            'platform_number' => 'ASEG-'.now()->format('ymd').'-'.Str::upper(Str::random(4)),
            'full_name' => $data['full_name'],
            'birth_date' => $data['birth_date'] ?? null,
            'sex' => $data['sex'] ?? null,
            'curp' => $data['curp'] ?? null,
            'rfc' => $data['rfc'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? null,
            'status' => $data['status'],
            'primary_doctor_id' => $data['primary_doctor_id'] ?? null,
            'risk_level' => $data['risk_level'],
            'enrolled_at' => $data['enrolled_at'] ?? now()->toDateString(),
            'general_observations' => $data['general_observations'] ?? null,
            'created_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
            'metadata' => ['source' => 'insurance_module'],
        ]);

        $policy = InsurancePolicy::query()->create([
            'patient_id' => $patient->id,
            'policy_number' => $data['policy_number'],
            'insurer_name' => $data['insurer_name'],
            'plan_name' => $data['plan_name'] ?? null,
            'employer_name' => $data['employer_name'] ?? null,
            'status' => 'active',
            'created_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
        ]);

        $audit->record($request, 'insurance.patient.created', $patient, ['policy_id' => $policy->id]);

        return redirect()->route('insurance.patients.show', $patient)->with('status', 'Paciente creado y ligado a usuario de plataforma.');
    }

    public function show(Request $request, Patient $patient, InsurancePermissionService $permissions): View
    {
        $permissions->assert($request->user(), 'view');

        $patient->load([
            'user',
            'primaryDoctor',
            'insurancePolicies',
            'diagnoses.chronicCondition',
            'diagnoses.doctor',
            'treatments.medication',
            'treatments.prescribingDoctor',
            'medicationDeliveries.treatment',
            'hospitalizations.hospital',
            'hospitalizations.invoices',
            'authorizations',
            'documents',
        ]);

        $timeline = collect()
            ->merge($patient->diagnoses->map(fn ($item) => ['date' => $item->diagnosed_at, 'title' => 'Diagnostico', 'detail' => $item->condition_name]))
            ->merge($patient->treatments->map(fn ($item) => ['date' => $item->starts_at, 'title' => 'Tratamiento', 'detail' => $item->medication_name]))
            ->merge($patient->medicationDeliveries->map(fn ($item) => ['date' => $item->scheduled_delivery_date, 'title' => 'Entrega', 'detail' => $item->status]))
            ->merge($patient->hospitalizations->map(fn ($item) => ['date' => $item->admitted_at, 'title' => 'Hospitalizacion', 'detail' => $item->hospital_name ?? $item->hospital?->name]))
            ->filter(fn ($item) => filled($item['date']))
            ->sortByDesc('date')
            ->values();

        return view('insurance.patients.show', [
            'patient' => $patient,
            'timeline' => $timeline,
            'abilities' => $permissions->abilitiesFor($request->user()),
            'conditions' => ChronicCondition::query()->where('status', 'active')->orderBy('name')->get(),
            'doctors' => Doctor::query()->where('status', 'active')->orderBy('full_name')->get(),
            'medications' => Medication::query()->where('status', 'active')->orderBy('name')->get(),
            'providers' => Provider::query()->where('status', 'active')->orderBy('name')->get(),
            'hospitals' => Hospital::query()->where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function edit(Request $request, Patient $patient, InsurancePermissionService $permissions): View
    {
        $permissions->assert($request->user(), 'manage_patients');

        return view('insurance.patients.form', [
            'patient' => $patient,
            'policy' => $patient->insurancePolicies()->latest()->first() ?? new InsurancePolicy(['status' => 'active']),
            'doctors' => Doctor::query()->orderBy('full_name')->get(),
            'mode' => 'edit',
        ]);
    }

    public function update(UpdateInsurancePatientRequest $request, Patient $patient, InsuranceAuditService $audit): RedirectResponse
    {
        $patient->fill($request->validated());
        $patient->updated_by = $request->user()?->id;
        $patient->save();

        $audit->record($request, 'insurance.patient.updated', $patient);

        return redirect()->route('insurance.patients.show', $patient)->with('status', 'Paciente actualizado.');
    }

    public function destroy(Request $request, Patient $patient, InsurancePermissionService $permissions, InsuranceAuditService $audit): RedirectResponse
    {
        $permissions->assert($request->user(), 'manage_patients');

        $patient->update([
            'status' => 'discharged',
            'updated_by' => $request->user()?->id,
        ]);
        $patient->delete();

        $audit->record($request, 'insurance.patient.deactivated', $patient);

        return redirect()->route('insurance.patients.index')->with('status', 'Paciente desactivado con eliminacion logica.');
    }

    private function patientUser(array $data): ?User
    {
        if (! filled($data['email'] ?? null)) {
            return User::query()->create([
                'name' => $data['full_name'],
                'username' => 'paciente.'.Str::lower(Str::random(8)),
                'role' => 'patient',
                'module' => 'patient',
                'status' => 'active',
                'passwordless_review' => true,
                'metadata' => ['created_from' => 'insurance_module'],
            ]);
        }

        return User::query()->firstOrCreate(
            ['email' => $data['email']],
            [
                'name' => $data['full_name'],
                'username' => $this->uniqueUsername($data),
                'role' => 'patient',
                'module' => 'patient',
                'status' => 'active',
                'email_verified_at' => now(),
                'passwordless_review' => true,
                'metadata' => ['created_from' => 'insurance_module'],
            ],
        );
    }

    private function uniqueUsername(array $data): string
    {
        $base = Str::of($data['email'] ?? $data['policy_number'])->before('@')->slug('.')->lower()->toString();
        $username = $base ?: 'paciente';
        $suffix = 1;

        while (User::query()->where('username', $username)->exists()) {
            $username = $base.'.'.$suffix;
            $suffix++;
        }

        return $username;
    }
}
