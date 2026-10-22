<?php

namespace Tests\Feature;

use App\Models\MedicalUnit;
use App\Models\ProcedureArea;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnitOperationalAreasTest extends TestCase
{
    use RefreshDatabase;

    public function test_surgery_calendar_and_pediatric_navigation_keep_the_selected_unit(): void
    {
        $user = User::query()->create(['name' => 'Unidad', 'username' => 'calendar.unit', 'role' => 'unit', 'status' => 'active']);
        $unit = MedicalUnit::query()->create(['name' => 'Hospital propio', 'unit_username' => $user->username]);
        $other = MedicalUnit::query()->create(['name' => 'Hospital ajeno']);
        $room = $unit->procedureAreas()->create(['type' => 'operating', 'unit_number' => 'Q-01']);
        $consulting = $unit->procedureAreas()->create(['type' => 'consulting', 'unit_number' => 'C-01']);
        $patient = \App\Models\Patient::query()->create(['full_name' => 'Cirugía del hospital']);
        foreach ([[$unit, $room, 'Cirugía propia'], [$other, $room, 'Cirugía ajena'], [$unit, $consulting, 'Consulta externa excluida']] as [$hospital, $area, $reason]) {
            \App\Models\Appointment::query()->create(['medical_unit_id' => $hospital->id, 'procedure_area_id' => $area->id, 'patient_id' => $patient->id, 'starts_at' => '2026-10-21 10:00', 'reason' => $reason]);
        }
        $this->actingAs($user);
        $this->get(route('operational.areas.show', ['type' => 'operating', 'unit' => $unit->id, 'section' => 'calendar', 'month' => '2026-10']))
            ->assertOk()->assertSee('Calendario de cirugías')->assertSee('Cirugía propia')->assertDontSee('Cirugía ajena')->assertDontSee('Consulta externa excluida');
        $this->get(route('operational.areas.show', ['type' => 'operating', 'unit' => $other->id, 'section' => 'calendar']))->assertForbidden();
        foreach (['entry' => 'Registro de entrada', 'cribs' => 'Cunas', 'nutrition' => 'Central de mezclas (Nutrición Parenteral)'] as $section => $title) {
            $this->get(route('operational.areas.show', ['type' => 'pediatric-icu', 'unit' => $unit->id, 'section' => $section]))
                ->assertOk()->assertSee($title)->assertSee('data-carousel-style="workspace"', false);
        }
        foreach ([[$unit, 'npt', 'NPT-PROPIA'], [$unit, 'chemo', 'ONCO-EXCLUIDA'], [$other, 'npt', 'NPT-AJENA']] as [$hospital, $type, $folio]) {
            \App\Models\ProviderRequest::query()->create(['medical_unit_id' => $hospital->id, 'request_type' => $type, 'external_id' => $folio]);
        }
        $this->get(route('operational.areas.show', ['type' => 'pediatric-icu', 'unit' => $unit->id, 'section' => 'nutrition']))
            ->assertOk()->assertSee('NPT-PROPIA')->assertDontSee('NPT-AJENA')->assertDontSee('ONCO-EXCLUIDA');
    }

    public function test_native_area_edits_are_shared_and_other_units_are_protected(): void
    {
        $user = User::query()->create(['name' => 'Unidad', 'username' => 'shared.unit', 'role' => 'unit', 'status' => 'active']);
        $unit = MedicalUnit::query()->create(['name' => 'Hospital propio', 'unit_username' => $user->username]);
        $other = MedicalUnit::query()->create(['name' => 'Hospital ajeno']);
        $area = $unit->procedureAreas()->create(['type' => 'uci', 'location' => 'Torre original', 'floor' => '2', 'unit_number' => 'UCI-01', 'simultaneous_capacity' => 4, 'responsible_name' => 'Responsable', 'status' => 'inactive']);
        $this->actingAs($user);
        $this->get(route('unit.dashboard', ['section' => 'procedure-areas', 'catalog' => 'uci']))->assertOk()->assertSee('Torre original')->assertSee('data-area-workspace="operation"', false)->assertDontSee('data-procedure-search', false)->assertDontSee('data-procedure-category=', false);
        $this->put(route('unit.procedure-areas.update', 'native-'.$area->id), [
            'type' => 'uci', 'location' => 'Torre compartida', 'floor' => '3', 'unit_number' => 'UCI-01', 'capacity' => 6, 'responsible' => 'Responsable',
            'schedule' => ['monday' => ['enabled' => '1', 'start' => '08:00', 'end' => '16:00']],
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('inactive', $area->fresh()->status);
        $this->assertDatabaseCount('procedure_areas', 1);
        $this->get(route('operational.areas.show', ['type' => 'uci', 'unit' => $unit->id]))->assertOk()->assertSee('Torre compartida')->assertSee('08:00');
        $this->get(route('operational.areas.show', ['type' => 'uci', 'unit' => $unit->id, 'area_status' => 'active']))->assertOk()->assertDontSee('Torre compartida');
        $this->get(route('operational.areas.show', ['type' => 'uci', 'unit' => $unit->id, 'area_status' => 'inactive']))->assertOk()->assertSee('Torre compartida');
        $area->update(['location' => 'Cambio operativo']);
        $this->get(route('unit.dashboard', ['section' => 'procedure-areas', 'catalog' => 'uci']))->assertOk()->assertSee('Cambio operativo')->assertDontSee('Torre compartida');
        foreach (['operational.dashboard', 'outpatient.dashboard', 'external-pharmacy.dashboard'] as $route) {
            $this->get(route($route, ['unit' => $other->id]))->assertForbidden();
            $html = $this->get(route($route, ['unit' => $unit->id]))->assertOk()->assertSee('operational-workspace.css')->getContent();
            preg_match('/<nav class="operational-native-menu[^"]*">(.*?)<\/nav>/s', $html, $menu);
            $this->assertStringNotContainsString('Salas de infusión', $menu[1]);
            foreach (\App\Support\UnitAreaCatalog::definitions() as $type => $definition) {
                if ($type === 'infusion') continue;
                $this->assertStringContainsString(e($definition['label']), $menu[1]);
            }
        }
        $this->get(route('operational.areas.show', ['type' => 'uci', 'unit' => $other->id]))->assertForbidden();
        foreach (['operating', 'uci', 'pediatric-icu', 'uti', 'recovery'] as $type) {
            $this->get(route('operational.areas.show', ['type' => $type, 'unit' => $unit->id]))->assertOk()->assertSee('data-carousel-style="workspace"', false)->assertSee('Activos')->assertSee('Inactivos');
        }
        \App\Models\Patient::query()->create(['full_name' => 'Paciente activo propio', 'status' => 'active', 'metadata' => ['medical_unit_id' => $unit->id]]);
        \App\Models\Patient::query()->create(['full_name' => 'Paciente inactivo propio', 'status' => 'inactive', 'metadata' => ['medical_unit_id' => $unit->id]]);
        $this->get(route('operational.patients.index', ['unit' => $unit->id, 'patient_status' => 'active']))->assertOk()->assertSee('data-carousel-style="workspace"', false)->assertSee('activo propio')->assertDontSee('inactivo propio');
    }

    public function test_new_area_types_can_be_created_edited_and_browsed(): void
    {
        $user = User::query()->create(['name' => 'Unidad', 'username' => 'areas.unit', 'role' => 'unit', 'status' => 'active']);
        $unit = MedicalUnit::query()->create(['name' => 'Hospital', 'unit_username' => $user->username]);
        $this->actingAs($user);
        foreach (['pediatric-icu' => 'UCI Pediátrico', 'inpatient-pharmacy' => 'Farmacia intrahospitalaria', 'external-pharmacy' => 'Farmacia Externa'] as $type => $label) {
            $data = ['type' => $type, 'location' => 'Planta baja', 'floor' => '1', 'unit_number' => $type.'-01', 'capacity' => 3, 'responsible' => ''];
            $this->post(route('unit.procedure-areas.store'), $data)->assertRedirect()->assertSessionHasNoErrors();
            $area = ProcedureArea::query()->where('medical_unit_id', $unit->id)->where('type', $type)->firstOrFail();
            $this->put(route('unit.procedure-areas.update', $area->external_id), array_merge($data, ['capacity' => 5]))->assertRedirect()->assertSessionHasNoErrors();
            $this->assertSame(5, $area->fresh()->simultaneous_capacity);
            $this->get(route('unit.dashboard', ['section' => 'procedure-areas', 'catalog' => $type]))->assertOk()->assertSee($label)->assertSee($type.'-01');
        }
        $html = $this->get(route('unit.dashboard', ['section' => 'procedure-areas']))->assertOk()->assertSee('Áreas operativas y equipamiento')->assertSee('Consulta Externa y Consultorios')->getContent();
        preg_match('/<nav class="unit-native-menu">(.*?)<\/nav>/s', $html, $menu);
        $this->assertStringNotContainsString('Farmacia Externa', $menu[1]);
        $this->assertStringContainsString('Médicos y Especialidades', $menu[1]);
        foreach (['doctors', 'specialties'] as $section) {
            $this->get(route('unit.dashboard', ['section' => $section]))->assertOk()->assertSee('aria-label="Médicos y Especialidades"', false)
                ->assertSee(route('unit.dashboard', ['unit' => $unit->id, 'section' => 'doctors']))
                ->assertSee(route('unit.dashboard', ['unit' => $unit->id, 'section' => 'specialties']));
        }
    }
}
