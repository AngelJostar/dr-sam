<?php

namespace Database\Seeders;

use App\Models\MedicationCatalogItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UniversalMedicationDemoSeeder extends Seeder
{
    public function run(): void
    {
        $items = json_decode(file_get_contents(database_path('data/imss_bienestar_essential_medicines_2026.json')), true, flags: JSON_THROW_ON_ERROR);
        $sources = json_decode(file_get_contents(database_path('data/universal_medication_sources.json')), true, flags: JSON_THROW_ON_ERROR);
        $references = collect($sources['records'])->keyBy('cnis');
        DB::transaction(function () use ($items, $sources, $references): void {
            app(\App\Services\MedicationSkuSequence::class)->lock();
            foreach ($items as $row) {
                // Reuse an existing CNIS, regardless of its institution. Never replace captured data.
                $medication = MedicationCatalogItem::query()->where('cnis', $row['cnis'])->orderBy('id')->first();
                if (! $medication) {
                    $medication = MedicationCatalogItem::create([
                        'external_id' => 'universal-demo-'.str_replace('.', '-', $row['cnis']),
                        'cnis' => $row['cnis'], 'name' => $row['name'], 'generic_name' => $row['name'],
                        'therapeutic_group' => $row['therapeutic_group'], 'presentation' => $row['presentation'],
                        'description' => $row['description'], 'sector_health' => true, 'status' => 'active',
                        'metadata' => [
                            'source' => ImssBienestarMedicationSeeder::SOURCE_URL,
                            'source_label' => 'IMSS Bienestar · Rutas de la Salud v5.1',
                            'source_reviewed_at' => '2026-11-12',
                            'source_page' => $references[$row['cnis']]['imss_page'],
                            'salud_key_reference' => $references[$row['cnis']]['salud_cdmx_page'] ? ['url' => $sources['salud_url'], 'page' => $references[$row['cnis']]['salud_cdmx_page'], 'edition' => $sources['salud_edition']] : null,
                            'catalog' => 'IMSS Bienestar', 'demo_catalog' => true,
                            'mobile_units' => $row['mobile_units'], 'basic_units' => $row['basic_units'], 'cessa' => $row['cessa'],
                            'dispensing_condition' => 'No especificada en la fuente',
                        ],
                    ]);
                }
                $identifiers = [];
                $metadata = $medication->metadata ?? [];
                $enrichedMetadata = $metadata + [
                    'mobile_units' => $row['mobile_units'], 'basic_units' => $row['basic_units'], 'cessa' => $row['cessa'],
                    'catalog' => 'IMSS Bienestar',
                    'catalog_reference' => ['url' => ImssBienestarMedicationSeeder::SOURCE_URL, 'page' => $references[$row['cnis']]['imss_page']],
                ];
                if ($metadata !== $enrichedMetadata) {
                    $identifiers['metadata'] = $enrichedMetadata;
                }
                $previousDemoSku = 'KLINI-MED-'.str_pad((string) $medication->id, 6, '0', STR_PAD_LEFT);
                if (blank($medication->sku) || $medication->sku === $previousDemoSku || preg_match('/\A[0-9]{8}\z/', $medication->sku)) {
                    $skuNumber = preg_match('/\A[0-9]{8}\z/', $medication->sku ?? '') ? (int) $medication->sku : $medication->id;
                    do {
                        $sku = '1'.str_pad((string) $skuNumber++, 8, '0', STR_PAD_LEFT);
                    } while (MedicationCatalogItem::where('sku', $sku)->where('id', '!=', $medication->id)->exists());
                    $identifiers['sku'] = $sku;
                }
                if (blank($medication->barcode)) {
                    $identifiers['barcode'] = '9900'.str_pad((string) $medication->id, 12, '0', STR_PAD_LEFT);
                    $identifiers['barcode_type'] = 'internal';
                }
                if ($identifiers) {
                    $medication->update($identifiers);
                }
            }
        });
    }
}
