<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\MedicalUnit;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\ProviderRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SharedUnitPatientCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_both_catalogs_share_all_unit_links_without_truncation_or_other_units(): void
    {
        $user = User::query()->create(['name' => 'Unidad', 'username' => 'shared.patients', 'role' => 'unit', 'status' => 'active']);
        $unit = MedicalUnit::query()->create(['name' => 'Hospital propio', 'unit_username' => $user->username]);
        $other = MedicalUnit::query()->create(['name' => 'Hospital ajeno']);
        $linked = collect();
        for ($i = 1; $i <= 35; $i++) {
            $linked->push(Patient::query()->create(['full_name' => sprintf('Alta %02d', $i), 'metadata' => ['medical_unit_id' => $unit->id], 'status' => $i === 35 ? 'inactive' : 'active']));
        }
        $appointmentPatient = Patient::query()->create(['full_name' => 'Paciente con cita']);
        Appointment::query()->create(['patient_id' => $appointmentPatient->id, 'medical_unit_id' => $unit->id, 'specialty' => 'Pediatría', 'starts_at' => now()]);
        $requestPatient = Patient::query()->create(['full_name' => 'Paciente con mezcla']);
        ProviderRequest::query()->create(['patient_id' => $requestPatient->id, 'medical_unit_id' => $unit->id, 'request_type' => 'npt']);
        $prescriptionPatient = Patient::query()->create(['full_name' => 'Paciente con receta']);
        Prescription::query()->create(['patient_id' => $prescriptionPatient->id, 'metadata' => ['medical_unit_id' => $unit->id], 'status' => 'inactive']);
        $linked->push($appointmentPatient, $requestPatient, $prescriptionPatient);
        $doctor = Doctor::query()->create(['full_name' => 'Médico propio', 'medical_unit_id' => $unit->id]);
        $foreign = Patient::query()->create(['full_name' => 'Paciente fuera de unidad', 'metadata' => ['medical_unit_id' => $other->id]]);
        Prescription::query()->create(['patient_id' => $foreign->id, 'doctor_id' => $doctor->id, 'metadata' => ['medical_unit_id' => $other->id]]);
        ProviderRequest::query()->create(['patient_id' => $appointmentPatient->id, 'medical_unit_id' => $unit->id, 'request_type' => 'chemo']);
        $this->actingAs($user);
        foreach (['unit.dashboard' => ['section' => 'patients'], 'operational.patients.index' => []] as $route => $parameters) {
            $html = $this->get(route($route, ['unit' => $unit->id] + $parameters))->assertOk()->assertDontSee('Paciente fuera de unidad')->getContent();
            preg_match_all('/data-patient-id="(\d+)"/', $html, $matches);
            $this->assertEqualsCanonicalizing($linked->pluck('id')->all(), array_map('intval', $matches[1]));
            $this->assertCount(38, $matches[1]);
        }
        $this->get(route('operational.patients.index', ['unit' => $unit->id, 'patient_status' => 'inactive']))->assertOk()->assertSee('Alta 35')->assertDontSee('Alta 01');
        $this->get(route('operational.patients.index', ['unit' => $unit->id, 'edit_patient' => $requestPatient->id]))->assertOk()->assertSee('Editar paciente');
    }

    public function test_create_and_edit_from_either_workspace_update_one_record_and_preserve_scope(): void
    {
        $user = User::query()->create(['name' => 'Unidad', 'username' => 'patient.editor', 'role' => 'unit', 'status' => 'active']);
        $unit = MedicalUnit::query()->create(['name' => 'Hospital propio', 'unit_username' => $user->username]);
        $other = MedicalUnit::query()->create(['name' => 'Hospital ajeno']);
        $this->actingAs($user);
        $data = ['unit' => $unit->id, '_patient_return' => 'unit', 'first_name' => 'Paciente', 'last_name' => 'Compartido', 'curp' => 'TEST000101HDFABC01', 'platform_number' => 'SHARED-001', 'phone' => '5551234567', 'nss_federal' => '123456', 'status' => 'active'];
        $this->post(route('operational.patients.store'), $data)->assertSessionHasNoErrors()->assertRedirect(route('unit.dashboard', ['unit' => $unit->id, 'section' => 'patients']));
        $patient = Patient::query()->sole();
        $this->get(route('operational.patients.index', ['unit' => $unit->id]))->assertOk()->assertSee('SHARED-001')->assertSee('123456');
        $this->patch(route('operational.patients.update', $patient), ['unit' => $unit->id, 'first_name' => 'Paciente', 'last_name' => 'Actualizado', 'status' => 'inactive'])
            ->assertSessionHasNoErrors()->assertRedirect(route('operational.patients.index', ['unit' => $unit->id]));
        $this->get(route('unit.dashboard', ['unit' => $unit->id, 'section' => 'patients']))->assertOk()->assertSee('Paciente Actualizado')->assertSee('SHARED-001')->assertSee('Inactivo');
        $this->assertSame('5551234567', $patient->fresh()->phone);
        $this->assertSame('123456', data_get($patient->fresh()->metadata, 'nss_federal'));
        $this->post(route('operational.patients.store'), $data)->assertSessionHasErrors(['curp', 'platform_number']);
        $this->assertDatabaseCount('patients', 1);

        $foreign = Patient::query()->create(['full_name' => 'Ajeno', 'metadata' => ['medical_unit_id' => $other->id, 'source' => 'original']]);
        foreach (['unit.dashboard' => ['section' => 'patients'], 'operational.patients.index' => []] as $route => $parameters) {
            $this->get(route($route, ['unit' => $unit->id, 'edit_patient' => $foreign->id] + $parameters))->assertNotFound();
        }
        $this->patch(route('operational.patients.update', $foreign), $data)->assertNotFound();
        $this->patch(route('outpatient.patients.update', $foreign), $data)->assertNotFound();
        Appointment::query()->create(['patient_id' => $foreign->id, 'medical_unit_id' => $unit->id]);
        $this->patch(route('operational.patients.update', $foreign), ['unit' => $unit->id, 'first_name' => 'Compartido', 'last_name' => 'Entre unidades'])->assertSessionHasNoErrors();
        $this->assertEquals($other->id, data_get($foreign->fresh()->metadata, 'medical_unit_id'));
        $this->assertSame('original', data_get($foreign->fresh()->metadata, 'source'));
    }

    public function test_carousel_order_is_inherited_by_operational_navigation_and_care_buttons_work(): void
    {
        $user = User::query()->create(['name' => 'Admin', 'username' => 'navigation.admin', 'role' => 'admin', 'status' => 'active']);
        $unit = MedicalUnit::query()->create(['name' => 'Hospital']);
        config(['unit_areas' => array_reverse(config('unit_areas'), true)]);
        $this->actingAs($user);
        $unitHtml = $this->get(route('unit.dashboard', ['unit' => $unit->id, 'section' => 'procedure-areas']))->assertOk()->getContent();
        preg_match('/<section[^>]*data-unit-carousel-menu="procedure-areas"[^>]*>.*?<\/section>/s', $unitHtml, $carousel);
        preg_match_all('/data-unit-carousel-filter="([^\"]+)"/', $carousel[0], $filters);
        $expected = \App\Support\UnitAreaCatalog::navigation();
        $this->assertSame(['all', ...array_keys($expected)], $filters[1]);
        foreach (['uci' => 'UCI', 'uti' => 'UTI', 'recovery' => 'Recuperación'] as $type => $label) {
            $html = $this->get(route('operational.areas.show', ['type' => $type, 'unit' => $unit->id]))->assertOk()->assertSee('Registro de entrada')->assertSee('Camas')->assertSee('Central de mezclas (Nutrición Parenteral)')->getContent();
            preg_match_all('/class="operational-area-label">([^<]+)</', $html, $menu);
            $this->assertSame([...array_column($expected, 'label'), 'Catálogo de pacientes'], array_map('html_entity_decode', $menu[1]));
            foreach (['entry', 'beds', 'nutrition'] as $section) {
                $this->get(route('operational.areas.show', ['type' => $type, 'unit' => $unit->id, 'section' => $section]))->assertOk()->assertSee($label);
            }
        }
    }
}
