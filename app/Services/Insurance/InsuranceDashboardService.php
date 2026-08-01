<?php

namespace App\Services\Insurance;

use App\Models\Authorization;
use App\Models\Document;
use App\Models\Hospitalization;
use App\Models\Invoice;
use App\Models\MedicationDelivery;
use App\Models\Patient;
use App\Models\Treatment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class InsuranceDashboardService
{
    public function metrics(): array
    {
        return [
            'Pacientes activos' => Patient::query()->where('status', 'active')->count(),
            'Alto riesgo' => Patient::query()->whereIn('risk_level', ['high', 'critical'])->count(),
            'Entregas pendientes' => MedicationDelivery::query()->whereIn('status', ['pending', 'in_route', 'rescheduled'])->count(),
            'Entregas atrasadas' => $this->lateDeliveries()->count(),
            'Hospitalizaciones activas' => Hospitalization::query()->where('status', 'active')->count(),
            'Estancias prolongadas' => $this->prolongedHospitalizations()->count(),
            'Facturas en revision' => Invoice::query()->whereIn('status', ['received', 'in_review'])->count(),
            'Facturas rechazadas' => Invoice::query()->where('status', 'rejected')->count(),
            'Monto facturado' => (float) Invoice::query()->sum('total'),
            'Pendiente de pago' => (float) Invoice::query()->whereNotIn('status', ['paid'])->sum('total'),
            'Autorizaciones pendientes' => Authorization::query()->whereIn('status', ['requested', 'in_review'])->count(),
            'Alertas criticas' => $this->alerts()->where('severity', 'critical')->count(),
        ];
    }

    public function patientsByDiagnosis(): Collection
    {
        return Patient::query()
            ->join('patient_diagnoses', 'patients.id', '=', 'patient_diagnoses.patient_id')
            ->selectRaw('patient_diagnoses.condition_name as label, count(distinct patients.id) as total')
            ->groupBy('patient_diagnoses.condition_name')
            ->orderByDesc('total')
            ->limit(8)
            ->get();
    }

    public function alerts(): Collection
    {
        $today = Carbon::today();
        $soon = $today->copy()->addDays(7);
        $alerts = collect();

        $this->lateDeliveries()->each(function (MedicationDelivery $delivery) use ($alerts): void {
            $alerts->push([
                'severity' => 'critical',
                'type' => 'Entrega atrasada',
                'message' => $delivery->patient?->full_name.' tiene entrega vencida para '.$delivery->scheduled_delivery_date?->format('d/m/Y'),
                'url' => route('insurance.deliveries.index'),
            ]);
        });

        MedicationDelivery::query()
            ->with('patient')
            ->where('status', 'pending')
            ->whereBetween('scheduled_delivery_date', [$today, $soon])
            ->orderBy('scheduled_delivery_date')
            ->limit(8)
            ->get()
            ->each(function (MedicationDelivery $delivery) use ($alerts): void {
                $alerts->push([
                    'severity' => 'warning',
                    'type' => 'Entrega proxima',
                    'message' => $delivery->patient?->full_name.' requiere entrega el '.$delivery->scheduled_delivery_date?->format('d/m/Y'),
                    'url' => route('insurance.deliveries.index'),
                ]);
            });

        Treatment::query()
            ->with('patient')
            ->where('status', 'active')
            ->whereNotNull('ends_at')
            ->where('ends_at', '<', $today)
            ->limit(8)
            ->get()
            ->each(function (Treatment $treatment) use ($alerts): void {
                $alerts->push([
                    'severity' => 'warning',
                    'type' => 'Tratamiento vencido',
                    'message' => $treatment->patient?->full_name.' tiene tratamiento vencido: '.$treatment->medication_name,
                    'url' => route('insurance.patients.show', $treatment->patient_id),
                ]);
            });

        $this->prolongedHospitalizations()
            ->each(function (Hospitalization $hospitalization) use ($alerts): void {
                $alerts->push([
                    'severity' => 'critical',
                    'type' => 'Hospitalizacion prolongada',
                    'message' => $hospitalization->patient?->full_name.' suma '.$hospitalization->stay_days.' dias de estancia',
                    'url' => route('insurance.hospitalizations.show', $hospitalization),
                ]);
            });

        Invoice::query()
            ->whereNull('xml_path')
            ->whereIn('status', ['received', 'in_review'])
            ->limit(8)
            ->get()
            ->each(function (Invoice $invoice) use ($alerts): void {
                $alerts->push([
                    'severity' => 'warning',
                    'type' => 'Factura sin XML',
                    'message' => 'Factura '.$invoice->invoice_number.' no tiene XML registrado',
                    'url' => route('insurance.invoices.index'),
                ]);
            });

        Authorization::query()
            ->with('patient')
            ->whereIn('status', ['authorized', 'in_review'])
            ->whereNotNull('valid_until')
            ->whereBetween('valid_until', [$today, $soon])
            ->limit(8)
            ->get()
            ->each(function (Authorization $authorization) use ($alerts): void {
                $alerts->push([
                    'severity' => 'warning',
                    'type' => 'Autorizacion por vencer',
                    'message' => $authorization->patient?->full_name.' vence el '.$authorization->valid_until?->format('d/m/Y'),
                    'url' => route('insurance.authorizations.index'),
                ]);
            });

        Patient::query()
            ->whereIn('risk_level', ['high', 'critical'])
            ->whereDoesntHave('hospitalizations', fn ($query) => $query->where('created_at', '>=', now()->subDays(30)))
            ->limit(8)
            ->get()
            ->each(function (Patient $patient) use ($alerts): void {
                $alerts->push([
                    'severity' => $patient->risk_level === 'critical' ? 'critical' : 'warning',
                    'type' => 'Alto riesgo sin seguimiento',
                    'message' => $patient->full_name.' requiere seguimiento reciente',
                    'url' => route('insurance.patients.show', $patient),
                ]);
            });

        Document::query()
            ->with('patient')
            ->where('status', 'current')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', $today)
            ->limit(8)
            ->get()
            ->each(function (Document $document) use ($alerts): void {
                $alerts->push([
                    'severity' => 'warning',
                    'type' => 'Documento vencido',
                    'message' => $document->name.' esta vencido',
                    'url' => route('insurance.documents.index'),
                ]);
            });

        return $alerts->values();
    }

    private function lateDeliveries(): Collection
    {
        return MedicationDelivery::query()
            ->with('patient')
            ->whereIn('status', ['pending', 'in_route', 'rescheduled'])
            ->where('scheduled_delivery_date', '<', Carbon::today())
            ->orderBy('scheduled_delivery_date')
            ->get();
    }

    private function prolongedHospitalizations(): Collection
    {
        return Hospitalization::query()
            ->with('patient')
            ->where('status', 'active')
            ->where('stay_days', '>=', 5)
            ->orderByDesc('stay_days')
            ->get();
    }
}
