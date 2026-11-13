<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceFormat extends BaseModel
{
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function purposeLabel(): string
    {
        return match ($this->purpose) {
            'request' => 'Solicitud',
            'execution' => 'Ejecución',
            'operation' => 'Operación',
            default => 'General',
        };
    }

    public function catalogSection(): ?string
    {
        // Only implemented administration screens may be opened from the catalog.
        return match ($this->key) {
            'npt-request', 'oncology-request' => 'mix-request-format',
            'prescription' => 'prescription-format',
            default => null,
        };
    }

    public function mixPanel(): ?string
    {
        return match ($this->key) {
            'npt-request' => 'parenteral',
            'oncology-request' => 'oncology',
            default => null,
        };
    }
}
