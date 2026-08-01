<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\DeliveryRoute;
use App\Models\Institution;
use App\Models\MedicalUnit;
use App\Models\MessengerProfile;
use App\Models\OperationalProfile;
use App\Models\ProviderRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OwnershipScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_institution_cannot_update_unit_from_another_institution(): void
    {
        $user = User::query()->create([
            'name' => 'Institucion Propia',
            'username' => 'institucion.propia',
            'email' => 'institucion.propia@test.local',
            'role' => 'institution',
            'module' => 'institution',
            'status' => 'active',
        ]);

        $ownInstitution = Institution::query()->create([
            'owner_user_id' => $user->id,
            'name' => 'Institucion Propia',
            'status' => 'active',
        ]);

        $otherInstitution = Institution::query()->create([
            'name' => 'Institucion Ajena',
            'status' => 'active',
        ]);

        MedicalUnit::query()->create([
            'institution_id' => $ownInstitution->id,
            'name' => 'Unidad Propia',
            'status' => 'active',
        ]);

        $otherUnit = MedicalUnit::query()->create([
            'institution_id' => $otherInstitution->id,
            'name' => 'Unidad Ajena',
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->patch(route('institution.units.status', $otherUnit), [
                'status' => 'maintenance',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('medical_units', [
            'id' => $otherUnit->id,
            'status' => 'active',
        ]);
    }

    public function test_unit_cannot_update_appointment_from_another_unit(): void
    {
        $user = User::query()->create([
            'name' => 'Unidad Propia',
            'username' => 'unidad.propia',
            'email' => 'unidad.propia@test.local',
            'role' => 'unit',
            'module' => 'unit',
            'status' => 'active',
        ]);

        MedicalUnit::query()->create([
            'name' => 'Unidad Propia',
            'unit_username' => $user->username,
            'status' => 'active',
        ]);

        $otherUnit = MedicalUnit::query()->create([
            'name' => 'Unidad Ajena',
            'status' => 'active',
        ]);

        $appointment = Appointment::query()->create([
            'medical_unit_id' => $otherUnit->id,
            'status' => 'scheduled',
            'starts_at' => now()->addDay(),
        ]);

        $this->actingAs($user)
            ->patch(route('unit.appointments.status', $appointment), [
                'status' => 'confirmed',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'scheduled',
        ]);
    }

    public function test_messenger_cannot_update_route_assigned_to_another_messenger(): void
    {
        $user = User::query()->create([
            'name' => 'Mensajero Uno',
            'username' => 'mensajero.uno',
            'email' => 'mensajero.uno@test.local',
            'role' => 'messenger',
            'module' => 'messenger',
            'status' => 'active',
        ]);

        MessengerProfile::query()->create([
            'user_id' => $user->id,
            'status' => 'active',
        ]);

        $otherProfile = MessengerProfile::query()->create([
            'status' => 'active',
        ]);

        $route = DeliveryRoute::query()->create([
            'messenger_profile_id' => $otherProfile->id,
            'route_code' => 'RUTA-AJENA',
            'status' => 'assigned',
            'scheduled_at' => now(),
        ]);

        $this->actingAs($user)
            ->patch(route('messenger.routes.status', $route), [
                'status' => 'delivered',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('delivery_routes', [
            'id' => $route->id,
            'status' => 'assigned',
        ]);
    }

    public function test_operational_user_cannot_update_provider_request_from_another_unit(): void
    {
        $user = User::query()->create([
            'name' => 'Operador Propio',
            'username' => 'operador.propio',
            'email' => 'operador.propio@test.local',
            'role' => 'operational',
            'module' => 'operational',
            'status' => 'active',
        ]);

        $ownUnit = MedicalUnit::query()->create([
            'name' => 'Unidad Propia',
            'status' => 'active',
        ]);

        $otherUnit = MedicalUnit::query()->create([
            'name' => 'Unidad Ajena',
            'status' => 'active',
        ]);

        OperationalProfile::query()->create([
            'user_id' => $user->id,
            'medical_unit_id' => $ownUnit->id,
            'status' => 'active',
        ]);

        $request = ProviderRequest::query()->create([
            'medical_unit_id' => $otherUnit->id,
            'request_type' => 'npt',
            'status' => 'requested',
            'requested_at' => now(),
        ]);

        $this->actingAs($user)
            ->patch(route('operational.provider-requests.status', $request), [
                'status' => 'accepted',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('provider_requests', [
            'id' => $request->id,
            'status' => 'requested',
        ]);
    }
}
