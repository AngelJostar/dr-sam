<?php

namespace App\Support;

use App\Models\MedicalUnit;
use Illuminate\Support\Collection;

final class UnitAreaValidations
{
    public static function rules(MedicalUnit $unit): Collection
    {
        return collect(data_get($unit->metadata, 'area_validation_rules', []))->values();
    }

    public static function areaNames(array $keys): string
    {
        $areas = UnitAreaCatalog::navigation();

        return collect($keys)->map(fn ($key) => $areas[$key]['label'] ?? $key)->implode(', ');
    }
}
