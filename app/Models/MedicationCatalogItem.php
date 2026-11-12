<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MedicationCatalogItem extends BaseModel
{
    public const STATUS_LABELS = ['active' => 'Activo', 'inactive' => 'Inactivo', 'shortage' => 'Desabasto', 'discontinued' => 'Descontinuado'];

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function pharmacyCategory(): string
    {
        $group = $this->therapeutic_group ?: data_get($this->metadata, 'group');
        if (filled($group)) return mb_convert_case(trim($group), MB_CASE_TITLE, 'UTF-8');
        $description = mb_strtolower($this->name.' '.$this->description);
        foreach (['jeringa' => 'Material de curación', 'gasa' => 'Material de curación', 'venda' => 'Material de curación', 'guante' => 'Material de curación'] as $word => $category) {
            if (str_contains($description, $word)) return $category;
        }
        return 'Medicamentos e insumos';
    }

    public function pharmacyOffers(): HasMany
    {
        return $this->hasMany(PharmacyProduct::class);
    }

    public function scopePublishedInPharmacy($query)
    {
        return $query->where('digital_pharmacy_enabled', true)->where('status', 'active')->where('controlled', false)->where('cold_chain', false);
    }

    protected function casts(): array
    {
        return [
            'requires_prescription' => 'boolean',
            'controlled' => 'boolean',
            'cold_chain' => 'boolean',
            'sector_health' => 'boolean',
            'metadata' => 'array',
            'digital_pharmacy_enabled' => 'boolean',
        ];
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function unitSettings(): HasMany
    {
        return $this->hasMany(UnitMedicationSetting::class);
    }
}

