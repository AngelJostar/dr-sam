<?php

namespace App\Services\Platform;

use App\Models\User;

class ImportUserDirectory
{
    public const AREAS = [
        'providers' => 'Proveedores',
        'regulatory' => 'Regulatorio',
        'logistics' => 'Logística',
        'administration' => 'Administración',
    ];

    public static function area(User $user): ?string
    {
        $assigned = data_get($user->metadata, 'import_area');
        if (is_string($assigned) && isset(self::AREAS[$assigned])) {
            return $assigned;
        }

        return match ($user->role) {
            'provider' => 'providers',
            'messenger', 'delivery_coordinator' => 'logistics',
            'superadmin', 'admin', 'billing' => 'administration',
            default => null,
        };
    }
}
