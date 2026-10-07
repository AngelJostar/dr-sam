<?php

namespace App\Models;

class InsuranceCarrier extends BaseModel
{
    protected $fillable = [
        'name',
        'slug',
        'type',
        'contact',
        'phone',
        'email',
        'scope',
        'status',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }
}
