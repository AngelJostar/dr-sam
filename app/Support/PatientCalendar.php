<?php

namespace App\Support;

use App\Models\Appointment;
use App\Models\Patient;

final class PatientCalendar
{
    /** Presentation data only; bookings continue through AppointmentSchedulingService. */
    public static function forPatient(Patient $patient): array
    {
        $appointments = $patient->appointments
            ->filter(fn (Appointment $appointment) => $appointment->starts_at !== null)
            ->sortBy('starts_at')
            ->map(function (Appointment $appointment): array {
                $location = $appointment->medicalUnit?->name ?: ($appointment->location ?: 'Ubicación por confirmar');

                return [
                    'id' => (string) $appointment->id,
                    'date' => $appointment->starts_at->format('Y-m-d'),
                    'time' => $appointment->starts_at->format('H:i'),
                    'duration' => $appointment->ends_at
                        ? max(1, (int) $appointment->starts_at->diffInMinutes($appointment->ends_at))
                        : 30,
                    'endsAt' => $appointment->ends_at?->format('d/m/Y H:i'),
                    'room' => $location,
                    'specialty' => $appointment->specialty ?: 'Consulta médica',
                    'doctor' => $appointment->doctor?->full_name ?: 'Médico por asignar',
                    'status' => $appointment->status ?: 'scheduled',
                    'reason' => $appointment->reason ?: 'No registrado',
                    'modality' => $appointment->modality ?: 'No registrada',
                ];
            })->values();
        $today = now()->format('Y-m-d');

        return [
            'patient' => $patient->full_name,
            'today' => $today,
            'date' => $appointments->first(fn ($appointment) => $appointment['date'] >= $today
                && ! in_array($appointment['status'], ['cancelled', 'completed', 'finished'], true))['date'] ?? $today,
            'timezone' => config('app.timezone'),
            'appointments' => $appointments->all(),
            'rooms' => $appointments->pluck('room')->unique()->map(fn ($location) => ['id' => $location, 'label' => $location])->values()->all(),
            'specialties' => $appointments->pluck('specialty')->unique()->values()->all(),
        ];
    }
}
