<?php

namespace App\Http\Requests\Insurance;

use App\Services\Insurance\InsurancePermissionService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAuthorizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(InsurancePermissionService::class)->can($this->user(), 'manage_authorizations');
    }

    public function rules(): array
    {
        return [
            'patient_id' => ['required', 'exists:patients,id'],
            'treatment_id' => ['nullable', 'exists:treatments,id'],
            'hospitalization_id' => ['nullable', 'exists:hospitalizations,id'],
            'type' => ['required', Rule::in(['medication', 'hospitalization', 'procedure', 'study', 'stay_extension'])],
            'medical_request' => ['nullable', 'string', 'max:2000'],
            'justification' => ['nullable', 'string', 'max:2000'],
            'requested_at' => ['nullable', 'date'],
            'responded_at' => ['nullable', 'date'],
            'status' => ['required', Rule::in(['requested', 'in_review', 'authorized', 'rejected', 'expired'])],
            'authorization_number' => ['nullable', 'string', 'max:255'],
            'authorized_amount' => ['nullable', 'numeric', 'min:0'],
            'valid_until' => ['nullable', 'date'],
            'observations' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
