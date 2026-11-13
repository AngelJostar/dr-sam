<?php

namespace App\Services;

use App\Models\MedicationCatalogItem;

class UniversalMedicationCatalog
{
    public function definition(): array
    {
        return [
            'title' => 'Catálogo Universal de Medicamentos',
            'eyebrow' => 'Módulo superadministrador',
            'description' => 'Medicamentos y presentaciones del catálogo universal.',
            'search_placeholder' => 'Buscar SKU, código de barras, CNIS o medicamento',
            'columns' => ['Clave CNIS', 'Grupo', 'Medicamento', 'Descripción', 'Denominación distintiva', 'Unidades móviles', 'Núcleos básicos', 'CESSA', 'Catálogo institucional de', 'SKU interno', 'Código de barras numérico', 'Código de barras imagen', 'Estatus'],
            'query' => fn () => MedicationCatalogItem::query()->orderBy('cnis')->orderBy('id'),
            'search' => ['sku', 'barcode', 'cnis', 'name', 'generic_name', 'description', 'presentation', 'therapeutic_group'],
            'status_filter' => true,
            'per_page' => 250,
            'map' => fn (MedicationCatalogItem $record) => $this->values($record),
        ];
    }

    public function values(MedicationCatalogItem $record): array
    {
        return [
            $record->cnis ?? 'Sin CNIS',
            data_get($record->metadata, 'group', $record->therapeutic_group ?? 'Por registrar'),
            trim($record->name.' '.($record->external_id ?? '')),
            $record->description ?? $record->presentation ?? 'Por registrar',
            data_get($record->metadata, 'distinctive_name') ?: 'Por registrar',
            data_get($record->metadata, 'mobile_units', 'No especificado'),
            data_get($record->metadata, 'basic_units', 'No especificado'),
            data_get($record->metadata, 'cessa', 'No especificado'),
            data_get($record->metadata, 'catalog', 'Por registrar'),
            $record->sku ?? 'Por registrar',
            $record->barcode ?? 'Por registrar',
            $record->barcode ? route('superadmin.medications.barcode', $record) : '',
            $record->status,
        ];
    }
}
