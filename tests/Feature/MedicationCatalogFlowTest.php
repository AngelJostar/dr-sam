<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Models\MedicalUnit;
use App\Models\MedicationCatalogItem;
use App\Models\UnitMedicationSetting;
use App\Models\User;
use Database\Seeders\DrSamDemoSeeder;
use Database\Seeders\ImssBienestarMedicationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MedicationCatalogFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_official_catalog_is_visible_and_unit_activation_is_independent(): void
    {
        $this->seed(DrSamDemoSeeder::class);

        $institution = Institution::query()->where('external_id', 'inst-portal')->firstOrFail();
        $unit = MedicalUnit::query()->where('external_id', 'demo-hospital-general-dr-sam')->firstOrFail();
        $medication = MedicationCatalogItem::query()
            ->where('institution_id', $institution->id)
            ->where('cnis', '010.000.0104.00')
            ->firstOrFail();
        $this->assertSame(203, MedicationCatalogItem::query()->where('institution_id', $institution->id)->count());
        $this->assertSame(203, MedicationCatalogItem::query()->where('institution_id', $institution->id)->get()
            ->filter(fn ($item) => data_get($item->metadata, 'source') === ImssBienestarMedicationSeeder::SOURCE_URL)->count());
        $this->assertSame(203, UnitMedicationSetting::query()->where('medical_unit_id', $unit->id)->count());
        $this->assertDatabaseMissing('medication_catalog_items', ['institution_id' => $institution->id, 'cnis' => '060.004.0109']);

        $this->actingAs(User::query()->where('username', 'institucion')->firstOrFail())
            ->get(route('institution.dashboard', ['institution' => $institution->id, 'section' => 'medications']))
            ->assertOk()
            ->assertSee('Fuente oficial');

        $unitResponse = $this->actingAs(User::query()->where('username', 'unidad.demo')->firstOrFail())
            ->get(route('unit.dashboard', ['unit' => $unit->id, 'section' => 'medications']));
        $unitResponse
            ->assertOk()
            ->assertSee('data-medication-toggle', false)
            ->assertSee('data-medication-filter-trigger="product"', false)
            ->assertSee('data-medication-filter-trigger="presentation"', false)
            ->assertSee('data-medication-filter-trigger="group"', false)
            ->assertSee('data-medication-filter-popover', false)
            ->assertDontSee('data-medication-pages', false)
            ->assertDontSee('pageSize = 20', false)
            ->assertSee('data-medication-value="Activo"', false);
        $this->assertSame(203, substr_count($unitResponse->getContent(), '<tr data-medication-row'));

        $this->patch(route('unit.medications.status', $medication), ['is_active' => '0'])
            ->assertRedirect(route('unit.dashboard', ['unit' => $unit->id, 'section' => 'medications']))
            ->assertSessionMissing('status');
        $this->withSession(['status' => 'Medicamento desactivado para la unidad.'])
            ->get(route('unit.dashboard', ['unit' => $unit->id, 'section' => 'medications']))
            ->assertOk()
            ->assertDontSee('Medicamento desactivado para la unidad.');
        $this->assertDatabaseHas('unit_medication_settings', [
            'medical_unit_id' => $unit->id,
            'medication_catalog_item_id' => $medication->id,
            'is_active' => false,
        ]);
        $this->assertSame('active', $medication->fresh()->status);

        $this->seed(ImssBienestarMedicationSeeder::class);
        $this->assertFalse(UnitMedicationSetting::query()
            ->where('medical_unit_id', $unit->id)
            ->where('medication_catalog_item_id', $medication->id)
            ->firstOrFail()->is_active);

        $this->patch(route('unit.medications.status', $medication), ['is_active' => '1'])
            ->assertRedirect(route('unit.dashboard', ['unit' => $unit->id, 'section' => 'medications']));
        $this->assertTrue(UnitMedicationSetting::query()
            ->where('medical_unit_id', $unit->id)
            ->where('medication_catalog_item_id', $medication->id)
            ->firstOrFail()->is_active);

        $this->withHeaders(['Accept' => 'application/json'])
            ->post(route('unit.medications.status', ['medication' => $medication, 'unit' => $unit->id]), [
                '_method' => 'PATCH',
                'is_active' => '0',
            ])
            ->assertOk()
            ->assertJsonPath('is_active', false)
            ->assertJsonMissingPath('message');
        $this->assertFalse(UnitMedicationSetting::query()
            ->where('medical_unit_id', $unit->id)
            ->where('medication_catalog_item_id', $medication->id)
            ->firstOrFail()->is_active);

        $medication->update(['status' => 'inactive']);
        $this->patch(route('unit.medications.status', $medication), ['is_active' => '1'])->assertStatus(422);

        $otherInstitution = Institution::query()->create([
            'external_id' => 'other-institution',
            'name' => 'Otra institución',
            'status' => 'active',
        ]);
        $otherMedication = MedicationCatalogItem::query()->create([
            'institution_id' => $otherInstitution->id,
            'external_id' => 'other-medication',
            'cnis' => '010.000.9999.00',
            'name' => 'Medicamento de otra institución',
            'generic_name' => 'Medicamento de otra institución',
            'status' => 'active',
        ]);
        $this->patch(route('unit.medications.status', $otherMedication), ['is_active' => '0'])->assertForbidden();
        $this->assertDatabaseMissing('unit_medication_settings', [
            'medical_unit_id' => $unit->id,
            'medication_catalog_item_id' => $otherMedication->id,
        ]);
    }
}
