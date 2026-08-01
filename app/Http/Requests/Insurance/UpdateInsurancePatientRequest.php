<?php

namespace App\Http\Requests\Insurance;

use App\Models\Patient;
use App\Services\Insurance\InsurancePermissionService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInsurancePatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(InsurancePermissionService::class)->can($this->user(), 'manage_patients');
    }

    public function rules(): array
    {
        $patient = $this->route('patient');
        $patientId = $patient instanceof Patient ? $patient->id : null;

        return [
            'full_name' => ['required', 'string', 'max:255'],
            'curp' => ['nullable', 'string', 'size:18', Rule::unique('patients', 'curp')->ignore($patientId)],
            'rfc' => ['nullable', 'string', 'min:12', 'max:13'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'sex' => ['nullable', Rule::in(['Femenino', 'Masculino', 'No especificado'])],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', Rule::in(['active', 'suspended', 'discharged', 'deceased'])],
            'risk_level' => ['required', Rule::in(['low', 'medium', 'high', 'critical'])],
            'primary_doctor_id' => ['nullable', 'exists:doctors,id'],
            'enrolled_at' => ['nullable', 'date'],
            'general_observations' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
