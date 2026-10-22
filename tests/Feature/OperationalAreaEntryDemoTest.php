<?php

namespace Tests\Feature;

use App\Models\Hospitalization;
use App\Models\MedicalUnit;
use App\Models\Patient;
use App\Models\User;
use Database\Seeders\OperationalAreaEntryDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperationalAreaEntryDemoTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_entries_are_persistent_repeatable_and_linked_to_both_patient_catalogs(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 21)->setTime(12, 0));
        $user = User::query()->create(['name' => 'Unidad', 'username' => 'entries.demo', 'role' => 'unit', 'status' => 'active']);
        $unit = MedicalUnit::query()->create(['external_id' => 'demo-hospital-general-dr-sam', 'name' => 'Hospital Demo', 'unit_username' => $user->username]);
        $this->seed(OperationalAreaEntryDemoSeeder::class);
        $this->assertDatabaseCount('hospitalizations', 12);
        $this->assertDatabaseCount('patients', 12);
        $entry = Hospitalization::query()->firstOrFail();
        $entry->update(['reason' => 'Edición conservada']);
        $entry->patient->update(['full_name' => 'Paciente Demo editado']);
        $this->seed(OperationalAreaEntryDemoSeeder::class);
        $this->assertDatabaseCount('hospitalizations', 12);
        $this->assertDatabaseCount('patients', 12);
        $this->assertSame('Edición conservada', $entry->fresh()->reason);

        $this->actingAs($user);
        foreach (['uci' => 'UCI', 'pediatric-icu' => 'UCIP', 'uti' => 'UTI', 'recovery' => 'REC'] as $type => $code) {
            $response = $this->get(route('operational.areas.show', ['type' => $type, 'unit' => $unit->id, 'section' => 'entry']))
                ->assertOk()->assertSee('DEMO-ING-'.$code.'-01')->assertSee('DEMO-ING-'.$code.'-03')
                ->assertSee('En estancia')->assertSee('Médico Demo de egreso '.$code)
                ->assertSee('data-stay-days="1"', false)->assertSee('data-stay-days="2"', false)->assertSee('data-stay-days="3"', false);
            $this->assertSame(3, substr_count($response->getContent(), 'data-entry-id='));
            if ($type === 'uci') {
                $response->assertSee('Paciente Demo editado')->assertSee('Edición conservada')->assertDontSee('DEMO-ING-UTI-01');
            }
        }
        foreach (['unit.dashboard' => ['section' => 'patients'], 'operational.patients.index' => []] as $route => $query) {
            $response = $this->get(route($route, ['unit' => $unit->id] + $query))->assertOk()->assertSee('Paciente Demo editado');
            $this->assertSame(12, substr_count($response->getContent(), 'data-patient-id='));
        }
    }

    public function test_entry_lists_scope_records_to_unit_and_area_and_keep_empty_states(): void
    {
        $user = User::query()->create(['name' => 'Unidad', 'username' => 'entries.scope', 'role' => 'unit', 'status' => 'active']);
        $unit = MedicalUnit::query()->create(['name' => 'Hospital propio', 'unit_username' => $user->username]);
        $other = MedicalUnit::query()->create(['name' => 'Hospital ajeno']);
        $patient = Patient::query()->create(['full_name' => 'Paciente de ingreso']);
        foreach ([[$unit, 'uci', 'Ingreso propio'], [$other, 'uci', 'Ingreso ajeno'], [$unit, 'uti', 'Ingreso de otra área']] as [$hospital, $type, $folio]) {
            Hospitalization::query()->create(['patient_id' => $patient->id, 'admitted_at' => now()->subDays(2),
                'metadata' => ['medical_unit_id' => $hospital->id, 'area_type' => $type, 'folio' => $folio]]);
        }
        $this->actingAs($user);
        $this->get(route('operational.areas.show', ['type' => 'uci', 'unit' => $unit->id, 'section' => 'entry']))
            ->assertOk()->assertSee('Ingreso propio')->assertDontSee('Ingreso ajeno')->assertDontSee('Ingreso de otra área');
        $this->get(route('operational.areas.show', ['type' => 'uci', 'unit' => $other->id, 'section' => 'entry']))->assertForbidden();
        $this->get(route('operational.areas.show', ['type' => 'recovery', 'unit' => $unit->id, 'section' => 'entry']))
            ->assertOk()->assertSee('Aún no hay registros de entrada en Recuperación.');
    }
}
