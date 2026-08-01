<?php

namespace App\Services;

use App\Models\Doctor;
use App\Models\DoctorAvailabilityRule;
use App\Models\DoctorClinic;
use Illuminate\Validation\ValidationException;

class DoctorAvailabilityService
{
    public function createRule(Doctor $doctor, array $data): DoctorAvailabilityRule
    {
        $clinic = DoctorClinic::query()->where('doctor_id', $doctor->id)->findOrFail($data['doctor_clinic_id']);
        // Los campos period_* son controles de la interfaz; su resultado ya fue
        // normalizado en recurrencia y meses antes de llegar a este servicio.
        $ruleData = collect($data)->except(['period_mode', 'period_year', 'period_month', 'period_start', 'period_end'])->all();
        $payload = [
            ...$ruleData,
            'doctor_id' => $doctor->id,
            'doctor_clinic_id' => $clinic->id,
            'selected_months' => $ruleData['selected_months'] ?? range(1, 12),
            'status' => $ruleData['status'] ?? 'published',
            'published_at' => ($ruleData['status'] ?? 'published') === 'published' ? now() : null,
        ];

        $conflict = DoctorAvailabilityRule::query()
            ->where('doctor_id', $doctor->id)
            ->where('weekday', $payload['weekday'])
            ->where('status', '!=', 'blocked')
            ->get()
            ->first(fn (DoctorAvailabilityRule $rule) => $this->overlaps($payload, $rule));

        if ($conflict) {
            throw ValidationException::withMessages(['schedule' => 'No puedes atender en dos consultorios al mismo tiempo.']);
        }

        return DoctorAvailabilityRule::query()->create($payload);
    }

    private function overlaps(array $incoming, DoctorAvailabilityRule $existing): bool
    {
        $incomingEnd = $incoming['recurrence_end'] ?? '2999-12-31';
        $existingEnd = $existing->recurrence_end?->toDateString() ?? '2999-12-31';
        $datesOverlap = $incoming['recurrence_start'] <= $existingEnd
            && $existing->recurrence_start->toDateString() <= $incomingEnd;

        return $datesOverlap
            && $this->monthsOverlap($incoming['selected_months'] ?? null, $existing->selected_months)
            && substr($incoming['start_time'], 0, 5) < substr((string) $existing->end_time, 0, 5)
            && substr($incoming['end_time'], 0, 5) > substr((string) $existing->start_time, 0, 5);
    }

    private function monthsOverlap(?array $incomingMonths, ?array $existingMonths): bool
    {
        $incoming = array_map('intval', $incomingMonths ?: range(1, 12));
        $existing = array_map('intval', $existingMonths ?: range(1, 12));

        return count(array_intersect($incoming, $existing)) > 0;
    }
}
