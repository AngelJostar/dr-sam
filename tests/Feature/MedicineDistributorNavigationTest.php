<?php

namespace Tests\Feature;

use App\Models\Provider;
use App\Models\User;
use App\Services\Platform\DashboardRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MedicineDistributorNavigationTest extends TestCase
{
    use RefreshDatabase;

    private function distributor(): User
    {
        $user = User::query()->create([
            'name' => 'Distribuidor de prueba', 'username' => 'distributor.navigation',
            'email' => 'distributor.navigation@test.local', 'role' => 'provider',
            'module' => 'provider_medicines', 'status' => 'active',
            'password' => bcrypt('Synthetic-navigation-password'),
        ]);
        Provider::query()->create([
            'user_id' => $user->id, 'name' => $user->name,
            'provider_type' => 'medicines', 'status' => 'active',
            'digital_pharmacy_enabled' => false,
        ]);

        return $user;
    }

    public function test_distributor_login_goes_to_catalog_instead_of_an_old_intended_panel(): void
    {
        config(['drsam.review_passwordless' => false]);
        $user = $this->distributor();

        $this->withSession(['url.intended' => route('provider.npt.dashboard')])
            ->post(route('demo-login.store'), [
                'username' => $user->username, 'password' => 'Synthetic-navigation-password',
            ])
            ->assertRedirect(route('provider.pharmacy.products'))
            ->assertSessionMissing('url.intended');
        $this->assertAuthenticatedAs($user);
    }

    public function test_old_dashboard_and_provider_panels_redirect_distributor_to_own_catalog(): void
    {
        $this->actingAs($this->distributor());

        foreach (['dashboard', 'provider.medicines.dashboard', 'provider.npt.dashboard',
            'provider.chemo.dashboard', 'provider.import.dashboard', 'provider.clinical-labs.dashboard'] as $route) {
            $this->get(route($route))->assertRedirect(route('provider.pharmacy.products'));
        }

        $this->get(route('provider.pharmacy.products'))->assertOk()
            ->assertSee('Catálogo propio')->assertSee('Solicitudes y pedidos')
            ->assertDontSee('Panel del distribuidor')->assertDontSee('NPT y Oncologicas')
            ->assertDontSee(route('provider.npt.dashboard'), false)
            ->assertDontSee(route('provider.import.dashboard'), false);
        $this->get(route('provider.pharmacy.orders'))->assertOk();
    }

    public function test_distributor_module_switcher_only_links_to_its_catalog(): void
    {
        $modules = app(DashboardRegistry::class)->modulesFor($this->distributor());

        $this->assertSame(['provider_medicines'], $modules->pluck('key')->all());
        $this->assertSame([route('provider.pharmacy.products')], $modules->pluck('url')->all());
    }

    public function test_other_provider_and_superadmin_keep_their_existing_panels(): void
    {
        foreach (['provider', 'superadmin'] as $role) {
            $user = User::query()->create([
                'name' => 'Otro perfil', 'username' => 'navigation.'.$role,
                'role' => $role, 'module' => $role === 'provider' ? 'provider_npt' : 'superadmin',
                'status' => 'active',
            ]);
            $this->actingAs($user)->get(route('dashboard'))->assertOk();
            $this->get(route('provider.npt.dashboard'))->assertOk()->assertViewIs('provider.dashboard');
            $this->assertContains('provider_npt', app(DashboardRegistry::class)->modulesFor($user)->pluck('key')->all());
        }
    }
}
