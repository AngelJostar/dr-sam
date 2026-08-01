<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\MedicalUnit;
use App\Models\ProviderRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActionPermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_institution_can_view_operational_but_cannot_update_provider_request_status(): void
    {
        $user = User::query()->create([
            'name' => 'Institucion Lectura',
            'username' => 'institucion.lectura',
            'email' => 'institucion.lectura@test.local',
            'role' => 'institution',
            'module' => 'institution',
            'status' => 'active',
        ]);

        $request = ProviderRequest::query()->create([
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

    public function test_institution_can_view_unit_but_cannot_update_appointment_status(): void
    {
        $user = User::query()->create([
            'name' => 'Institucion Unidad',
            'username' => 'institucion.unidad.permiso',
            'email' => 'institucion.unidad.permiso@test.local',
            'role' => 'institution',
            'module' => 'institution',
            'status' => 'active',
        ]);

        $unit = MedicalUnit::query()->create([
            'name' => 'Unidad Permiso',
            'status' => 'active',
        ]);

        $appointment = Appointment::query()->create([
            'medical_unit_id' => $unit->id,
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
}
