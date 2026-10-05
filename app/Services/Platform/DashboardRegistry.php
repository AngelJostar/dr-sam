<?php

namespace App\Services\Platform;

use App\Enums\UserRole;
use App\Models\PlatformModule;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

class DashboardRegistry
{
    public function modulesFor(User $user): Collection
    {
        $role = UserRole::tryFrom($user->role);
        $moduleRows = PlatformModule::query()
            ->get()
            ->keyBy('key');

        $modules = collect(config('drsam.modules', []))
            ->filter(fn (array $module, string $key) => $moduleRows->get($key)?->enabled ?? true)
            ->map(fn (array $module, string $key) => [
                'key' => $key,
                'label' => $moduleRows->get($key)?->label ?? $module['label'],
                'target' => $moduleRows->get($key)?->target ?? $module['target'],
                'roles' => $moduleRows->get($key)?->roles ?? $module['roles'] ?? [],
                'priority' => config("drsam.module_priorities.{$key}"),
                'url' => isset($module['route']) && Route::has($module['route'])
                    ? route($module['route'])
                    : '#',
            ]);

        // Passwordless review only changes how demo users authenticate. It must
        // never broaden the modules that an authenticated role can access.
        if ($role?->canReviewAllModules() && ($user->role === 'superadmin' || !is_array(data_get($user->metadata, 'assigned_modules')))) {
            return $modules->values();
        }

        return $modules
            ->filter(function (array $module) use ($user) {
                $assigned = data_get($user->metadata, 'assigned_modules');
                if (is_array($assigned) && !in_array($user->role, ['doctor', 'patient'], true)) {
                    return !in_array($module['key'], ['superadmin', 'doctor', 'patient'], true) && in_array($module['key'], $assigned, true);
                }
                return in_array($user->role, $module['roles'], true);
            })
            ->values();
    }

    public function allDemoUsers(): Collection
    {
        return collect(config('drsam.demo_users', []))
            ->map(fn (array $user) => [
                'username' => $user['username'],
                'name' => $user['name'],
                'role' => $user['role'],
                'module' => $user['module'],
            ]);
    }
}
