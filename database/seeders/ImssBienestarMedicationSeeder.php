<?php

namespace Database\Seeders;

use App\Models\Institution;
use App\Models\MedicalUnit;
use App\Models\MedicationCatalogItem;
use App\Models\UnitMedicationSetting;
use Illuminate\Database\Seeder;

class ImssBienestarMedicationSeeder extends Seeder
{
    public const SOURCE_URL = 'https://pgsp.imssbienestar.gob.mx/rutas_de_la_salud/docs/App_RdlS_v5.1_Meds_MatCur.pdf';

    public function run(): void
    {
        $institution = Institution::query()->where('external_id', 'inst-portal')->first();
        if (! $institution) {
            return;
        }

        $unit = MedicalUnit::query()
            ->where('external_id', 'demo-hospital-general-dr-sam')
            ->where('institution_id', $institution->id)
            ->first();
        $items = json_decode(
            file_get_contents(database_path('data/imss_bienestar_essential_medicines_2026.json')),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        foreach ($items as $row) {
            $sourceMetadata = [
                'source' => self::SOURCE_URL,
                'source_label' => 'Listado de Insumos Esenciales, IMSS Bienestar 2026',
                'source_scope' => 'Primer nivel de atención',
                'mobile_units' => $row['mobile_units'],
                'basic_units' => $row['basic_units'],
                'cessa' => $row['cessa'],
                'dispensing_condition' => 'No especificada en la fuente',
            ];
            $medication = MedicationCatalogItem::query()->firstOrCreate(
                ['institution_id' => $institution->id, 'cnis' => $row['cnis']],
                [
                    'external_id' => 'imss-bienestar-essential-'.str_replace('.', '-', $row['cnis']),
                    'name' => $row['name'],
                    'generic_name' => $row['name'],
                    'therapeutic_group' => $row['therapeutic_group'],
                    'presentation' => $row['presentation'],
                    'description' => $row['description'],
                    'sector_health' => true,
                    'status' => 'active',
                    'metadata' => $sourceMetadata,
                ],
            );

            if (data_get($medication->metadata, 'source') !== self::SOURCE_URL) {
                $medication->update(['metadata' => array_merge($medication->metadata ?? [], $sourceMetadata)]);
            }

            if ($unit) {
                UnitMedicationSetting::query()->firstOrCreate(
                    ['medical_unit_id' => $unit->id, 'medication_catalog_item_id' => $medication->id],
                    ['is_active' => true],
                );
            }
        }
    }
}
