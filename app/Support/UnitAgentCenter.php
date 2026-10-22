<?php

namespace App\Support;

use App\Models\MedicalUnit;
use Illuminate\Support\Collection;

final class UnitAgentCenter
{
    public static function agents(MedicalUnit $unit): Collection
    {
        $defaults = collect(config('unit_agents.agents'))->map(fn (array $agent, string $id) => $agent + [
            'id' => $id, 'status' => 'inactive', 'version' => 1, 'updated_at' => null,
        ]);

        return $defaults->replace(data_get($unit->metadata, 'agent_center', []))->values();
    }
}
