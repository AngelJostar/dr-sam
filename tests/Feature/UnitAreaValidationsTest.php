<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Institution;
use App\Models\MedicalUnit;
use App\Models\MedicationCatalogItem;
use App\Models\Service;
use App\Models\User;
use App\Support\UnitAreaCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnitAreaValidationsTest extends TestCase
{
    use RefreshDatabase;

    private function unit(): MedicalUnit
    {
        $user = User::query()->create(['name' => 'Responsable Unidad', 'username' => 'rules.unit', 'role' => 'unit', 'status' => 'active']);
        $this->actingAs($user);

        return MedicalUnit::query()->create(['name' => 'Hospital propio', 'unit_username' => $user->username, 'metadata' => ['profile' => ['public_name' => 'Nombre conservado']]]);
    }

    private function rule(): array
    {
        return ['requester_area' => 'pediatric-icu', 'scope' => 'all', 'review_areas' => ['inpatient-pharmacy'], 'authorize_areas' => ['hospitalization'], 'status' => 'draft', 'notes' => 'Revisar antes del envío.'];
    }

    public function test_unit_can_save_edit_and_audit_rules_without_losing_other_metadata(): void
    {
        $unit = $this->unit();
        $service = Service::query()->create(['name' => 'Nutrición parenteral']);
        $data = array_merge($this->rule(), ['scope' => 'service', 'service_id' => $service->id]);
        $this->post(route('unit.area-validations.store', ['unit' => $unit->id]), $data)->assertRedirect()->assertSessionHasNoErrors();
        $saved = $unit->fresh()->metadata['area_validation_rules'][0];
        $this->assertSame('Nutrición parenteral', $saved['scope_label']);
        $this->assertSame(1, $saved['version']);
        $this->assertSame('Nombre conservado', $unit->fresh()->metadata['profile']['public_name']);

        $this->put(route('unit.area-validations.update', ['unit' => $unit->id, 'ruleId' => $saved['id']]), array_merge($data, ['version' => 1, 'status' => 'active']))->assertRedirect()->assertSessionHasNoErrors();
        $updated = $unit->fresh()->metadata['area_validation_rules'][0];
        $this->assertSame(2, $updated['version']);
        $this->assertSame('active', $updated['status']);
        $this->assertDatabaseCount('audit_logs', 2);
        $this->assertSame('draft', data_get(AuditLog::query()->latest('id')->first()->payload, 'context.before.status'));
        $this->get(route('unit.dashboard', ['unit' => $unit->id, 'section' => 'procedure-areas', 'workspace' => 'validations']))
            ->assertOk()->assertSee('Validaciones por área operativa')->assertSee('Nutrición parenteral')->assertSee('Responsable Unidad')->assertSee('data-validation-edit="'.$saved['id'].'"', false);

        $this->put(route('unit.area-validations.update', ['unit' => $unit->id, 'ruleId' => $saved['id']]), array_merge($data, ['version' => 1, 'status' => 'inactive']))->assertSessionHasErrors('version');
        $this->assertSame('active', $unit->fresh()->metadata['area_validation_rules'][0]['status']);
        $this->assertDatabaseCount('audit_logs', 2);
    }

    public function test_rules_are_scoped_to_the_unit_and_only_unit_roles_can_change_them(): void
    {
        $unit = $this->unit();
        $other = MedicalUnit::query()->create(['name' => 'Hospital ajeno', 'metadata' => ['area_validation_rules' => [array_merge($this->rule(), ['id' => 'foreign-rule', 'version' => 1, 'scope_label' => 'REGLA AJENA'])]]]);
        $this->post(route('unit.area-validations.store', ['unit' => $other->id]), $this->rule())->assertForbidden();
        $this->put(route('unit.area-validations.update', ['unit' => $unit->id, 'ruleId' => 'foreign-rule']), $this->rule() + ['version' => 1])->assertNotFound();
        $this->get(route('unit.dashboard', ['unit' => $other->id, 'section' => 'procedure-areas', 'workspace' => 'validations']))->assertForbidden();
        $this->get(route('unit.dashboard', ['unit' => $unit->id, 'section' => 'procedure-areas']))->assertOk()->assertDontSee('REGLA AJENA');
        $this->actingAs(User::query()->create(['name' => 'Operador', 'username' => 'rules.operator', 'role' => 'operational', 'status' => 'active']));
        $this->post(route('unit.area-validations.store', ['unit' => $unit->id]), $this->rule())->assertForbidden();
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_invalid_or_incomplete_active_requirements_cannot_be_saved(): void
    {
        $unit = $this->unit();
        $url = route('unit.area-validations.store', ['unit' => $unit->id]);
        $this->post($url, array_merge($this->rule(), ['requester_area' => 'unknown']))->assertSessionHasErrors('requester_area');
        $this->post($url, array_merge($this->rule(), ['review_areas' => ['pediatric-icu']]))->assertSessionHasErrors('review_areas.0');
        $this->post($url, array_merge($this->rule(), ['review_areas' => [], 'authorize_areas' => [], 'status' => 'active']))->assertSessionHasErrors('review_areas');
        $this->post($url, array_merge($this->rule(), ['scope' => 'service']))->assertSessionHasErrors('service_id');
        $institution = Institution::query()->create(['name' => 'Otra institución']);
        $product = MedicationCatalogItem::query()->create(['name' => 'Producto ajeno', 'institution_id' => $institution->id]);
        $this->post($url, array_merge($this->rule(), ['scope' => 'product', 'product_id' => $product->id]))->assertSessionHasErrors('product_id');
        $this->assertArrayNotHasKey('area_validation_rules', $unit->fresh()->metadata);
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_all_unit_areas_have_an_unconfigured_row_and_validation_navigation(): void
    {
        $unit = $this->unit();
        $page = $this->get(route('unit.dashboard', ['unit' => $unit->id, 'section' => 'procedure-areas']))->assertOk()
            ->assertSee('data-area-workspace="validations"', false)->assertSee('Sin configurar')->assertSee('Historial de cambios');
        foreach (UnitAreaCatalog::navigation() as $key => $area) {
            $page->assertSee('data-validation-area="'.$key.'"', false)->assertSee($area['label']);
        }
    }
}
