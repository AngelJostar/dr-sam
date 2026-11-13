<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PharmacyPrescriptionUpload extends Model
{
    protected $fillable = ['user_id', 'patient_id', 'path', 'file_name', 'mime_type', 'size_bytes', 'status'];
    protected $hidden = ['path'];
    protected function casts(): array
    {
        return ['size_bytes' => 'integer'];
    }
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
