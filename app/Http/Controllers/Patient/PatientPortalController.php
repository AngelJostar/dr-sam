<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\InsurancePolicy;
use App\Models\Patient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PatientPortalController extends Controller
{
    public function __invoke(Request $request): View
    {
        $patient = $this->resolvePatient($request);

        $patient->load([
            'user',
            'primaryDoctor',
            'appointments.doctor',
            'appointments.medicalUnit',
            'clinicalRecords.doctor',
            'prescriptions.doctor',
            'prescriptions.items',
            'insurancePolicies',
            'diagnoses.doctor',
            'hospitalizations.hospital',
            'authorizations',
            'documents',
        ]);

        return view('patient.dashboard', [
            'patient' => $patient,
            'doctors' => Doctor::query()
                ->with('medicalUnit')
                ->where('status', 'active')
                ->orderBy('full_name')
                ->get(),
            'nextAppointments' => $patient->appointments
                ->sortBy('starts_at')
                ->take(5),
            'recentRecords' => $patient->clinicalRecords
                ->sortByDesc('recorded_at')
                ->take(6),
            'activePrescriptions' => $patient->prescriptions
                ->sortByDesc('issued_at')
                ->take(6),
            'policy' => $patient->insurancePolicies
                ->sortByDesc('created_at')
                ->first(),
            'clinicalAnalyses' => $patient->documents
                ->filter(fn ($document) => in_array($document->document_type, ['clinical_analysis', 'laboratory', 'analysis'], true))
                ->sortByDesc('loaded_at'),
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $patient = $this->resolvePatient($request);
        $validated = $request->validate([
            'first_name' => ['nullable', 'string', 'max:120'],
            'last_name' => ['nullable', 'string', 'max:160'],
            'birth_date' => ['nullable', 'date'],
            'sex' => ['nullable', 'in:female,male,other,unspecified'],
            'curp' => ['nullable', 'string', 'max:18', 'unique:patients,curp,'.$patient->id],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        $fullName = trim(($validated['first_name'] ?? '').' '.($validated['last_name'] ?? ''));
        $patient->update([
            ...$validated,
            'full_name' => $fullName !== '' ? $fullName : $patient->full_name,
            'profile_completed_at' => now(),
        ]);

        return back()->with('patient_notice', 'Perfil actualizado correctamente.');
    }

    public function saveInsurance(Request $request): RedirectResponse
    {
        $patient = $this->resolvePatient($request);
        $validated = $request->validate([
            'policy_number' => ['required', 'string', 'max:120'],
            'insurer_name' => ['required', 'string', 'max:180'],
            'plan_name' => ['nullable', 'string', 'max:180'],
            'employer_name' => ['nullable', 'string', 'max:180'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'status' => ['required', 'in:active,inactive,expired,pending'],
        ]);

        InsurancePolicy::query()->updateOrCreate(
            ['patient_id' => $patient->id, 'policy_number' => $validated['policy_number']],
            [...$validated, 'patient_id' => $patient->id],
        );

        return back()->with('patient_notice', 'Póliza guardada correctamente.');
    }

    private function resolvePatient(Request $request): Patient
    {
        $user = $request->user();

        if ($user?->patient) {
            return $user->patient;
        }

        abort_unless(in_array($user?->role, ['superadmin', 'admin'], true), 403);

        return Patient::query()
            ->with('user')
            ->latest()
            ->firstOrFail();
    }
}
