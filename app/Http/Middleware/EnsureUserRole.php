<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        if ($user && is_array(data_get($user->metadata, 'assigned_modules')) && !in_array($user->role, ['superadmin', 'doctor', 'patient'], true)) {
            foreach (config('drsam.modules', []) as $key => $module) {
                if ($request->routeIs($module['route'] ?? '')) {
                    abort_unless(app(\App\Services\Platform\DashboardRegistry::class)->modulesFor($user)->contains('key', $key), 403);
                    return $next($request);
                }
            }
        }

        if (! $user || ! in_array($user->role, $roles, true)) {
            abort(403, 'No tienes permiso para entrar a este modulo.');
        }

        return $next($request);
    }
}

