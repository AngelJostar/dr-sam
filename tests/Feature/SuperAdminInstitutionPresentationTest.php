<?php

namespace Tests\Feature;

use App\Models\ContractedService;
use App\Models\Institution;
use App\Models\MedicalUnit;
use App\Models\PlatformModule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminInstitutionPresentationTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::query()->create(['name' => 'Admin', 'username' => 'presentation.admin', 'role' => 'superadmin', 'status' => 'active']);
    }

    public function test_selection_preserves_filters_and_shows_the_selected_institution_counts_and_destinations(): void
    {
        $admin = $this->admin();
        $selected = Institution::query()->create(['name' => 'Institución seleccionada de prueba', 'type' => 'institution', 'status' => 'inactive', 'owner_user_id' => $admin->id]);
        $other = Institution::query()->create(['name' => 'Otra institución', 'status' => 'active']);
        MedicalUnit::query()->create(['name' => 'Unidad de prueba', 'institution_id' => $selected->id]);
        ContractedService::query()->create(['institution_id' => $selected->id]);
        ContractedService::query()->create(['institution_id' => $selected->id]);
        $url = route('superadmin.catalog', ['section' => 'institutions', 'status' => 'all', 'selected_institution' => $selected->id]);

        $response = $this->actingAs($admin)->get($url)->assertOk();
        $institution = $response->viewData('selectedInstitution');
        $this->assertSame($selected->id, $institution->id);
        $this->assertSame(1, $institution->medical_units_count);
        $this->assertSame(2, $institution->services_count);
        $response->assertSee('Catálogo de instituciones')->assertSee('Exportar CSV')->assertSee('Institución seleccionada');
        $document = new \DOMDocument;
        @$document->loadHTML('<?xml encoding="utf-8" ?>'.$response->getContent());
        $xpath = new \DOMXPath($document);
        $this->assertStringContainsString($selected->name, $xpath->query('//tr[@class="is-selected-institution"]')->item(0)->textContent);
        $detail = $xpath->query('//*[@id="institution-detail"]')->item(0);
        $this->assertStringContainsString('Inactivo', $detail->textContent);
        $counts = $xpath->query('.//article//strong', $detail);
        $this->assertSame(['1', '2', '1', (string) PlatformModule::query()->where('enabled', true)->count()], array_map(fn ($node) => $node->textContent, iterator_to_array($counts)));
        foreach (['units', 'services'] as $section) {
            $response->assertSee(route('institution.dashboard', ['institution' => $selected->id, 'section' => $section]));
        }
        $response->assertSee(route('superadmin.catalog', ['section' => 'institutions', 'status' => 'all', 'selected_institution' => $other->id]).'#institution-detail');
        $response->assertDontSee('data-admin-feedback-dialog', false);
    }

    public function test_update_confirmation_appears_once_inside_a_closable_dialog(): void
    {
        $admin = $this->admin();
        $institution = Institution::query()->create(['name' => 'Institución de prueba', 'status' => 'active']);
        $url = route('superadmin.catalog', ['section' => 'institutions', 'status' => 'all']);
        $this->actingAs($admin)->from($url)->patch(route('superadmin.institutions.update', $institution), [
            'name' => 'Institución actualizada', 'type' => 'institution', 'status' => 'active',
        ])->assertRedirect($url)->assertSessionHas('status');
        $response = $this->get($url)->assertOk()
            ->assertSee('data-admin-feedback-dialog', false)
            ->assertSee('aria-label="Cerrar mensaje"', false)
            ->assertSee('Institucion Institución actualizada actualizada.')
            ->assertDontSee('class="notice success"', false);
        $this->assertSame(1, substr_count($response->getContent(), 'data-admin-feedback-dialog'));
        $this->assertDatabaseHas('institutions', ['id' => $institution->id, 'name' => 'Institución actualizada']);
    }

    public function test_validation_errors_use_the_dialog_and_do_not_update_the_institution(): void
    {
        $institution = Institution::query()->create(['name' => 'Conservar nombre', 'status' => 'active']);
        $url = route('superadmin.catalog', 'institutions');
        $this->actingAs($this->admin())->from($url)->patch(route('superadmin.institutions.update', $institution), [
            'name' => '', 'type' => 'invalid', 'status' => 'invalid',
        ])->assertSessionHasErrors(['name', 'type', 'status'])->assertRedirect($url);
        $this->get($url)->assertOk()->assertSee('Revisa la información')->assertSee('data-admin-feedback-dialog', false)->assertDontSee('class="notice danger"', false);
        $this->assertSame('Conservar nombre', $institution->fresh()->name);
    }

    public function test_dashboard_feedback_escapes_message_content(): void
    {
        $message = '<script>alert("message")</script>';
        $response = $this->actingAs($this->admin())->withSession(['status' => $message])->get(route('superadmin.dashboard'))->assertOk();
        $response->assertSee($message)->assertDontSee($message, false)->assertSee('data-admin-feedback-close', false)->assertDontSee('class="notice success"', false);
    }
}
