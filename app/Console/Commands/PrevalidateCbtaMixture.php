<?php

namespace App\Console\Commands;

use App\Services\Integrations\Cbta\CbtaCatalogClient;
use Illuminate\Console\Command;
use Throwable;

class PrevalidateCbtaMixture extends Command
{
    protected $signature = 'cbta:prevalidate
        {unitCode : Codigo externo de unidad CBTA}
        {type : npt u oncology}
        {productCode : Codigo externo de producto}
        {presentationCode : Codigo externo de presentacion}
        {quantity : Cantidad solicitada}
        {unit : ml, mg o unit}
        {--catalog-version= : Version previamente consultada del catalogo}';

    protected $description = 'Prevalida un insumo de mezcla en CBTA sin crear solicitudes ni afectar inventario';

    public function handle(CbtaCatalogClient $client): int
    {
        try {
            $result = $client->prevalidateMixture(array_filter([
                'medical_unit_code' => (string) $this->argument('unitCode'),
                'catalog_type' => (string) $this->argument('type'),
                'catalog_version' => $this->option('catalog-version'),
                'items' => [[
                    'product_code' => (string) $this->argument('productCode'),
                    'presentation_code' => (string) $this->argument('presentationCode'),
                    'quantity' => (float) $this->argument('quantity'),
                    'unit' => (string) $this->argument('unit'),
                ]],
            ], fn ($value) => $value !== null && $value !== ''));
        } catch (Throwable $exception) {
            $this->error('No fue posible prevalidar en CBTA: '.$exception->getMessage());

            return self::FAILURE;
        }

        $item = $result['items'][0] ?? [];
        $this->table(['Resultado', 'Disponible', 'Unidad', 'Version'], [[
            $result['valid'] ? 'Valido' : 'Rechazado',
            $item['available_quantity'] ?? 'N/D',
            $item['availability_unit'] ?? 'N/D',
            $result['catalog_version'],
        ]]);

        foreach ($result['errors'] as $error) {
            $this->warn("{$error['code']}: {$error['message']}");
        }

        return $result['valid'] ? self::SUCCESS : self::FAILURE;
    }
}
