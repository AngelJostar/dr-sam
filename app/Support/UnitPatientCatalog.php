<?php

namespace App\Support;

use App\Models\MedicalUnit;
use App\Models\Patient;
use App\Models\ProviderRequest;
use Illuminate\Database\Eloquent\Builder;

final class UnitPatientCatalog
{
    /** Both workspaces read the same patient records and unit relationships. */
    public static function query(?MedicalUnit $unit): Builder
    {
        if (! $unit) {
            return Patient::query()->whereRaw('1 = 0');
        }

        return Patient::query()->where(function (Builder $query) use ($unit): void {
            $query->where('metadata->medical_unit_id', $unit->id)
                ->orWhereHas('appointments', fn (Builder $appointments) => $appointments->where('medical_unit_id', $unit->id))
                ->orWhereIn('id', ProviderRequest::query()->select('patient_id')->where('medical_unit_id', $unit->id)->whereNotNull('patient_id'))
                ->orWhereHas('prescriptions', function (Builder $prescriptions) use ($unit): void {
                    $prescriptions->where('metadata->medical_unit_id', $unit->id)
                        ->orWhere(function (Builder $legacy) use ($unit): void {
                            $legacy->whereNull('metadata->medical_unit_id')
                                ->whereHas('doctor', fn (Builder $doctors) => $doctors->where('medical_unit_id', $unit->id));
                        });
                });
        })->with(['appointments' => fn ($appointments) => $appointments->where('medical_unit_id', $unit->id)
            ->with(['doctor', 'procedureArea'])->latest('starts_at')]);
    }
}
