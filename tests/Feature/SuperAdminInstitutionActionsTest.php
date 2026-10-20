<?php

namespace Tests\Feature;

use App\Models\ContractedService;
use App\Models\Institution;
use App\Models\MedicalUnit;
use App\Models\MedicationCatalogItem;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminInstitutionActionsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::query()->create(['name' => 'Admin', 'username' => 'institution.actions.admin', 'role' => 'superadmin', 'status' => 'active']);
    }

    public function test_catalog_has_five_separate_action_columns_with_the_correct_destinations(): void
    {
        $institution = Institution::query()->create(['name' => 'Institución de prueba', 'status' => 'active']);
        MedicalUnit::query()->create(['name' => 'Hospital de prueba', 'institution_id' => $institution->id]);
        $response = $this->actingAs($this->admin())->get(route('superadmin.catalog', 'institutions'))->assertOk();
        $document = new \DOMDocument;
        @$document->loadHTML('<?xml encoding="utf-8" ?>'.$response->getContent());
        $xpath = new \DOMXPath($document);
        $headers = $xpath->query('//table[contains(@class, "superadmin-native-catalog-table")]/thead/tr/th');
        $this->assertCount(12, $headers);
        $this->assertSame(['Hospitales', 'Bloquear', 'Reporte', 'Editar', 'Eliminar'], array_slice(array_map(fn ($node) => trim($node->textContent), iterator_to_array($headers)), -5));
        $cells = $xpath->query('//tr[@data-institution-row]/td');
        $this->assertCount(12, $cells);
        $this->assertSame(route('institution.dashboard', ['institution' => $institution->id, 'section' => 'units']), $xpath->query('.//a', $cells->item(7))->item(0)->getAttribute('href'));
        $this->assertSame('1', $xpath->query('.//span', $cells->item(7))->item(0)->textContent);
        $this->assertSame(route('superadmin.institutions.status', $institution), $xpath->query('.//form', $cells->item(8))->item(0)->getAttribute('action'));
        $this->assertSame(route('superadmin.institutions.report', $institution), $xpath->query('.//a', $cells->item(9))->item(0)->getAttribute('href'));
        $this->assertSame('#edit-institution-'.$institution->id, $xpath->query('.//a', $cells->item(10))->item(0)->getAttribute('href'));
        $this->assertSame(route('superadmin.institutions.destroy', $institution), $xpath->query('.//form[@data-institution-delete]', $cells->item(11))->item(0)->getAttribute('action'));
    }

    public function test_block_and_unblock_only_change_status_for_the_institution_and_its_owner(): void
    {
        $owner = User::query()->create(['name' => 'Responsable', 'username' => 'institution.actions.owner', 'role' => 'institution', 'status' => 'active']);
        $institution = Institution::query()->create(['name' => 'Nombre conservado', 'legal_name' => 'Razón social conservada', 'owner_user_id' => $owner->id]);
        $url = route('superadmin.catalog', ['section' => 'institutions', 'status' => 'all']);
        $this->actingAs($this->admin());
        foreach (['inactive', 'active'] as $status) {
            $this->from($url)->patch(route('superadmin.institutions.status', $institution), ['status' => $status, 'name' => 'No aplicar'])
                ->assertRedirect($url)->assertSessionHas('status');
            $this->assertSame($status, $institution->fresh()->status);
            $this->assertSame($status, $owner->fresh()->status);
            $this->assertSame('Nombre conservado', $institution->fresh()->name);
            $this->assertSame('Razón social conservada', $institution->fresh()->legal_name);
        }
        $this->patch(route('superadmin.institutions.status', $institution), ['status' => 'invalid'])->assertSessionHasErrors('status');
        $this->assertDatabaseHas('audit_logs', ['event' => 'superadmin.institution.status.updated', 'auditable_id' => $institution->id]);
    }

    public function test_report_contains_only_the_selected_institution_and_escapes_spreadsheet_formulas(): void
    {
        $institution = Institution::query()->create(['name' => '=SUM(1,2)', 'legal_name' => 'Razón social', 'type' => 'publica']);
        Institution::query()->create(['name' => 'Otra institución']);
        MedicalUnit::query()->create(['name' => 'Hospital', 'institution_id' => $institution->id]);
        ContractedService::query()->create(['institution_id' => $institution->id]);
        $response = $this->actingAs($this->admin())->get(route('superadmin.institutions.report', $institution))->assertOk();
        $this->assertStringContainsString('attachment;', $response->headers->get('Content-Disposition'));
        $csv = $response->streamedContent();
        $this->assertStringNotContainsString('Otra institución', $csv);
        $lines = preg_split('/\r?\n/', trim(substr($csv, 3)));
        $this->assertCount(2, $lines);
        $row = str_getcsv($lines[1]);
        $this->assertCount(8, $row);
        $this->assertSame("'=SUM(1,2)", $row[0]);
        $this->assertSame(['Pública', 'Activo', '1', '1', '0'], array_slice($row, 2, 5));
    }

    public function test_delete_requires_confirmation_and_preserves_the_owner_account(): void
    {
        $owner = User::query()->create(['name' => 'Responsable', 'username' => 'institution.actions.owner', 'role' => 'institution', 'status' => 'active']);
        $institution = Institution::query()->create(['name' => 'Institución vacía', 'owner_user_id' => $owner->id]);
        $other = Institution::query()->create(['name' => 'Conservar']);
        $this->actingAs($this->admin())->delete(route('superadmin.institutions.destroy', $institution))->assertSessionHasErrors('confirm_delete');
        $this->assertModelExists($institution);
        $this->delete(route('superadmin.institutions.destroy', $institution), ['confirm_delete' => 1])
            ->assertRedirect(route('superadmin.catalog', ['section' => 'institutions']))->assertSessionHas('status');
        $this->assertModelMissing($institution);
        $this->assertModelExists($other);
        $this->assertModelExists($owner);
        $this->assertSame('inactive', $owner->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['event' => 'superadmin.institution.deleted', 'auditable_id' => $institution->id]);
    }

    public function test_delete_preserves_institutions_with_linked_records(): void
    {
        $this->actingAs($this->admin());
        $fixtures = [
            fn ($id) => MedicalUnit::query()->create(['name' => 'Hospital', 'institution_id' => $id]),
            fn ($id) => ContractedService::query()->create(['institution_id' => $id]),
            fn ($id) => MedicationCatalogItem::query()->create(['name' => 'Medicamento', 'institution_id' => $id]),
            fn ($id) => Service::query()->create(['name' => 'Especialidad', 'metadata' => ['institution_ids' => [$id]]]),
            fn ($id) => Service::query()->create(['name' => 'Especialidad', 'metadata' => ['institution_ids' => [(string) $id]]]),
            fn ($id) => User::query()->create(['name' => 'Usuario', 'username' => 'institution.actions.linked', 'role' => 'institution', 'metadata' => ['institution_id' => $id]]),
        ];
        foreach ($fixtures as $createLinkedRecord) {
            $institution = Institution::query()->create(['name' => 'Con vínculos']);
            $related = $createLinkedRecord($institution->id);
            $this->delete(route('superadmin.institutions.destroy', $institution), ['confirm_delete' => 1])->assertSessionHasErrors('institution');
            $this->assertModelExists($institution);
            $this->assertModelExists($related);
        }
    }

    public function test_non_superadmin_cannot_use_institution_actions(): void
    {
        $owner = User::query()->create(['name' => 'Responsable', 'username' => 'institution.actions.owner', 'role' => 'institution', 'status' => 'active']);
        $institution = Institution::query()->create(['name' => 'Institución', 'owner_user_id' => $owner->id]);
        $this->actingAs($owner);
        $this->patch(route('superadmin.institutions.status', $institution), ['status' => 'inactive'])->assertForbidden();
        $this->get(route('superadmin.institutions.report', $institution))->assertForbidden();
        $this->delete(route('superadmin.institutions.destroy', $institution), ['confirm_delete' => 1])->assertForbidden();
        $this->assertModelExists($institution);
        $this->assertSame('active', $institution->fresh()->status);
    }
}
