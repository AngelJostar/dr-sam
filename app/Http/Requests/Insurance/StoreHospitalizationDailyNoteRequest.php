<?php

namespace App\Http\Requests\Insurance;

use App\Services\Insurance\InsurancePermissionService;
use Illuminate\Foundation\Http\FormRequest;

class StoreHospitalizationDailyNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(InsurancePermissionService::class)->can($this->user(), 'manage_hospitalizations');
    }

    public function rules(): array
    {
        return [
            'hospitalization_id' => ['required', 'exists:hospitalizations,id'],
            'note_date' => ['required', 'date'],
            'administrative_evolution' => ['nullable', 'string', 'max:2000'],
            'general_clinical_status' => ['nullable', 'string', 'max:255'],
            'relevant_changes' => ['nullable', 'string', 'max:2000'],
            'additional_requirements' => ['nullable', 'string', 'max:2000'],
            'pending_studies' => ['nullable', 'string', 'max:2000'],
            'pending_authorizations' => ['nullable', 'string', 'max:2000'],
            'prolonged_stay_risk' => ['nullable', 'boolean'],
            'possible_discharge_date' => ['nullable', 'date'],
        ];
    }
}
