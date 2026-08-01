<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class NativeModuleRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_module_routes_resolve_to_application_controllers(): void
    {
        $routeNames = [
            'doctor.dashboard', 'patient.dashboard', 'orders.index', 'pharmacy.dashboard',
            'external-pharmacy.dashboard', 'messenger.dashboard', 'operational.dashboard',
            'provider.npt.dashboard', 'provider.chemo.dashboard', 'provider.import.dashboard',
            'provider.medicines.dashboard', 'provider.clinical-labs.dashboard', 'unit.dashboard',
            'institution.dashboard', 'superadmin.dashboard', 'insurance-advisor.dashboard',
            'insurance.dashboard',
        ];

        foreach ($routeNames as $routeName) {
            $route = Route::getRoutes()->getByName($routeName);

            $this->assertNotNull($route, "Route [{$routeName}] is missing.");
            $this->assertStringContainsString('App\\Http\\Controllers\\', $route->getActionName());
            $this->assertStringNotContainsString('Bridge', $route->getActionName());
        }
    }
}
