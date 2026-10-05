<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Platform\DashboardRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModulePreferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_assigned_modules_and_home_preference_are_enforced(): void
    {
        $user = User::query()->create(['name' => 'Usuario módulos', 'username' => 'modules-test', 'role' => 'provider', 'module' => 'provider_import', 'status' => 'active', 'metadata' => ['assigned_modules' => ['provider_import', 'unit']]]);
        $this->assertSame(['unit', 'provider_import'], app(DashboardRegistry::class)->modulesFor($user)->pluck('key')->all());
        $this->actingAs($user)->post(route('account.home-module'), ['module' => 'unit'])->assertRedirect();
        $this->assertSame('unit', data_get($user->fresh()->metadata, 'home_module'));
        $this->get(route('login'))->assertRedirect(route('unit.dashboard'));
        $this->post(route('account.home-module'), ['module' => 'superadmin'])->assertForbidden();
        $this->get(route('provider.npt.dashboard'))->assertForbidden();
        $this->get(route('provider.import.dashboard'))->assertOk()->assertSee('data-module-toggle', false);
    }

    public function test_doctor_cannot_change_home_module(): void
    {
        $user = User::query()->create(['name' => 'Médico', 'username' => 'doctor-module-test', 'role' => 'doctor', 'status' => 'active']);
        $this->actingAs($user)->post(route('account.home-module'), ['module' => 'unit'])->assertForbidden();
    }
}
