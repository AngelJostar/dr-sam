<?php

namespace Tests\Feature;

use App\Models\{AuditLog, InsuranceCarrier, InsurancePlan, Patient, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InsurancePlanCatalogTest extends TestCase
{
    use RefreshDatabase;

    private function account(string $role): User
    {
        return User::query()->create(['name' => $role, 'username' => $role.'.plans', 'email' => $role.'@plans.test', 'role' => $role, 'module' => $role, 'status' => 'active']);
    }

    private function data(array $override = []): array
    {
        return array_replace(['name' => 'Plan de prueba', 'category' => 'gmm', 'status' => 'active', 'is_demo' => true, 'monthly_premium' => 1532.45, 'insured_amount' => 2500000, 'deductible' => 23000, 'coinsurance' => 12, 'details' => ['coverages' => "Hospitalización\nAmbulancia", 'conditions' => 'Condiciones del catálogo', 'exclusions' => 'Exclusiones de prueba', 'teleconsultations' => '6 al año', 'ambulance' => '2 al año', 'addons' => ['dental' => ['name' => 'Dental', 'price' => 120.50]]]], $override);
    }

    public function test_admin_has_three_sections_and_persistent_editable_catalog(): void
    {
        $admin = $this->account('superadmin');
        $this->actingAs($admin)->get(route('superadmin.catalog', 'insurance-carriers'))->assertOk()->assertSee('Aseguradoras')->assertSee('Seguros de gastos médicos mayores')->assertSee('Seguros de vida');
        foreach (['gmm', 'life'] as $category) {
            $this->get(route('superadmin.catalog', ['section' => 'insurance-carriers', 'view' => $category]))->assertOk()->assertSee('Vincular a app')->assertSee('Vista previa móvil')->assertSee('Condiciones');
        }
        $this->post(route('superadmin.insurance-plans.store'), $this->data(['is_linked' => true]))->assertRedirect();
        $plan = InsurancePlan::query()->where('name', 'Plan de prueba')->firstOrFail();
        $this->assertFalse($plan->is_linked);
        $this->assertSame('1532.45', $plan->monthly_premium);
        $this->assertSame('6 al año', $plan->details['teleconsultations']);
        $this->patch(route('superadmin.insurance-plans.update', $plan), $this->data(['name' => 'Vida nueva', 'category' => 'life', 'monthly_premium' => null]))->assertRedirect();
        $this->assertSame('life', $plan->fresh()->category);
        $this->assertNull($plan->fresh()->monthly_premium);
        $this->assertDatabaseHas('audit_logs', ['event' => 'superadmin.insurance_plan.updated', 'auditable_id' => $plan->id]);
        $this->postJson(route('superadmin.insurance-plans.store'), $this->data(['coinsurance' => 101, 'is_demo' => false]))->assertUnprocessable();
        $this->assertSame(1, InsurancePlan::query()->where('name', 'Vida nueva')->count());
    }

    public function test_only_linked_active_plans_reach_patient_and_mobile_feed(): void
    {
        $admin = $this->account('superadmin'); $patient = $this->account('patient');
        Patient::query()->create(['user_id' => $patient->id, 'full_name' => 'Paciente seguros', 'platform_number' => 'PLAN-1', 'status' => 'active']);
        $plan = InsurancePlan::query()->create($this->data());
        $this->actingAs($patient)->get(route('patient.insurance-plans.index'))->assertJsonCount(0, 'data.plans');
        $this->get(route('patient.dashboard'))->assertOk()->assertSee('Aún no hay seguros disponibles');
        $this->actingAs($admin)->patch(route('superadmin.insurance-plans.link', $plan), ['is_linked' => true])->assertRedirect();
        $this->actingAs($patient)->get(route('patient.dashboard'))->assertOk()->assertSee('Plan de prueba')->assertSee('1532.45', false)->assertDontSee('Seguros Horizonte');
        $this->get(route('patient.insurance-plans.index'))->assertJsonPath('data.plans.0.details.teleconsultations', '6 al año')->assertJsonCount(1, 'data.plans');
        Sanctum::actingAs($patient, ['mobile:access', 'role:patient']);
        $this->getJson(route('api.mobile.v1.patient.insurance-plans.index'))->assertOk()->assertJsonPath('data.plans.0.price', 1532.45);
        $plan->update(['status' => 'inactive']);
        $this->getJson(route('api.mobile.v1.patient.insurance-plans.index'))->assertJsonCount(0, 'data.plans');
        $plan->update(['status' => 'active', 'insurance_carrier_id' => InsuranceCarrier::query()->create(['name' => 'Inactiva', 'slug' => 'inactive-plans', 'status' => 'inactive'])->id]);
        $this->getJson(route('api.mobile.v1.patient.insurance-plans.index'))->assertJsonCount(0, 'data.plans');
        $this->actingAs($admin)->patch(route('superadmin.insurance-plans.link', $plan), ['is_linked' => true])->assertSessionHasErrors('is_linked');
        $plan->update(['insurance_carrier_id' => null]);
        $this->patch(route('superadmin.insurance-plans.link', $plan), ['is_linked' => false])->assertRedirect();
        $this->actingAs($patient)->get(route('patient.insurance-plans.index'))->assertJsonCount(0, 'data.plans');
    }

    public function test_documents_are_private_and_follow_plan_publication(): void
    {
        Storage::fake('local');
        $admin = $this->account('superadmin'); $patient = $this->account('patient');
        $plan = InsurancePlan::query()->create($this->data());
        $this->actingAs($admin)->post(route('superadmin.insurance-plans.documents.store', $plan), ['documents' => [UploadedFile::fake()->create('condiciones.pdf', 10, 'application/pdf')]])->assertRedirect();
        $doc = $plan->documents()->firstOrFail();
        $this->get(route('patient.insurance-plans.documents', $doc))->assertOk()->assertDownload('condiciones.pdf');
        $this->actingAs($patient)->get(route('patient.insurance-plans.documents', $doc))->assertNotFound();
        $plan->update(['is_linked' => true]);
        $this->get(route('patient.insurance-plans.documents', $doc))->assertDownload('condiciones.pdf');
        Sanctum::actingAs($patient, ['mobile:access', 'role:patient']);
        $this->getJson(route('api.mobile.v1.patient.insurance-plans.index'))->assertJsonPath('data.plans.0.documents.0.url', route('api.mobile.v1.patient.insurance-plans.documents', $doc));
        $this->get(route('api.mobile.v1.patient.insurance-plans.documents', $doc))->assertOk();
        $this->deleteJson(route('superadmin.insurance-plans.documents.destroy', $doc))->assertForbidden();
        $this->actingAs($admin)->delete(route('superadmin.insurance-plans.documents.destroy', $doc))->assertRedirect();
        Storage::disk('local')->assertMissing($doc->path);
        $this->postJson(route('superadmin.insurance-plans.documents.store', $plan), ['documents' => [UploadedFile::fake()->create('script.html', 10, 'text/html')]])->assertUnprocessable();
    }

    public function test_patient_cannot_edit_catalog_and_quote_requires_a_published_plan(): void
    {
        $patientUser = $this->account('patient');
        $patient = Patient::query()->create(['user_id' => $patientUser->id, 'full_name' => 'Paciente', 'platform_number' => 'PLAN-2', 'status' => 'active']);
        $plan = InsurancePlan::query()->create($this->data());
        $this->actingAs($patientUser)->postJson(route('superadmin.insurance-plans.store'), $this->data())->assertForbidden();
        $this->patchJson(route('superadmin.insurance-plans.link', $plan), ['is_linked' => true])->assertForbidden();
        $quote = ['type' => 'quote', 'insurance_plan_id' => $plan->id, 'email' => 'quote@plans.test', 'phone' => '5555550100', 'notes' => 'Solicito información del plan', 'consent' => true];
        $this->postJson(route('patient.insurance.requests.store'), $quote)->assertUnprocessable()->assertJsonValidationErrors('insurance_plan_id');
        $plan->update(['is_linked' => true]);
        $this->postJson(route('patient.insurance.requests.store'), $quote)->assertCreated();
        $this->assertSame($plan->id, data_get($patient->fresh()->metadata, 'insurance_requests.0.insurance_plan_id'));
        $this->assertSame($plan->name, data_get($patient->fresh()->metadata, 'insurance_requests.0.insurance_plan_name'));
    }

    public function test_search_export_and_html_escaping_preserve_plan_data(): void
    {
        $this->actingAs($this->account('superadmin'));
        InsurancePlan::query()->create($this->data(['name' => '=CATALOGUE <script>alert(1)</script>']));
        $url = route('superadmin.catalog', ['section' => 'insurance-carriers', 'view' => 'gmm', 'q' => '=CATALOGUE']);
        $this->get($url)->assertOk()->assertSee('=CATALOGUE &lt;script&gt;', false)->assertDontSee('<script>alert(1)</script>', false)->assertDontSee('Klini Esencial');
        $csv = $this->get(route('superadmin.catalog.csv', ['section' => 'insurance-carriers', 'view' => 'gmm', 'q' => '=CATALOGUE']))->assertOk()->streamedContent();
        $this->assertStringContainsString("'=CATALOGUE", $csv);
        $this->assertStringContainsString('6 al año', $csv);
        $this->assertStringNotContainsString('Klini Esencial', $csv);
    }

    public function test_user_catalog_lists_login_and_password_state_without_exposing_secrets(): void
    {
        $admin = $this->account('superadmin');
        $member = $this->account('patient'); $member->update(['password' => bcrypt('Example-only-123!')]);
        $response = $this->actingAs($admin)->get(route('superadmin.catalog', 'users'))->assertOk();
        $response->assertSeeInOrder(['Usuario Klini', 'No. usuario operativo', 'Usuario del sistema', 'Contraseña']);
        $response->assertSee($admin->username)->assertSee($member->username)->assertSee('Sin contraseña')->assertSee('Protegida')->assertDontSee('Example-only-123!')->assertDontSee($member->fresh()->password);
        $csv = $this->get(route('superadmin.catalog.csv', ['section' => 'users']))->assertOk()->streamedContent();
        $this->assertStringNotContainsString($member->fresh()->password, $csv);
    }
}
