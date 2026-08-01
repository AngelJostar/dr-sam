<?php

namespace App\Http\Controllers\Insurance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Insurance\StoreTreatmentRequest;
use App\Models\Medication;
use App\Models\Treatment;
use App\Models\TreatmentChangeLog;
use App\Services\Insurance\InsuranceAuditService;
use Illuminate\Http\RedirectResponse;

class TreatmentController extends Controller
{
    public function store(StoreTreatmentRequest $request, InsuranceAuditService $audit): RedirectResponse
    {
        $data = $request->validated();

        if (filled($data['medication_id'] ?? null)) {
            $medication = Medication::query()->find($data['medication_id']);
            $data['medication_name'] = $data['medication_name'] ?: $medication?->name;
            $data['active_substance'] = $data['active_substance'] ?: $medication?->active_substance;
            $data['presentation'] = $data['presentation'] ?: $medication?->presentation;
        }

        $treatment = Treatment::query()->create($data + [
            'requires_authorization' => (bool) ($data['requires_authorization'] ?? false),
            'created_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
        ]);

        TreatmentChangeLog::query()->create([
            'treatment_id' => $treatment->id,
            'changed_by_user_id' => $request->user()?->id,
            'new_payload' => $treatment->only([
                'medication_name',
                'dose',
                'frequency',
                'duration',
                'status',
                'starts_at',
                'ends_at',
            ]),
            'reason' => $data['change_reason'] ?? 'Alta inicial de tratamiento',
            'changed_at' => now(),
        ]);

        $audit->record($request, 'insurance.treatment.created', $treatment, ['patient_id' => $treatment->patient_id]);

        return redirect()->route('insurance.patients.show', $treatment->patient_id)->with('status', 'Tratamiento registrado.');
    }
}
