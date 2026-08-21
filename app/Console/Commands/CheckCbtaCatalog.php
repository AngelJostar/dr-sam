<?php

namespace App\Console\Commands;

use App\Services\Integrations\Cbta\CbtaCatalogClient;
use Illuminate\Console\Command;
use Throwable;

class CheckCbtaCatalog extends Command
{
    protected $signature = 'cbta:check-catalog
        {unitCode : Codigo externo de la unidad en CBTA}
        {--type=npt : Catalogo a consultar: npt u oncology}';

    protected $description = 'Verifica la conexion con CBTA sin exponer credenciales ni datos sensibles';

    public function handle(CbtaCatalogClient $client): int
    {
        $type = strtolower((string) $this->option('type'));

        if (! in_array($type, ['npt', 'oncology'], true)) {
            $this->error('El tipo debe ser npt u oncology.');

            return self::INVALID;
        }

        try {
            $catalog = $type === 'npt'
                ? $client->nptCatalog((string) $this->argument('unitCode'))
                : $client->oncologyCatalog((string) $this->argument('unitCode'));
        } catch (Throwable $exception) {
            $this->error('No fue posible consultar CBTA: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->table(
            ['Unidad CBTA', 'Tipo', 'Version', 'Productos'],
            [[
                $this->argument('unitCode'),
                $type,
                $catalog['catalog_version'],
                count($catalog['items']),
            ]]
        );

        return self::SUCCESS;
    }
}
