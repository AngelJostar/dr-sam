<?php

namespace App\Http\Middleware;

use App\Services\Platform\ActionPermissionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActionPermission
{
    public function __construct(private readonly ActionPermissionService $permissions)
    {
    }

    public function handle(Request $request, Closure $next, string $ability): Response
    {
        $this->permissions->assert($request->user(), $ability);

        return $next($request);
    }
}
