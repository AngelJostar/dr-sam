<?php

namespace App\Console\Commands;

use App\Models\MedicalUnit;
use App\Services\Integrations\Cbta\CbtaCatalogClient;
use Illuminate\Console\Command;
use Throwable;

class MapCbtaMedicalUnit extends Command
{
    protected $signature = 'cbta:map-unit
        {unit : ID, codigo o CLUES de la unidad en Dr. Sam}
        {cbtaCode : Codigo externo de la unidad en CBTA}';

    protected $description = 'Vincula una unidad de Dr. Sam con su unidad operativa en CBTA';

    public function handle(CbtaCatalogClient $client): int
    {
        $identifier = (string) $this->argument('unit');
        $cbtaCode = strtoupper(trim((string) $this->argument('cbtaCode')));

        $unit = MedicalUnit::query()
            ->whereKey($identifier)
            ->orWhere('code', $identifier)
            ->orWhere('clues', $identifier)
            ->first();

        if (! $unit) {
            $this->error('No se encontro la unidad indicada en Dr. Sam.');

            return self::FAILURE;
        }

        try {
            $npt = $client->nptCatalog($cbtaCode);
            $oncology = $client->oncologyCatalog($cbtaCode);
        } catch (Throwable $exception) {
            $this->error('CBTA no reconoce o no permite consultar esa unidad: '.$exception->getMessage());

            return self::FAILURE;
        }

        $unit->update(['cbta_external_code' => $cbtaCode]);

        $this->info("Unidad {$unit->name} vinculada con {$cbtaCode}.");
        $this->line('Catalogos disponibles: '.count($npt['items']).' NPT y '.count($oncology['items']).' oncologicos.');

        return self::SUCCESS;
    }
}
