<?php

namespace App\Services\Insurance;

use App\Models\Authorization;
use App\Models\Hospitalization;
use App\Models\Invoice;
use App\Models\MedicationDelivery;
use App\Models\Patient;
use App\Models\Treatment;
use Illuminate\Http\Request;

class InsuranceReportService
{
    public function build(Request $request): array
    {
        return [
            'active_patients_by_diagnosis' => Patient::query()
                ->join('patient_diagnoses', 'patients.id', '=', 'patient_diagnoses.patient_id')
                ->where('patients.status', 'active')
                ->selectRaw('patient_diagnoses.condition_name as label, count(distinct patients.id) as total')
                ->groupBy('patient_diagnoses.condition_name')
                ->orderByDesc('total')
                ->get(),
            'delivered_medications' => MedicationDelivery::query()
                ->with(['patient', 'treatment'])
                ->when($request->filled('period_from'), fn ($query) => $query->where('actual_delivery_date', '>=', $request->date('period_from')))
                ->when($request->filled('period_to'), fn ($query) => $query->where('actual_delivery_date', '<=', $request->date('period_to')))
                ->where('status', 'delivered')
                ->latest('actual_delivery_date')
                ->limit(50)
                ->get(),
            'pending_medication_deliveries' => MedicationDelivery::query()
                ->with(['patient', 'treatment'])
                ->whereIn('status', ['pending', 'in_route', 'rescheduled'])
                ->orderBy('scheduled_delivery_date')
                ->limit(50)
                ->get(),
            'active_hospitalizations' => Hospitalization::query()
                ->with(['patient', 'hospital'])
                ->where('status', 'active')
                ->latest('admitted_at')
                ->get(),
            'hospitalizations_by_hospital' => Hospitalization::query()
                ->selectRaw("coalesce(hospital_name, 'Sin hospital') as label, count(*) as total, avg(stay_days) as average_stay")
                ->groupBy('hospital_name')
                ->orderByDesc('total')
                ->get(),
            'billing_by_hospitalization' => Invoice::query()
                ->with('hospitalization.patient')
                ->selectRaw('hospitalization_id, count(*) as invoices_count, sum(total) as total')
                ->groupBy('hospitalization_id')
                ->get(),
            'billing_by_provider' => Invoice::query()
                ->selectRaw("coalesce(provider_name, 'Sin proveedor') as label, count(*) as invoices_count, sum(total) as total")
                ->groupBy('provider_name')
                ->orderByDesc('total')
                ->get(),
            'pending_invoices' => Invoice::query()
                ->with('hospitalization.patient')
                ->whereNotIn('status', ['paid'])
                ->latest('invoice_date')
                ->limit(50)
                ->get(),
            'pending_authorizations' => Authorization::query()
                ->with('patient')
                ->whereIn('status', ['requested', 'in_review'])
                ->latest('requested_at')
                ->get(),
            'treatments_to_renew' => Treatment::query()
                ->with('patient')
                ->where('status', 'active')
                ->whereNotNull('ends_at')
                ->where('ends_at', '<=', now()->addDays(15))
                ->orderBy('ends_at')
                ->get(),
        ];
    }
}
