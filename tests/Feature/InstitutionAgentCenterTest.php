<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Models\MedicalUnit;
use App\Models\User;
use App\Support\InstitutionAgentCenter;
use App\Support\UnitAgentCenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstitutionAgentCenterTest extends TestCase
{
    use RefreshDatabase;

    private function institution(): Institution
    {
        $user = User::query()->create(['name' => 'Coordinación', 'username' => 'agents.institution', 'role' => 'institution', 'status' => 'active']);
        $institution = Institution::query()->create(['owner_user_id' => $user->id, 'name' => 'Institución de prueba', 'metadata' => ['state' => 'Conservar']]);
        $this->actingAs($user);

        return $institution;
    }

    private function data(Institution $institution): array
    {
        return InstitutionAgentCenter::agents($institution)->firstWhere('id', 'maintenance');
    }

    public function test_center_has_institutional_instructions_and_menu_access_in_every_section(): void
    {
        $institution = $this->institution();
        $agents = InstitutionAgentCenter::agents($institution);
        $this->assertCount(12, $agents);
        foreach ($agents as $agent) {
            $this->assertSame('inactive', $agent['status']);
            $this->assertNotEmpty($agent['objective']);
            $this->assertNotEmpty($agent['instructions']);
            $this->assertNotEmpty($agent['response_format']);
            $this->assertStringContainsString('Unidad', $agent['report_columns']);
            $this->assertEmpty(array_diff($agent['sources'], array_keys(config('institution_agents.sources'))));
        }
        $this->get(route('institution.dashboard', ['section' => 'agents', 'institution' => $institution->id]))
            ->assertOk()->assertSeeInOrder(['Centro de ayuda', 'Centro de agentes'])
            ->assertSee('12 agentes')->assertSee('Red de unidades')->assertSee('Servicios institucionales')
            ->assertSee('Todas las unidades adscritas')->assertSee('Editar instrucciones')->assertSee('Guardar instrucciones')
            ->assertSee('Ejecución automática inactiva')->assertSee('data-agent-dialog', false)
            ->assertDontSee('Nuevo servicio');
        $this->get(route('institution.dashboard', ['section' => 'medications']))->assertOk()->assertSee('Centro de agentes');
        $this->assertArrayNotHasKey('agent_center', $institution->fresh()->metadata);
    }

    public function test_edits_are_persistent_isolated_from_other_institutions_and_units_and_versioned(): void
    {
        $institution = $this->institution();
        $other = Institution::query()->create(['name' => 'Otra institución']);
        $unit = MedicalUnit::query()->create(['institution_id' => $institution->id, 'name' => 'Unidad adscrita']);
        $before = $this->data($institution);
        $unitBefore = UnitAgentCenter::agents($unit)->firstWhere('id', 'maintenance');
        $data = array_replace($before, ['objective' => 'Consolidar pendientes por unidad', 'instructions' => "1. Revisar reportes de las unidades.\n2. Preparar pendientes institucionales.", 'status' => 'active', 'scope' => $other->id]);
        $url = route('institution.agents.update', ['institution' => $institution->id, 'agent' => 'maintenance']);
        $this->patchJson($url, $data)->assertOk()->assertJsonPath('message', 'Guardado correctamente')
            ->assertJsonPath('agent.version', 2)->assertJsonPath('agent.status', 'inactive');
        $stored = $this->data($institution->fresh());
        $this->assertSame($data['instructions'], $stored['instructions']);
        $this->assertSame('Conservar', $institution->fresh()->metadata['state']);
        $this->assertArrayNotHasKey('scope', $stored);
        $this->assertCount(1, $institution->fresh()->metadata['agent_center']);
        $this->assertSame($before['instructions'], $this->data($other)['instructions']);
        $this->assertSame($unitBefore, UnitAgentCenter::agents($unit->fresh())->firstWhere('id', 'maintenance'));
        $this->assertDatabaseCount('audit_logs', 1);
        $this->patchJson($url, $data)->assertStatus(409);
        $this->assertSame(2, $this->data($institution->fresh())['version']);
        $this->assertDatabaseCount('audit_logs', 1);
        $this->get(route('institution.dashboard', ['institution' => $institution->id, 'section' => 'agents']))->assertOk()->assertSee('Consolidar pendientes por unidad');
    }

    public function test_create_and_validation_preserve_text_and_reject_invalid_configuration(): void
    {
        $institution = $this->institution();
        $data = array_replace($this->data($institution), ['name' => 'Agente institucional de prueba', 'objective' => '<script>alert(1)</script>']);
        $created = $this->postJson(route('institution.agents.store', ['institution' => $institution->id]), $data)
            ->assertCreated()->assertJsonPath('agent.status', 'inactive')->assertJsonPath('agent.version', 1)->json('agent');
        $this->assertCount(13, InstitutionAgentCenter::agents($institution->fresh()));
        $this->get(route('institution.dashboard', ['institution' => $institution->id, 'section' => 'agents']))->assertOk()
            ->assertSee('Agente institucional de prueba')->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false);
        $url = route('institution.agents.update', ['institution' => $institution->id, 'agent' => $created['id']]);
        $this->patchJson($url, array_replace($data, ['instructions' => '', 'sources' => ['patients']]))
            ->assertUnprocessable()->assertJsonValidationErrors(['instructions', 'sources.0']);
        $this->patchJson($url, array_replace($data, ['sources' => []]))->assertUnprocessable()->assertJsonValidationErrors('sources');
        $this->patchJson($url, array_replace($data, ['instructions' => str_repeat('x', 10001)]))->assertUnprocessable()->assertJsonValidationErrors('instructions');
        $this->patchJson($url, array_replace($data, ['version' => null]))->assertUnprocessable()->assertJsonValidationErrors('version');
        $this->patchJson(route('institution.agents.update', ['institution' => $institution->id, 'agent' => 'missing']), $data)->assertNotFound();
        $this->assertDatabaseCount('audit_logs', 1);
    }

    public function test_other_institutions_and_non_institutional_roles_cannot_access_or_modify_agents(): void
    {
        $institution = $this->institution();
        $other = Institution::query()->create(['name' => 'Otra institución']);
        $data = $this->data($institution);
        $this->get(route('institution.dashboard', ['institution' => $other->id, 'section' => 'agents']))->assertForbidden();
        $this->postJson(route('institution.agents.store', ['institution' => $other->id]), $data)->assertForbidden();
        $this->patchJson(route('institution.agents.update', ['institution' => $other->id, 'agent' => 'maintenance']), $data)->assertForbidden();
        foreach (['unit', 'operational', 'patient', 'institution'] as $role) {
            $user = User::query()->create(['name' => $role, 'username' => 'agent.'.$role, 'role' => $role, 'status' => 'active']);
            $this->actingAs($user)->get(route('institution.dashboard', ['institution' => $institution->id, 'section' => 'agents']))->assertForbidden();
            $this->postJson(route('institution.agents.store', ['institution' => $institution->id]), $data)->assertForbidden();
            $this->patchJson(route('institution.agents.update', ['institution' => $institution->id, 'agent' => 'maintenance']), $data)->assertForbidden();
        }
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_administrator_saves_in_the_selected_institution(): void
    {
        $institution = $this->institution();
        $other = Institution::query()->create(['name' => 'Otra institución']);
        $admin = User::query()->create(['name' => 'Administración', 'username' => 'agents.admin', 'role' => 'admin', 'status' => 'active']);
        $this->actingAs($admin)->patchJson(route('institution.agents.update', ['institution' => $other->id, 'agent' => 'maintenance']), $this->data($other))
            ->assertOk()->assertJsonPath('agent.version', 2);
        $this->assertSame(1, $this->data($institution->fresh())['version']);
        $this->assertSame(2, $this->data($other->fresh())['version']);
    }
}
