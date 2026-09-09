<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Platform\DashboardRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_user_can_enter_without_password_when_review_mode_is_enabled(): void
    {
        config(['drsam.review_passwordless' => true]);

        User::query()->create([
            'name' => 'Paciente Demo',
            'username' => 'paciente',
            'email' => 'paciente@demo.drsam.local',
            'role' => 'patient',
            'module' => 'patient',
            'status' => 'active',
            'passwordless_review' => true,
        ]);

        $response = $this->post('/demo-login', [
            'username' => 'paciente',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }

    public function test_passwordless_review_does_not_grant_patient_access_to_every_module(): void
    {
        config(['drsam.review_passwordless' => true]);

        $patient = User::query()->create([
            'name' => 'Paciente Demo',
            'username' => 'paciente',
            'email' => 'paciente@demo.drsam.local',
            'role' => 'patient',
            'module' => 'patient',
            'status' => 'active',
            'passwordless_review' => true,
        ]);

        $modules = app(DashboardRegistry::class)->modulesFor($patient);

        $this->assertSame(['patient'], $modules->pluck('key')->all());
    }

    public function test_superadmin_can_still_review_every_module(): void
    {
        $superadmin = User::query()->create([
            'name' => 'Superadministrador',
            'username' => 'superadmin',
            'email' => 'superadmin@demo.drsam.local',
            'role' => 'superadmin',
            'module' => 'superadmin',
            'status' => 'active',
        ]);

        $modules = app(DashboardRegistry::class)->modulesFor($superadmin);

        $this->assertCount(count(config('drsam.modules')), $modules);
        $this->assertSame('high', $modules->firstWhere('key', 'provider_npt')['priority']);
        $this->assertSame('high', $modules->firstWhere('key', 'messenger')['priority']);
        $this->assertSame('medium', $modules->firstWhere('key', 'insurance_health')['priority']);
        $this->assertNull($modules->firstWhere('key', 'provider_chemo'));
        $this->assertNull($modules->firstWhere('key', 'provider_clinical_labs')['priority']);

        $this->actingAs($superadmin)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Modulo operativo')
            ->assertSee('Acceder')
            ->assertSee('Prioridad alta')
            ->assertSee('Prioridad media')
            ->assertSee('module-card-priority-high', false)
            ->assertSee('module-card-priority-medium', false)
            ->assertDontSee('Proveedor quimioterapias')
            ->assertSee(route('operational.dashboard'));
    }

    public function test_general_admin_can_review_every_module(): void
    {
        $admin = User::query()->create([
            'name' => 'Administración General',
            'username' => 'admin',
            'email' => 'admin@demo.drsam.local',
            'role' => 'admin',
            'module' => 'institution',
            'status' => 'active',
        ]);

        $modules = app(DashboardRegistry::class)->modulesFor($admin);

        $this->assertCount(count(config('drsam.modules')), $modules);
    }

    public function test_insurance_admin_only_sees_the_insurance_flow(): void
    {
        $insuranceAdmin = User::query()->create([
            'name' => 'Administrador Aseguradora',
            'username' => 'aseguradora.admin',
            'email' => 'aseguradora.admin@demo.drsam.local',
            'role' => 'insurance_admin',
            'module' => 'insurance_health',
            'status' => 'active',
        ]);

        $modules = app(DashboardRegistry::class)->modulesFor($insuranceAdmin);

        $this->assertSame(['insurance_health'], $modules->pluck('key')->all());
    }

    public function test_role_module_matrix_matches_the_approved_assignment(): void
    {
        $matrix = [
            'institution' => ['institution', 'unit', 'operational', 'operational_outpatient'],
            'unit' => ['unit', 'operational', 'operational_outpatient'],
            'operational' => ['operational', 'operational_outpatient', 'external_pharmacy', 'digital_pharmacy', 'orders'],
            'provider' => ['provider_npt', 'provider_import', 'provider_medicines', 'provider_clinical_labs'],
            'messenger' => ['messenger'],
            'doctor' => ['doctor'],
            'patient' => ['patient'],
            'insurance_advisor' => ['insurance_health', 'insurance_advisor'],
            'insurance_admin' => ['insurance_health'],
            'medical_auditor' => ['insurance_health'],
            'patient_coordinator' => ['insurance_health'],
            'delivery_coordinator' => ['insurance_health'],
            'hospital_coordinator' => ['insurance_health'],
            'billing' => ['insurance_health'],
            'read_only' => ['insurance_health'],
        ];

        foreach ($matrix as $role => $expectedModules) {
            $user = User::query()->create([
                'name' => 'Usuario '.$role,
                'username' => 'matrix.'.$role,
                'email' => 'matrix.'.$role.'@demo.drsam.local',
                'role' => $role,
                'module' => $expectedModules[0],
                'status' => 'active',
            ]);

            $actualModules = app(DashboardRegistry::class)->modulesFor($user)->pluck('key')->all();

            $this->assertSame($expectedModules, $actualModules, "La asignación del rol {$role} no coincide.");
        }
    }

    public function test_login_page_is_never_cached_by_the_browser(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-cache', (string) $response->headers->get('Cache-Control'));
        $response->assertHeader('Pragma', 'no-cache');
    }
}
