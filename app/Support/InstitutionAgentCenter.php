<?php

namespace App\Support;

use App\Models\Institution;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

final class InstitutionAgentCenter
{
    public static function resolve(Request $request): Institution
    {
        $user = $request->user();
        if ($user?->institution) {
            abort_if($request->filled('institution') && $request->integer('institution') !== $user->institution->id, 403);

            return Institution::query()->findOrFail($user->institution->id);
        }

        abort_unless(in_array($user?->role, ['superadmin', 'admin'], true), 403);

        return $request->filled('institution')
            ? Institution::query()->findOrFail($request->integer('institution'))
            : Institution::query()->orderBy('name')->firstOrFail();
    }

    public static function agents(Institution $institution): Collection
    {
        $defaults = collect(config('institution_agents.agents'))->map(fn (array $agent, string $id) => $agent + [
            'id' => $id, 'status' => 'inactive', 'version' => 1, 'updated_at' => null,
        ]);

        return $defaults->replace(data_get($institution->metadata, 'agent_center', []))->values();
    }
}
