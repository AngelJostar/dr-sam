<?php

namespace App\Http\Requests\Insurance;

use App\Services\Insurance\InsurancePermissionService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(InsurancePermissionService::class)->can($this->user(), 'manage_billing');
    }

    public function rules(): array
    {
        return [
            'hospitalization_id' => ['nullable', 'exists:hospitalizations,id'],
            'hospital_id' => ['nullable', 'exists:hospitals,id'],
            'provider_id' => ['nullable', 'exists:providers,id'],
            'provider_name' => ['nullable', 'string', 'max:255'],
            'provider_rfc' => ['nullable', 'string', 'min:12', 'max:13'],
            'invoice_number' => ['required', 'string', 'max:255'],
            'fiscal_uuid' => ['nullable', 'string', 'max:255', 'unique:invoices,fiscal_uuid'],
            'invoice_date' => ['nullable', 'date'],
            'concept' => ['nullable', 'string', 'max:2000'],
            'subtotal' => ['required', 'numeric', 'min:0'],
            'vat' => ['nullable', 'numeric', 'min:0'],
            'withholdings' => ['nullable', 'numeric', 'min:0'],
            'total' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'status' => ['required', Rule::in(['received', 'in_review', 'approved', 'rejected', 'paid', 'partially_paid'])],
            'paid_at' => ['nullable', 'date'],
            'rejection_reason' => ['nullable', 'string', 'max:2000'],
            'concept_type' => ['nullable', Rule::in(['room', 'medical_fees', 'medications', 'supplies', 'laboratory', 'imaging', 'operating_room', 'intensive_care', 'other'])],
        ];
    }
}
