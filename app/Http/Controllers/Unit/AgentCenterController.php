<?php

namespace App\Http\Controllers\Unit;

use App\Http\Controllers\Controller;
use App\Models\MedicalUnit;
use App\Services\Platform\PlatformAuditService;
use App\Support\OperationalUnitContext;
use App\Support\UnitAgentCenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AgentCenterController extends Controller
{
    public function store(Request $request, PlatformAuditService $audit): JsonResponse
    {
        return $this->save($request, $audit);
    }

    public function update(Request $request, PlatformAuditService $audit, string $agent): JsonResponse
    {
        return $this->save($request, $audit, $agent);
    }

    private function save(Request $request, PlatformAuditService $audit, ?string $id = null): JsonResponse
    {
        $unit = OperationalUnitContext::resolve($request);
        abort_unless($unit, 404);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'objective' => ['required', 'string', 'max:500'],
            'instructions' => ['required', 'string', 'max:10000'],
            'sources' => ['required', 'array', 'min:1', 'max:24'],
            'sources.*' => ['required', 'string', 'distinct', Rule::in(array_keys(config('unit_agents.sources')))],
            'response_format' => ['required', 'string', 'max:200'],
            'report_columns' => ['required', 'string', 'max:500'],
            'version' => [$id ? 'required' : 'nullable', 'integer', 'min:1'],
        ]);

        $saved = DB::transaction(function () use ($request, $audit, $unit, $id, $data): array {
            $locked = MedicalUnit::query()->lockForUpdate()->findOrFail($unit->id);
            $agents = UnitAgentCenter::agents($locked);
            $previous = $id ? $agents->firstWhere('id', $id) : null;
            abort_if($id && !$previous, 404);
            abort_if($previous && $previous['version'] !== (int) $data['version'], 409, 'Las instrucciones cambiaron en otra sesión. Cierra el formulario y actualiza la página antes de guardar.');
            abort_if(!$previous && $agents->count() >= 100, 422, 'La unidad puede configurar hasta 100 agentes.');
            $saved = array_merge($data, [
                'id' => $id ?? (string) Str::uuid(), 'status' => 'inactive',
                'version' => ($previous['version'] ?? 0) + 1,
                'updated_at' => now()->toISOString(), 'updated_by' => $request->user()->id,
            ]);
            $metadata = $locked->metadata ?? [];
            $metadata['agent_center'][$saved['id']] = $saved;
            $locked->update(['metadata' => $metadata]);
            $audit->record($request, $previous ? 'unit.agent.updated' : 'unit.agent.created', $locked, 'unit', [
                'agent_id' => $saved['id'], 'version' => $saved['version'],
            ]);
            return $saved;
        });

        return response()->json(['message' => 'Guardado correctamente', 'agent' => $saved], $id ? 200 : 201);
    }
}
