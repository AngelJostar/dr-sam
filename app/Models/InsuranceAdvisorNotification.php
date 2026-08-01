<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InsuranceAdvisorNotification extends BaseModel
{
    protected function casts(): array
    {
        return ['occurred_at' => 'datetime', 'metadata' => 'array'];
    }

    public function policy(): BelongsTo
    {
        return $this->belongsTo(InsurancePolicy::class, 'insurance_policy_id');
    }
}
