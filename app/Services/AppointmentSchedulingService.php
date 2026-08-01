<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\ProcedureArea;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class AppointmentSchedulingService
{
    public function assertAvailable(Doctor $doctor, Carbon $startsAt, Carbon $endsAt, ?ProcedureArea $room = null, ?int $ignoreAppointmentId = null): void
    {
        $this->assertDoctorSchedule($doctor, $startsAt, $endsAt);
        $this->assertNoDoctorOverlap($doctor, $startsAt, $endsAt, $ignoreAppointmentId);

        if ($room) {
            $this->assertRoomSchedule($room, $startsAt, $endsAt);
            $this->assertRoomCapacity($room, $startsAt, $endsAt, $ignoreAppointmentId);
        }
    }

    private function assertDoctorSchedule(Doctor $doctor, Carbon $startsAt, Carbon $endsAt): void
    {
        $published = $doctor->availabilityRules()->where('status', 'published');
        if (! $published->exists()) {
            return;
        }

        $available = (clone $published)
            ->where('weekday', $startsAt->dayOfWeekIso)
            ->whereDate('recurrence_start', '<=', $startsAt->toDateString())
            ->where(fn ($query) => $query->whereNull('recurrence_end')->orWhereDate('recurrence_end', '>=', $startsAt->toDateString()))
            ->get()
            ->contains(fn ($rule) => $this->ruleAllowsMonth($rule->selected_months, $startsAt->month)
                && substr((string) $rule->start_time, 0, 5) <= $startsAt->format('H:i')
                && substr((string) $rule->end_time, 0, 5) >= $endsAt->format('H:i'));

        if (! $available) {
            throw ValidationException::withMessages(['starts_at' => 'El médico no tiene disponibilidad publicada para este horario.']);
        }

        $blocked = $doctor->availabilityExceptions()
            ->whereDate('date_start', '<=', $startsAt->toDateString())
            ->whereDate('date_end', '>=', $startsAt->toDateString())
            ->exists();

        if ($blocked) {
            throw ValidationException::withMessages(['starts_at' => 'El médico tiene una ausencia o bloqueo registrado para esta fecha.']);
        }
    }

    private function assertNoDoctorOverlap(Doctor $doctor, Carbon $startsAt, Carbon $endsAt, ?int $ignoreId): void
    {
        $exists = Appointment::query()->where('doctor_id', $doctor->id)
            ->whereNotIn('status', ['cancelled'])
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt)
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages(['starts_at' => 'El médico ya tiene una consulta en ese horario.']);
        }
    }

    private function assertRoomSchedule(ProcedureArea $room, Carbon $startsAt, Carbon $endsAt): void
    {
        if (! $room->schedules()->exists()) {
            return;
        }

        $available = $room->schedules()->where('active', true)->where('day_of_week', $startsAt->dayOfWeek)->get()
            ->contains(fn ($schedule) => substr((string) $schedule->starts_at, 0, 5) <= $startsAt->format('H:i')
                && substr((string) $schedule->ends_at, 0, 5) >= $endsAt->format('H:i'));

        if (! $available) {
            throw ValidationException::withMessages(['procedure_area_id' => 'El consultorio no está habilitado para este horario.']);
        }
    }

    private function assertRoomCapacity(ProcedureArea $room, Carbon $startsAt, Carbon $endsAt, ?int $ignoreId): void
    {
        $simultaneous = Appointment::query()->where('procedure_area_id', $room->id)
            ->whereNotIn('status', ['cancelled'])
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt)
            ->count();

        if ($simultaneous >= max(1, $room->simultaneous_capacity)) {
            throw ValidationException::withMessages(['procedure_area_id' => 'El consultorio alcanzó su capacidad para este horario.']);
        }
    }

    private function ruleAllowsMonth(?array $selectedMonths, int $month): bool
    {
        $months = $selectedMonths ?: range(1, 12);

        return in_array($month, array_map('intval', $months), true);
    }
}