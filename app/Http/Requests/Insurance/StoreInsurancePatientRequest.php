<?php

namespace App\Http\Requests\Insurance;

use App\Services\Insurance\InsurancePermissionService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInsurancePatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(InsurancePermissionService::class)->can($this->user(), 'manage_patients');
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'curp' => ['nullable', 'string', 'size:18', 'unique:patients,curp'],
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
            'policy_number' => ['required', 'string', 'max:80', 'unique:insurance_policies,policy_number'],
            'insurer_name' => ['required', 'string', 'max:255'],
            'plan_name' => ['nullable', 'string', 'max:255'],
            'employer_name' => ['nullable', 'string', 'max:255'],
        ];
    }
}
