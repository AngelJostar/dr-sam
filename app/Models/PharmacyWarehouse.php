<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PharmacyWarehouse extends BaseModel
{
    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }

    public function medicalUnit(): BelongsTo
    {
        return $this->belongsTo(MedicalUnit::class);
    }
}
