<?php

namespace App\Http\Controllers\Insurance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Insurance\StorePatientDiagnosisRequest;
use App\Models\ChronicCondition;
use App\Models\PatientDiagnosis;
use App\Services\Insurance\InsuranceAuditService;
use Illuminate\Http\RedirectResponse;

class PatientDiagnosisController extends Controller
{
    public function store(StorePatientDiagnosisRequest $request, InsuranceAuditService $audit): RedirectResponse
    {
        $data = $request->validated();

        if (filled($data['chronic_condition_id'] ?? null) && ! filled($data['condition_name'] ?? null)) {
            $data['condition_name'] = ChronicCondition::query()->find($data['chronic_condition_id'])?->name;
        }

        $diagnosis = PatientDiagnosis::query()->create($data + [
            'created_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
        ]);

        $audit->record($request, 'insurance.diagnosis.created', $diagnosis, ['patient_id' => $diagnosis->patient_id]);

        return redirect()->route('insurance.patients.show', $diagnosis->patient_id)->with('status', 'Diagnostico agregado al expediente.');
    }
}
