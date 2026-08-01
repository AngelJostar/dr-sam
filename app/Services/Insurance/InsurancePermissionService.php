<?php

namespace App\Services\Insurance;

use App\Models\User;

class InsurancePermissionService
{
    private const ABILITIES = [
        'view' => [
            'superadmin',
            'admin',
            'insurance_admin',
            'medical_auditor',
            'patient_coordinator',
            'delivery_coordinator',
            'hospital_coordinator',
            'billing',
            'read_only',
            'insurance_advisor',
        ],
        'manage_patients' => ['superadmin', 'admin', 'insurance_admin', 'patient_coordinator'],
        'manage_treatments' => ['superadmin', 'admin', 'insurance_admin', 'medical_auditor', 'patient_coordinator'],
        'manage_deliveries' => ['superadmin', 'admin', 'insurance_admin', 'delivery_coordinator'],
        'manage_hospitalizations' => ['superadmin', 'admin', 'insurance_admin', 'hospital_coordinator'],
        'manage_billing' => ['superadmin', 'admin', 'insurance_admin', 'billing'],
        'manage_authorizations' => ['superadmin', 'admin', 'insurance_admin', 'medical_auditor', 'hospital_coordinator'],
        'manage_documents' => [
            'superadmin',
            'admin',
            'insurance_admin',
            'medical_auditor',
            'patient_coordinator',
            'delivery_coordinator',
            'hospital_coordinator',
            'billing',
        ],
        'admin_users' => ['superadmin', 'admin', 'insurance_admin'],
    ];

    public function can(?User $user, string $ability): bool
    {
        if (! $user) {
            return false;
        }

        return in_array($user->role, self::ABILITIES[$ability] ?? [], true);
    }

    public function assert(?User $user, string $ability): void
    {
        abort_unless($this->can($user, $ability), 403, 'No tienes permiso para esta operacion.');
    }

    public function abilitiesFor(?User $user): array
    {
        return collect(array_keys(self::ABILITIES))
            ->mapWithKeys(fn (string $ability) => [$ability => $this->can($user, $ability)])
            ->all();
    }
}
