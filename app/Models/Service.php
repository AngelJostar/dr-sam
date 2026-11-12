<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends BaseModel
{
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function contractedServices(): HasMany
    {
        return $this->hasMany(ContractedService::class);
    }

    public function formats(): HasMany
    {
        return $this->hasMany(ServiceFormat::class)->orderBy('name')->orderBy('id');
    }
}

