<?php

namespace App\Http\Requests\Insurance;

use App\Services\Insurance\InsurancePermissionService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePatientDiagnosisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(InsurancePermissionService::class)->can($this->user(), 'manage_treatments');
    }

    public function rules(): array
    {
        return [
            'patient_id' => ['required', 'exists:patients,id'],
            'chronic_condition_id' => ['nullable', 'exists:chronic_conditions,id'],
            'condition_name' => ['required', 'string', 'max:255'],
            'diagnosed_at' => ['nullable', 'date'],
            'cie10' => ['nullable', 'string', 'max:20'],
            'doctor_id' => ['nullable', 'exists:doctors,id'],
            'specialty' => ['nullable', 'string', 'max:255'],
            'indicated_treatment' => ['nullable', 'string', 'max:2000'],
            'follow_up_frequency' => ['nullable', 'string', 'max:255'],
            'required_studies' => ['nullable', 'string', 'max:2000'],
            'administrative_notes' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::in(['controlled', 'in_surveillance', 'decompensated', 'critical'])],
        ];
    }
}
