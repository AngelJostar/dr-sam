<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Patient extends BaseModel
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'email_verified_at' => 'datetime',
            'profile_completed_at' => 'datetime',
            'enrolled_at' => 'date',
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function clinicalRecords(): HasMany
    {
        return $this->hasMany(ClinicalRecord::class);
    }

    public function clinicalEncounters(): HasMany
    {
        return $this->hasMany(ClinicalEncounter::class);
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(PatientOrder::class);
    }

    public function primaryDoctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'primary_doctor_id');
    }

    public function insurancePolicies(): HasMany
    {
        return $this->hasMany(InsurancePolicy::class);
    }

    public function diagnoses(): HasMany
    {
        return $this->hasMany(PatientDiagnosis::class);
    }

    public function treatments(): HasMany
    {
        return $this->hasMany(Treatment::class);
    }

    public function medicationDeliveries(): HasMany
    {
        return $this->hasMany(MedicationDelivery::class);
    }

    public function hospitalizations(): HasMany
    {
        return $this->hasMany(Hospitalization::class);
    }

    public function authorizations(): HasMany
    {
        return $this->hasMany(Authorization::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }
}
