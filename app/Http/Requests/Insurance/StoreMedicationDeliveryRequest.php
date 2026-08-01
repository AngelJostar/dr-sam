<?php

namespace App\Http\Requests\Insurance;

use App\Services\Insurance\InsurancePermissionService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMedicationDeliveryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(InsurancePermissionService::class)->can($this->user(), 'manage_deliveries');
    }

    public function rules(): array
    {
        return [
            'patient_id' => ['required', 'exists:patients,id'],
            'treatment_id' => ['nullable', 'exists:treatments,id'],
            'medication_id' => ['nullable', 'exists:medications,id'],
            'provider_id' => ['nullable', 'exists:providers,id'],
            'quantity_delivered' => ['required', 'integer', 'min:0'],
            'covered_period' => ['nullable', 'string', 'max:255'],
            'scheduled_delivery_date' => ['required', 'date'],
            'actual_delivery_date' => ['nullable', 'date'],
            'delivery_address' => ['nullable', 'string', 'max:1000'],
            'delivery_responsible' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['pending', 'in_route', 'delivered', 'not_delivered', 'rescheduled', 'cancelled'])],
            'patient_acceptance' => ['nullable', 'string', 'max:255'],
            'observations' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
