<?php

namespace App\Support;

use App\Models\MedicalUnit;
use App\Models\ProcedureArea;
use Illuminate\Support\Collection;

final class UnitAreaCatalog
{
    public static function definitions(): array
    {
        return collect(config('unit_areas'))->map(fn (array $area) => $area + ['button' => $area['label']])->all();
    }

    /** Unit's carousel and the operational sidebar share visibility and order. */
    public static function navigation(): array
    {
        return collect(self::definitions())->except('infusion')->all();
    }

    public static function operationUrl(string $type, ?int $unitId): string
    {
        $definition = self::definitions()[$type];
        return route($definition['route'] ?? 'operational.areas.show',
            ($definition['parameters'] ?? ['type' => $type]) + ($unitId ? ['unit' => $unitId] : []));
    }

    /** Native records take precedence over the legacy metadata copy. */
    public static function records(MedicalUnit $unit): Collection
    {
        $legacy = collect(data_get($unit->metadata, 'procedure_areas', []))->keyBy('id');
        $days = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
        $native = $unit->procedureAreas()->with('schedules')->get()->mapWithKeys(function (ProcedureArea $area) use ($days): array {
            $id = $area->external_id ?: 'native-'.$area->id;
            return [$id => [
                'id' => $id, 'database_id' => $area->id, 'type' => $area->type, 'location' => $area->location,
                'floor' => $area->floor, 'unit_number' => $area->unit_number, 'capacity' => $area->simultaneous_capacity,
                'responsible' => $area->responsible_name, 'status' => $area->status,
                'schedule' => $area->schedules->where('active', true)->mapWithKeys(fn ($schedule) => [$days[$schedule->day_of_week] => [
                    'enabled' => true, 'start' => substr($schedule->starts_at, 0, 5), 'end' => substr($schedule->ends_at, 0, 5),
                ]])->all(),
            ]];
        });
        return $legacy->merge($native)->filter(fn ($row) => isset(config('unit_areas')[$row['type'] ?? '']))->values();
    }
}
