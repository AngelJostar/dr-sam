<?php

namespace App\Services\Platform;

use App\Models\MedicalUnit;
use App\Models\ProcedureArea;
use Illuminate\Support\Facades\DB;

class ProcedureAreaMetadataMigrator
{
    public function migrateUnit(MedicalUnit $unit, bool $dryRun = false): int
    {
        $areas = collect(data_get($unit->metadata, 'procedure_areas', []));

        if ($dryRun) {
            return $areas->count();
        }

        DB::transaction(function () use ($unit, $areas): void {
            foreach ($areas->values() as $index => $payload) {
                $externalId = (string) (data_get($payload, 'id') ?: "unit-{$unit->id}-area-{$index}");
                $type = $this->normalizeType((string) data_get($payload, 'type', 'consulting'));
                $unitNumber = (string) (data_get($payload, 'unit_number') ?: data_get($payload, 'number') ?: strtoupper(substr($type, 0, 2)).'-'.($index + 1));

                $area = ProcedureArea::query()->updateOrCreate(
                    ['medical_unit_id' => $unit->id, 'external_id' => $externalId],
                    [
                        'type' => $type,
                        'location' => data_get($payload, 'location'),
                        'floor' => data_get($payload, 'floor'),
                        'unit_number' => $unitNumber,
                        'simultaneous_capacity' => max(1, (int) data_get($payload, 'capacity', 1)),
                        'responsible_name' => data_get($payload, 'responsible'),
                        'status' => data_get($payload, 'status', 'active'),
                        'metadata' => ['source_payload' => $payload],
                    ],
                );

                $area->schedules()->delete();
                foreach ($this->schedules(data_get($payload, 'schedule', [])) as $schedule) {
                    $area->schedules()->create($schedule);
                }
            }
        });

        return $areas->count();
    }

    private function normalizeType(string $type): string
    {
        return match (strtolower($type)) {
            'consultation', 'consultorio' => 'consulting',
            'sala de infusion', 'infusion_room' => 'infusion',
            'quirofano', 'operating_room' => 'operating',
            'unidad de cuidados intensivos', 'cuidados intensivos', 'icu' => 'uci',
            'unidad de terapia intermedia', 'terapia intermedia' => 'uti',
            'recuperacion', 'recovery_room' => 'recovery',
            default => $type,
        };
    }

    private function schedules(mixed $raw): array
    {
        $days = [
            'sunday' => 0, 'domingo' => 0, 'monday' => 1, 'lunes' => 1,
            'tuesday' => 2, 'martes' => 2, 'wednesday' => 3, 'miercoles' => 3, 'miércoles' => 3,
            'thursday' => 4, 'jueves' => 4, 'friday' => 5, 'viernes' => 5,
            'saturday' => 6, 'sabado' => 6, 'sábado' => 6,
        ];
        $result = [];

        foreach (is_array($raw) ? $raw : [] as $key => $value) {
            $day = strtolower((string) (is_array($value) ? ($value['day'] ?? $key) : $key));
            $startsAt = is_array($value) ? ($value['start'] ?? $value['starts_at'] ?? null) : null;
            $endsAt = is_array($value) ? ($value['end'] ?? $value['ends_at'] ?? null) : null;
            $enabled = is_array($value) ? ($value['enabled'] ?? true) : false;

            if (! $enabled || ! isset($days[$day]) || ! $startsAt || ! $endsAt) {
                continue;
            }

            $result[] = ['day_of_week' => $days[$day], 'starts_at' => $startsAt, 'ends_at' => $endsAt, 'active' => true];
        }

        return $result;
    }
}
