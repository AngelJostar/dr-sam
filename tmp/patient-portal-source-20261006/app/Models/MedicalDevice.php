<?php

namespace App\Models;

class MedicalDevice extends BaseModel
{
    protected $fillable = [
        'name',
        'code',
        'category',
        'manufacturer',
        'model',
        'connectivity',
        'linked_module',
        'recorded_data',
        'compatibility',
        'status',
        'metadata',
    ];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }
}
