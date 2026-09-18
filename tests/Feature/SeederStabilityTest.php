<?php

namespace Tests\Feature;

use App\Models\ContractedService;
use App\Models\MedicalUnit;
use App\Models\PlatformModule;
use App\Models\Service;
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

        $unit = MedicalUnit::query()->where('external_id', 'demo-hospital-general-dr-sam')->firstOrFail();
        $service = Service::query()->where('external_id', 'farmacia-externa')->firstOrFail();
        $contract = ContractedService::query()
            ->where('medical_unit_id', $unit->id)
            ->where('service_id', $service->id)
            ->firstOrFail();

        $this->assertDatabaseHas('services', [
            'external_id' => 'farmacia-externa',
            'name' => 'Farmacia externa',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('contracted_services', [
            'institution_id' => $unit->institution_id,
            'medical_unit_id' => $unit->id,
            'service_id' => $service->id,
            'status' => 'active',
        ]);

        $this->actingAs(User::query()->where('username', 'unidad.demo')->firstOrFail())
            ->get(route('unit.dashboard', ['section' => 'services', 'unit' => $unit->id, 'service' => $contract->id]))
            ->assertOk()
            ->assertSee('data-unit-service-key="farmacia-externa"', false)
            ->assertSee('<strong>Farmacia externa</strong>', false)
            ->assertSee('data-unit-service-layout="farmacia-externa"', false);

        $this->actingAs(User::query()->where('username', 'institucion')->firstOrFail())
            ->get(route('institution.dashboard', ['section' => 'services']))
            ->assertOk()
            ->assertSee('data-service-carousel-button="'.$service->id.'"', false)
            ->assertSee('<strong>Farmacia externa</strong>', false);

        $this->actingAs(User::query()->where('username', 'angeles')->firstOrFail())
            ->get(route('institution.dashboard', ['section' => 'services']))
            ->assertOk()
            ->assertDontSee('data-service-carousel-button="'.$service->id.'"', false);
    }
}
