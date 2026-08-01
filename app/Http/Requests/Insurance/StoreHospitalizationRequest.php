<?php

namespace App\Http\Requests\Insurance;

use App\Services\Insurance\InsurancePermissionService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHospitalizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(InsurancePermissionService::class)->can($this->user(), 'manage_hospitalizations');
    }

    public function rules(): array
    {
        return [
            'patient_id' => ['required', 'exists:patients,id'],
            'hospital_id' => ['nullable', 'exists:hospitals,id'],
            'doctor_id' => ['nullable', 'exists:doctors,id'],
            'hospital_name' => ['nullable', 'string', 'max:255'],
            'admitted_at' => ['required', 'date'],
            'discharged_at' => ['nullable', 'date', 'after_or_equal:admitted_at'],
            'reason' => ['nullable', 'string', 'max:2000'],
            'admission_diagnosis' => ['nullable', 'string', 'max:255'],
            'discharge_diagnosis' => ['nullable', 'string', 'max:255'],
            'area' => ['nullable', Rule::in(['urgencias', 'hospitalizacion', 'uci', 'quirofano', 'terapia_intermedia'])],
            'event_type' => ['nullable', Rule::in(['programmed', 'emergency', 'complication', 'relapse'])],
            'authorization_number' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['active', 'discharged', 'cancelled', 'in_review', 'billed'])],
            'procedures_summary' => ['nullable', 'string', 'max:2000'],
            'inpatient_medications' => ['nullable', 'string', 'max:2000'],
            'studies_performed' => ['nullable', 'string', 'max:2000'],
            'administrative_notes' => ['nullable', 'string', 'max:2000'],
            'authorized_amount' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
