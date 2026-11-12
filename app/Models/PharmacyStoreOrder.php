<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PharmacyStoreOrder extends BaseModel
{
    protected function casts(): array
    {
        return ['items' => 'array', 'address' => 'array', 'total' => 'decimal:2', 'subtotal' => 'decimal:2', 'shipping_fee' => 'decimal:2', 'terms_accepted_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class);
    }
}
