<?php

namespace Tests\Feature;

use App\Models\MedicalUnit;
use App\Models\User;
use App\Support\UnitAgentCenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnitAgentCenterTest extends TestCase
{
    use RefreshDatabase;

    private function unit(): MedicalUnit
    {
        $user = User::query()->create(['name' => 'Administración', 'username' => 'agent.unit', 'role' => 'unit', 'status' => 'active']);
        $this->actingAs($user);
        return MedicalUnit::query()->create(['name' => 'Unidad de prueba', 'unit_username' => $user->username, 'metadata' => ['profile' => ['public_name' => 'Conservar']]]);
    }

    private function data(MedicalUnit $unit): array
    {
        return UnitAgentCenter::agents($unit)->firstWhere('id', 'maintenance');
    }

    public function test_center_has_twelve_inactive_agents_with_complete_instructions_and_is_last_in_menu(): void
    {
        $unit = $this->unit();
        $agents = UnitAgentCenter::agents($unit);
        $this->assertCount(12, $agents);
        foreach ($agents as $agent) {
            $this->assertSame('inactive', $agent['status']);
            $this->assertNotEmpty($agent['objective']);
            $this->assertNotEmpty($agent['instructions']);
            $this->assertNotEmpty($agent['sources']);
            $this->assertNotEmpty($agent['report_columns']);
        }
        $this->get(route('unit.dashboard', ['unit' => $unit->id, 'section' => 'agents']))
            ->assertOk()->assertSeeInOrder(['Personal, mantenimientos y servicios', 'Centro de agentes'])
            ->assertSee('12 agentes')->assertSee('Editar instrucciones')->assertSee('Guardar instrucciones')
            ->assertSee('Ejecución automática inactiva')->assertSee('data-agent-dialog', false);
        $this->assertArrayNotHasKey('agent_center', $unit->fresh()->metadata);
    }

    public function test_edit_persists_only_this_agent_and_unit_and_prevents_stale_overwrites(): void
    {
        $unit = $this->unit();
        $other = MedicalUnit::query()->create(['name' => 'Otra unidad']);
        $before = $this->data($unit);
        $data = array_replace($before, ['objective' => 'Revisar servicios semanales', 'instructions' => "1. Consultar la agenda.\n2. Preparar pendientes.", 'status' => 'active', 'scope' => $other->id]);
        $url = route('unit.agents.update', ['unit' => $unit->id, 'agent' => 'maintenance']);
        $this->patchJson($url, $data)->assertOk()->assertJsonPath('message', 'Guardado correctamente')
            ->assertJsonPath('agent.version', 2)->assertJsonPath('agent.status', 'inactive');
        $stored = $this->data($unit->fresh());
        $this->assertSame($data['instructions'], $stored['instructions']);
        $this->assertSame('Conservar', $unit->fresh()->metadata['profile']['public_name']);
        $this->assertArrayNotHasKey('scope', $stored);
        $this->assertCount(1, $unit->fresh()->metadata['agent_center']);
        $this->assertSame($before['instructions'], $this->data($other)['instructions']);
        $this->assertDatabaseCount('audit_logs', 1);
        $this->patchJson($url, $data)->assertStatus(409);
        $this->assertSame(2, $this->data($unit->fresh())['version']);
        $this->assertDatabaseCount('audit_logs', 1);
        $this->get(route('unit.dashboard', ['unit' => $unit->id, 'section' => 'agents']))->assertOk()->assertSee('Revisar servicios semanales');
    }

    public function test_create_and_validation_keep_configuration_safe_and_render_text_as_text(): void
    {
        $unit = $this->unit();
        $data = array_replace($this->data($unit), ['name' => 'Agente de prueba', 'objective' => '<script>alert(1)</script>']);
        $created = $this->postJson(route('unit.agents.store', ['unit' => $unit->id]), $data)
            ->assertCreated()->assertJsonPath('agent.status', 'inactive')->assertJsonPath('agent.version', 1)->json('agent');
        $this->assertCount(13, UnitAgentCenter::agents($unit->fresh()));
        $this->get(route('unit.dashboard', ['unit' => $unit->id, 'section' => 'agents']))->assertOk()
            ->assertSee('Agente de prueba')->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false);
        $url = route('unit.agents.update', ['unit' => $unit->id, 'agent' => $created['id']]);
        $this->patchJson($url, array_replace($data, ['instructions' => '', 'sources' => ['unknown']]))
            ->assertUnprocessable()->assertJsonValidationErrors(['instructions', 'sources.0']);
        $this->patchJson($url, array_replace($data, ['sources' => []]))->assertUnprocessable()->assertJsonValidationErrors('sources');
        $this->patchJson($url, array_replace($data, ['instructions' => str_repeat('x', 10001)]))->assertUnprocessable()->assertJsonValidationErrors('instructions');
        $this->patchJson(route('unit.agents.update', ['unit' => $unit->id, 'agent' => 'missing']), $data)->assertNotFound();
        $this->assertDatabaseCount('audit_logs', 1);
    }

    public function test_other_units_and_non_administrative_roles_cannot_access_or_modify_agents(): void
    {
        $unit = $this->unit();
        $other = MedicalUnit::query()->create(['name' => 'Otra unidad']);
        $data = $this->data($unit);
        $this->get(route('unit.dashboard', ['unit' => $other->id, 'section' => 'agents']))->assertForbidden();
        $this->postJson(route('unit.agents.store', ['unit' => $other->id]), $data)->assertForbidden();
        $this->patchJson(route('unit.agents.update', ['unit' => $other->id, 'agent' => 'maintenance']), $data)->assertForbidden();
        foreach (['operational', 'patient'] as $role) {
            $user = User::query()->create(['name' => $role, 'username' => 'agent.'.$role, 'role' => $role, 'status' => 'active']);
            $this->actingAs($user)->postJson(route('unit.agents.store', ['unit' => $unit->id]), $data)->assertForbidden();
            $this->patchJson(route('unit.agents.update', ['unit' => $unit->id, 'agent' => 'maintenance']), $data)->assertForbidden();
        }
        $unassignedInstitution = User::query()->create(['name' => 'Sin institución', 'username' => 'agent.unassigned', 'role' => 'institution', 'status' => 'active']);
        $this->actingAs($unassignedInstitution)->get(route('unit.dashboard', ['unit' => $unit->id, 'section' => 'agents']))->assertForbidden();
        $this->postJson(route('unit.agents.store', ['unit' => $unit->id]), $data)->assertForbidden();
        $this->assertDatabaseCount('audit_logs', 0);
    }
}
