<?php

namespace App\Http\Controllers\Api\Insurance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Insurance\StoreAuthorizationRequest;
use App\Http\Requests\Insurance\StoreDocumentRequest;
use App\Http\Requests\Insurance\StoreHospitalizationDailyNoteRequest;
use App\Http\Requests\Insurance\StoreHospitalizationRequest;
use App\Http\Requests\Insurance\StoreInsurancePatientRequest;
use App\Http\Requests\Insurance\StoreInvoiceRequest;
use App\Http\Requests\Insurance\StoreMedicationDeliveryRequest;
use App\Http\Requests\Insurance\StorePatientDiagnosisRequest;
use App\Http\Requests\Insurance\StoreTreatmentRequest;
use App\Models\Authorization;
use App\Models\Document;
use App\Models\Hospital;
use App\Models\Hospitalization;
use App\Models\HospitalizationDailyNote;
use App\Models\InsurancePolicy;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\MedicationDelivery;
use App\Models\Patient;
use App\Models\PatientDiagnosis;
use App\Models\Treatment;
use App\Models\User;
use App\Services\Insurance\InsuranceAuditService;
use App\Services\Insurance\InsuranceDashboardService;
use App\Services\Insurance\InsurancePermissionService;
use App\Services\Insurance\InsuranceReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class InsuranceApiController extends Controller
{
    public function dashboard(
        Request $request,
        InsuranceDashboardService $dashboard,
        InsurancePermissionService $permissions,
    ): JsonResponse {
        $permissions->assert($request->user(), 'view');

        return response()->json([
            'metrics' => $dashboard->metrics(),
            'alerts' => $dashboard->alerts(),
            'patients_by_diagnosis' => $dashboard->patientsByDiagnosis(),
        ]);
    }

    public function reports(Request $request, InsuranceReportService $reports, InsurancePermissionService $permissions): JsonResponse
    {
        $permissions->assert($request->user(), 'view');

        return response()->json($reports->build($request));
    }

    public function patients(Request $request, InsurancePermissionService $permissions): JsonResponse
    {
        $permissions->assert($request->user(), 'view');

        return response()->json(Patient::query()
            ->with(['user', 'insurancePolicies', 'primaryDoctor'])
            ->when($request->filled('search'), fn ($query) => $query->where('full_name', 'like', '%'.$request->input('search').'%'))
            ->latest()
            ->paginate(25));
    }

    public function patient(Request $request, Patient $patient, InsurancePermissionService $permissions): JsonResponse
    {
        $permissions->assert($request->user(), 'view');

        return response()->json($patient->load([
            'user',
            'insurancePolicies',
            'diagnoses',
            'treatments',
            'medicationDeliveries',
            'hospitalizations.invoices',
            'authorizations',
            'documents',
        ]));
    }

    public function storePatient(StoreInsurancePatientRequest $request, InsuranceAuditService $audit): JsonResponse
    {
        $data = $request->validated();
        $user = filled($data['email'] ?? null)
            ? User::query()->firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['full_name'],
                    'username' => $this->uniqueUsername($data['email']),
                    'role' => 'patient',
                    'module' => 'patient',
                    'status' => 'active',
                    'email_verified_at' => now(),
                    'passwordless_review' => true,
                    'metadata' => ['created_from' => 'insurance_api'],
                ],
            )
            : User::query()->create([
                'name' => $data['full_name'],
                'username' => 'paciente.'.Str::lower(Str::random(8)),
                'role' => 'patient',
                'module' => 'patient',
                'status' => 'active',
                'passwordless_review' => true,
                'metadata' => ['created_from' => 'insurance_api'],
            ]);

        $patient = Patient::query()->create([
            'user_id' => $user->id,
            'platform_number' => 'ASEG-'.now()->format('ymd').'-'.Str::upper(Str::random(4)),
            'full_name' => $data['full_name'],
            'birth_date' => $data['birth_date'] ?? null,
            'sex' => $data['sex'] ?? null,
            'curp' => $data['curp'] ?? null,
            'rfc' => $data['rfc'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? null,
            'status' => $data['status'],
            'primary_doctor_id' => $data['primary_doctor_id'] ?? null,
            'risk_level' => $data['risk_level'],
            'enrolled_at' => $data['enrolled_at'] ?? now()->toDateString(),
            'general_observations' => $data['general_observations'] ?? null,
            'created_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
        ]);

        $policy = InsurancePolicy::query()->create([
            'patient_id' => $patient->id,
            'policy_number' => $data['policy_number'],
            'insurer_name' => $data['insurer_name'],
            'plan_name' => $data['plan_name'] ?? null,
            'employer_name' => $data['employer_name'] ?? null,
            'status' => 'active',
            'created_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
        ]);

        $audit->record($request, 'insurance.api.patient.created', $patient, ['policy_id' => $policy->id]);

        return response()->json($patient->load(['user', 'insurancePolicies']), 201);
    }

    public function storeDiagnosis(StorePatientDiagnosisRequest $request, InsuranceAuditService $audit): JsonResponse
    {
        $diagnosis = PatientDiagnosis::query()->create($request->validated() + [
            'created_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
        ]);

        $audit->record($request, 'insurance.api.diagnosis.created', $diagnosis);

        return response()->json($diagnosis, 201);
    }

    public function storeTreatment(StoreTreatmentRequest $request, InsuranceAuditService $audit): JsonResponse
    {
        $data = $request->validated();
        $treatment = Treatment::query()->create($data + [
            'requires_authorization' => (bool) ($data['requires_authorization'] ?? false),
            'created_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
        ]);

        $audit->record($request, 'insurance.api.treatment.created', $treatment);

        return response()->json($treatment, 201);
    }

    public function storeDelivery(StoreMedicationDeliveryRequest $request, InsuranceAuditService $audit): JsonResponse
    {
        $delivery = MedicationDelivery::query()->create($request->validated() + [
            'created_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
        ]);

        $audit->record($request, 'insurance.api.delivery.created', $delivery);

        return response()->json($delivery, 201);
    }

    public function updateDelivery(Request $request, MedicationDelivery $delivery, InsurancePermissionService $permissions, InsuranceAuditService $audit): JsonResponse
    {
        $permissions->assert($request->user(), 'manage_deliveries');

        $data = $request->validate([
            'status' => ['required', Rule::in(['pending', 'in_route', 'delivered', 'not_delivered', 'rescheduled', 'cancelled'])],
            'actual_delivery_date' => ['nullable', 'date'],
            'observations' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($data['status'] === 'delivered' && blank($data['actual_delivery_date'] ?? null)) {
            $data['actual_delivery_date'] = now()->toDateString();
        }

        $delivery->update($data + ['updated_by' => $request->user()?->id]);
        $audit->record($request, 'insurance.api.delivery.updated', $delivery);

        return response()->json($delivery);
    }

    public function storeHospitalization(StoreHospitalizationRequest $request, InsuranceAuditService $audit): JsonResponse
    {
        $data = $request->validated();
        $data['stay_days'] = $this->stayDays($data['admitted_at'] ?? null, $data['discharged_at'] ?? null);

        if (filled($data['hospital_id'] ?? null) && blank($data['hospital_name'] ?? null)) {
            $data['hospital_name'] = Hospital::query()->find($data['hospital_id'])?->name;
        }

        $hospitalization = Hospitalization::query()->create($data + [
            'created_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
        ]);

        $audit->record($request, 'insurance.api.hospitalization.created', $hospitalization);

        return response()->json($hospitalization, 201);
    }

    public function storeDailyNote(StoreHospitalizationDailyNoteRequest $request, InsuranceAuditService $audit): JsonResponse
    {
        $note = HospitalizationDailyNote::query()->create($request->validated() + [
            'captured_by' => $request->user()?->id,
            'created_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
        ]);

        $audit->record($request, 'insurance.api.hospitalization_note.created', $note);

        return response()->json($note, 201);
    }

    public function storeInvoice(StoreInvoiceRequest $request, InsuranceAuditService $audit): JsonResponse
    {
        $data = $request->validated();
        $invoiceData = $data;
        unset($invoiceData['concept_type']);

        $invoice = Invoice::query()->create($invoiceData + [
            'vat' => $data['vat'] ?? 0,
            'withholdings' => $data['withholdings'] ?? 0,
            'created_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
        ]);

        InvoiceItem::query()->create([
            'invoice_id' => $invoice->id,
            'concept_type' => $data['concept_type'] ?? 'other',
            'description' => $data['concept'] ?? null,
            'quantity' => 1,
            'unit_price' => $invoice->subtotal,
            'subtotal' => $invoice->subtotal,
            'total' => $invoice->total,
            'created_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
        ]);

        $audit->record($request, 'insurance.api.invoice.created', $invoice);

        return response()->json($invoice->load('items'), 201);
    }

    public function storeAuthorization(StoreAuthorizationRequest $request, InsuranceAuditService $audit): JsonResponse
    {
        $authorization = Authorization::query()->create($request->validated() + [
            'created_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
        ]);

        $audit->record($request, 'insurance.api.authorization.created', $authorization);

        return response()->json($authorization, 201);
    }

    public function storeDocument(StoreDocumentRequest $request, InsuranceAuditService $audit): JsonResponse
    {
        $data = $request->validated();
        $file = $request->file('document_file');

        if ($file) {
            $data['file_path'] = $file->store('insurance-documents');
            $data['file_mime'] = $file->getClientMimeType();
            $data['file_size'] = $file->getSize();
        }

        unset($data['document_file']);

        $document = Document::query()->create($data + [
            'uploaded_by' => $request->user()?->id,
            'loaded_at' => now(),
            'created_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
        ]);

        $audit->record($request, 'insurance.api.document.created', $document);

        return response()->json($document, 201);
    }

    private function stayDays(?string $admittedAt, ?string $dischargedAt): int
    {
        if (! $admittedAt) {
            return 0;
        }

        $start = Carbon::parse($admittedAt)->startOfDay();
        $end = $dischargedAt ? Carbon::parse($dischargedAt)->startOfDay() : now()->startOfDay();

        return (int) max(1, $start->diffInDays($end) + 1);
    }

    private function uniqueUsername(string $email): string
    {
        $base = Str::of($email)->before('@')->slug('.')->lower()->toString() ?: 'paciente';
        $username = $base;
        $suffix = 1;

        while (User::query()->where('username', $username)->exists()) {
            $username = $base.'.'.$suffix;
            $suffix++;
        }

        return $username;
    }
}
