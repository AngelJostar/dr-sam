<?php

namespace Tests\Feature;

use App\Models\PlatformModule;
use App\Models\User;
use App\Services\Platform\DashboardRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommunityModuleTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        return User::query()->create([
            'name' => 'Usuario de prueba', 'username' => 'community.'.$role,
            'email' => $role.'@community.test', 'role' => $role,
            'module' => $role === 'patient' ? 'patient' : 'superadmin', 'status' => 'active',
        ]);
    }

    public function test_community_pages_require_a_session(): void
    {
        $this->get(route('community.dashboard'))->assertRedirect(route('login'));
        $this->get(route('community.explore'))->assertRedirect(route('login'));
    }

    public function test_admin_module_is_registered_and_accessible_from_superadmin(): void
    {
        $this->actingAs($this->user('superadmin'));
        $this->get(route('superadmin.dashboard'))->assertOk()
            ->assertSee('Administrador de Comunidades')->assertSee(route('community.dashboard'));
        $this->assertDatabaseHas('platform_modules', ['key' => 'community_admin', 'enabled' => true]);
        $this->get(route('community.dashboard'))->assertOk()
            ->assertSee('data-community-mode="admin"', false)
            ->assertSee('Espacio de demostración')->assertSee(route('logout'))->assertDontSee('<iframe', false);
    }

    public function test_patient_can_explore_but_cannot_open_admin_module(): void
    {
        $patient = $this->user('patient');
        $this->actingAs($patient)->get(route('community.explore'))->assertOk()
            ->assertSee('data-community-mode="explore"', false);
        $this->get(route('community.dashboard'))->assertForbidden();
        $this->assertFalse(app(DashboardRegistry::class)->modulesFor($patient)->contains('key', 'community_admin'));
    }

    public function test_disabled_admin_module_cannot_be_opened_directly(): void
    {
        PlatformModule::query()->create([
            'key' => 'community_admin', 'label' => 'Administrador de Comunidades',
            'target' => 'community.dashboard', 'enabled' => false, 'roles' => ['superadmin', 'admin'],
        ]);
        $this->actingAs($this->user('superadmin'))->get(route('community.dashboard'))->assertForbidden();
    }

    public function test_unrelated_roles_cannot_open_either_community_view(): void
    {
        $this->actingAs($this->user('provider'));
        $this->get(route('community.dashboard'))->assertForbidden();
        $this->get(route('community.explore'))->assertForbidden();
    }
}
