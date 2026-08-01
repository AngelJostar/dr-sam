<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicationCatalogItem extends BaseModel
{
    protected function casts(): array
    {
        return [
            'requires_prescription' => 'boolean',
            'controlled' => 'boolean',
            'cold_chain' => 'boolean',
            'sector_health' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }
}

