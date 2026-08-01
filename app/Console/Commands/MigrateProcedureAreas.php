<?php

namespace App\Console\Commands;

use App\Models\MedicalUnit;
use App\Services\Platform\ProcedureAreaMetadataMigrator;
use Illuminate\Console\Command;

class MigrateProcedureAreas extends Command
{
    protected $signature = 'drsam:migrate-procedure-areas
        {--unit= : ID de una unidad específica}
        {--dry-run : Cuenta registros sin modificar la base de datos}';

    protected $description = 'Normaliza las subunidades almacenadas en medical_units.metadata de forma idempotente.';

    public function handle(ProcedureAreaMetadataMigrator $migrator): int
    {
        $query = MedicalUnit::query()->orderBy('id');
        if ($this->option('unit')) {
            $query->whereKey((int) $this->option('unit'));
        }

        $units = 0;
        $areas = 0;
        $query->each(function (MedicalUnit $unit) use ($migrator, &$units, &$areas): void {
            $count = $migrator->migrateUnit($unit, (bool) $this->option('dry-run'));
            if ($count > 0) {
                $units++;
                $areas += $count;
                $this->line("{$unit->id} · {$unit->name}: {$count}");
            }
        });

        $this->info("Unidades procesadas: {$units}; subunidades: {$areas}.");

        return self::SUCCESS;
    }
}
