<?php

namespace App\Http\Requests\Insurance;

use App\Services\Insurance\InsurancePermissionService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTreatmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(InsurancePermissionService::class)->can($this->user(), 'manage_treatments');
    }

    public function rules(): array
    {
        return [
            'patient_id' => ['required', 'exists:patients,id'],
            'patient_diagnosis_id' => ['nullable', 'exists:patient_diagnoses,id'],
            'medication_id' => ['nullable', 'exists:medications,id'],
            'prescription_id' => ['nullable', 'exists:prescriptions,id'],
            'prescribing_doctor_id' => ['nullable', 'exists:doctors,id'],
            'medication_name' => ['required', 'string', 'max:255'],
            'active_substance' => ['nullable', 'string', 'max:255'],
            'presentation' => ['nullable', 'string', 'max:255'],
            'dose' => ['nullable', 'string', 'max:255'],
            'frequency' => ['nullable', 'string', 'max:255'],
            'duration' => ['nullable', 'string', 'max:255'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'requires_authorization' => ['nullable', 'boolean'],
            'status' => ['required', Rule::in(['active', 'suspended', 'changed', 'finished'])],
            'change_reason' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
