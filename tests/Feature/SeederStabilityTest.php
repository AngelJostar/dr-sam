<?php

namespace Tests\Feature;

use App\Models\PlatformModule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeederStabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_creates_demo_users_and_native_module_targets(): void
    {
        $this->seed();

        $this->assertDatabaseHas('users', [
            'username' => 'superadmin',
            'role' => 'superadmin',
        ]);

        $this->assertDatabaseHas('users', [
            'username' => 'paciente',
            'role' => 'patient',
        ]);

        $this->assertSame(count(config('drsam.demo_users')), User::query()->count());

        $this->assertSame(0, PlatformModule::query()
            ->where('target', 'like', '%.html%')
            ->count());

        $this->assertDatabaseHas('platform_modules', [
            'key' => 'superadmin',
            'target' => 'superadmin.dashboard',
            'enabled' => true,
        ]);
    }
}
