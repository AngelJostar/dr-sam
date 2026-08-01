<?php

namespace App\Http\Requests\Insurance;

use App\Services\Insurance\InsurancePermissionService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(InsurancePermissionService::class)->can($this->user(), 'manage_documents');
    }

    public function rules(): array
    {
        return [
            'patient_id' => ['nullable', 'exists:patients,id'],
            'treatment_id' => ['nullable', 'exists:treatments,id'],
            'medication_delivery_id' => ['nullable', 'exists:medication_deliveries,id'],
            'hospitalization_id' => ['nullable', 'exists:hospitalizations,id'],
            'authorization_id' => ['nullable', 'exists:authorizations,id'],
            'invoice_id' => ['nullable', 'exists:invoices,id'],
            'name' => ['required', 'string', 'max:255'],
            'document_type' => ['required', Rule::in([
                'official_id',
                'policy',
                'prescription',
                'treatment_authorization',
                'clinical_summary',
                'laboratory',
                'imaging',
                'invoice_pdf',
                'fiscal_xml',
                'medical_letter',
                'informed_consent',
                'delivery_evidence',
                'other',
            ])],
            'document_file' => ['nullable', 'file', 'max:10240'],
            'expires_at' => ['nullable', 'date'],
            'status' => ['required', Rule::in(['current', 'expired', 'replaced', 'rejected'])],
        ];
    }
}
