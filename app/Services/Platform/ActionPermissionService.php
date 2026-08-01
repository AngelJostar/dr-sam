<?php

namespace App\Services\Platform;

use App\Models\User;

class ActionPermissionService
{
    public function can(?User $user, string $ability): bool
    {
        if (! $user) {
            return false;
        }

        $roles = config('drsam_permissions', [])[$ability] ?? null;

        if (! is_array($roles)) {
            return false;
        }

        return in_array($user->role, $roles, true);
    }

    public function assert(?User $user, string $ability): void
    {
        abort_unless($this->can($user, $ability), 403, 'No tienes permiso para ejecutar esta accion.');
    }
}
