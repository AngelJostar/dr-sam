<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Platform\DashboardRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DemoAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_initial_login_requires_password_even_when_review_mode_is_enabled(): void
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

        $response = $this->post('/login', [
            'username' => 'paciente',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertGuest();
    }

    public function test_initial_page_has_credentials_without_user_selector(): void
    {
        $this->get('/')->assertOk()->assertSee('Bienvenido a Klini')
            ->assertSee('name="username"', false)->assertSee('name="password"', false)
            ->assertDontSee('<select', false);
    }

    public function test_superadmin_enters_selector_and_can_then_enter_a_users_panel(): void
    {
        $superadmin = $this->accessUser('superadmin', 'superadmin');
        $patient = $this->accessUser('patient', 'patient');

        $this->post('/login', ['username' => $superadmin->username, 'password' => 'test-password'])
            ->assertRedirect(route('demo-login.index'));
        $this->get('/demo-login')->assertOk()->assertSee($patient->username);
        $this->post('/demo-login', ['username' => $patient->username, 'password' => 'wrong'])
            ->assertSessionHasErrors('username');
        $this->assertAuthenticatedAs($superadmin);
        $this->post('/demo-login', ['username' => $patient->username, 'password' => 'test-password'])
            ->assertRedirect(route('patient.dashboard'));
        $this->assertAuthenticatedAs($patient);
        $this->get('/demo-login')->assertForbidden();
        $this->post('/demo-login', ['username' => $superadmin->username, 'password' => 'test-password'])
            ->assertForbidden();
    }

    public function test_regular_users_enter_their_assigned_authorized_panel(): void
    {
        foreach ([['patient', 'patient', 'patient.dashboard'], ['doctor', 'doctor', 'doctor.dashboard'],
            ['insurance_admin', 'insurance', 'insurance.dashboard'], ['provider', 'provider', 'provider.npt.dashboard'],
            ['operational', 'externalPharmacy', 'external-pharmacy.dashboard']] as [$role, $module, $route]) {
            $user = $this->accessUser($role, $module);
            $this->withSession(['url.intended' => route('superadmin.dashboard')])
                ->post('/login', ['username' => $user->username, 'password' => 'test-password'])
                ->assertRedirect(route($route));
            $this->assertAuthenticatedAs($user);
            $this->post('/logout');
        }
    }

    public function test_guest_cannot_access_selector_or_login_through_it(): void
    {
        $this->get('/demo-login')->assertRedirect(route('login'));
        $this->post('/demo-login', ['username' => 'superadmin', 'password' => 'test-password'])
            ->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_invalid_credentials_and_inactive_accounts_cannot_enter(): void
    {
        $user = $this->accessUser('patient', 'patient');
        $this->post('/login', ['username' => $user->username, 'password' => 'wrong'])
            ->assertSessionHasErrors('username');
        $this->assertGuest();
        $user->update(['status' => 'inactive']);
        $this->post('/login', ['username' => $user->username, 'password' => 'test-password'])
            ->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    private function accessUser(string $role, string $module): User
    {
        return User::query()->create([
            'name' => 'Test '.$role, 'username' => 'test.'.$role, 'email' => $role.'@test.local',
            'password' => Hash::make('test-password'), 'role' => $role, 'module' => $module, 'status' => 'active',
        ]);
    }

    public function test_selector_only_supplies_verified_demo_passwords(): void
    {
        $superadmin = $this->accessUser('superadmin', 'superadmin');
        $demo = $this->accessUser('patient', 'patient');
        $demo->update(['is_demo' => true, 'password' => Hash::make('Demo2026')]);
        $changed = $this->accessUser('doctor', 'doctor');
        $changed->update(['is_demo' => true]);
        $regular = $this->accessUser('provider', 'provider');
        $regular->update(['password' => Hash::make('Demo2026')]);

        $this->actingAs($superadmin)->get('/demo-login')->assertOk()
            ->assertViewHas('demoPasswords', fn ($passwords) =>
                $passwords[$demo->username] === 'Demo2026'
                && $passwords[$changed->username] === ''
                && $passwords[$regular->username] === '')
            ->assertSee('id="demo-password" type="text"', false);
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
            ->assertSee('Cerrar sesión')
            ->assertSee('action="'.route('logout').'"', false)
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
