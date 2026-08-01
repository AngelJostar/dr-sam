<?php

namespace Tests\Feature;

use App\Models\MedicalUnit;
use App\Models\DeliveryRoute;
use App\Models\MessengerProfile;
use App\Models\Patient;
use App\Models\Provider;
use App\Models\ProviderRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProviderModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_provider_user_can_open_native_npt_dashboard(): void
    {
        $user = User::query()->create([
            'name' => 'Proveedor NPT',
            'username' => 'proveedor.npt.test',
            'email' => 'proveedor.npt@test.local',
            'role' => 'provider',
            'module' => 'provider_npt',
            'status' => 'active',
        ]);

        $provider = Provider::query()->create([
            'user_id' => $user->id,
            'name' => 'Proveedor NPT Test',
            'provider_type' => 'npt',
            'status' => 'active',
        ]);

        $patient = Patient::query()->create([
            'full_name' => 'Paciente Proveedor',
            'status' => 'active',
        ]);

        $unit = MedicalUnit::query()->create([
            'name' => 'Unidad Proveedor',
            'status' => 'active',
        ]);

        ProviderRequest::query()->create([
            'provider_id' => $provider->id,
            'patient_id' => $patient->id,
            'medical_unit_id' => $unit->id,
            'external_id' => 'REQ-PROV-001',
            'request_type' => 'npt',
            'status' => 'requested',
            'requested_at' => now(),
            'required_at' => now()->addDay(),
            'payload' => [
                'service' => 'Nutricion parenteral',
                'doctor' => 'Dra. Laura Operativa',
                'diagnosis' => 'Soporte nutricional postoperatorio',
                'prescription_code' => 'RX-CE-001',
                'prescription_items' => [
                    ['medication_name' => 'Formula NPT', 'dose' => '1500 ml'],
                ],
            ],
        ]);

        $this->actingAs($user)
            ->get(route('provider.npt.dashboard'))
            ->assertOk()
            ->assertSee('Proveedor Integral')
            ->assertSee('provider-native-screen')
            ->assertSee('Historial de Solicitudes')
            ->assertSee('Ver Formato de Solicitud')
            ->assertSee('data-provider-central-form', false)
            ->assertSee('provider-native-dialog', false)
            ->assertSee('Remision de entrega')
            ->assertSee('REQ-PROV-001')
            ->assertSee('Paciente Proveedor')
            ->assertSee('Dra. Laura Operativa')
            ->assertSee('Receta CE RX-CE-001')
            ->assertSee('Soporte nutricional postoperatorio')
            ->assertSee('1 partida(s)')
            ->assertDontSee('<iframe');

        $this->actingAs($user)
            ->get(route('provider.npt.dashboard', ['section' => 'pending']))
            ->assertOk()
            ->assertSee('Solicitudes Pendientes')
            ->assertSee('Solicitudes que siguen en revision por central');

        $this->actingAs($user)
            ->get(route('provider.npt.dashboard', ['section' => 'prices']))
            ->assertOk()
            ->assertSee('Catalogo NPT')
            ->assertSee('Importar lista de precios');

        $this->actingAs($user)
            ->get(route('provider.npt.dashboard', ['section' => 'messengers']))
            ->assertOk()
            ->assertSee('Mensajeros')
            ->assertSee('Dar de alta mensajero');

        $this->actingAs($user)
            ->get(route('provider.npt.dashboard', ['section' => 'shipments']))
            ->assertOk()
            ->assertSee('Envios')
            ->assertSee('Catalogo de Rutas');
    }

    public function test_provider_can_update_request_and_create_delivery_route_when_in_route(): void
    {
        $user = User::query()->create([
            'name' => 'Proveedor Quimio',
            'username' => 'proveedor.quimio.test',
            'email' => 'proveedor.quimio@test.local',
            'role' => 'provider',
            'module' => 'provider_chemo',
            'status' => 'active',
        ]);

        $provider = Provider::query()->create([
            'user_id' => $user->id,
            'name' => 'Proveedor Quimio Test',
            'provider_type' => 'chemotherapy',
            'status' => 'active',
        ]);

        $messengerUser = User::query()->create([
            'name' => 'Mensajero Proveedor',
            'username' => 'mensajero.proveedor',
            'email' => 'mensajero.proveedor@test.local',
            'role' => 'messenger',
            'module' => 'messenger',
            'status' => 'active',
        ]);

        $messenger = MessengerProfile::query()->create([
            'user_id' => $messengerUser->id,
            'status' => 'active',
        ]);

        $unit = MedicalUnit::query()->create([
            'name' => 'Unidad Quimio',
            'status' => 'active',
        ]);

        $request = ProviderRequest::query()->create([
            'provider_id' => $provider->id,
            'medical_unit_id' => $unit->id,
            'request_type' => 'chemotherapy',
            'status' => 'accepted',
            'requested_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('provider.chemo.dashboard'))
            ->assertOk()
            ->assertSee('Proveedor Quimioterapias')
            ->assertSee('provider-chemo-native-screen')
            ->assertSee('Catalogo de medicamentos general')
            ->assertSee('Ciclofosfamida');

        $this->actingAs($user)
            ->patch(route('provider.requests.status', ['chemotherapy', $request]), [
                'status' => 'in_route',
                'notes' => 'Sale a unidad',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('provider_requests', [
            'id' => $request->id,
            'status' => 'in_route',
        ]);

        $this->assertDatabaseHas('provider_request_status_events', [
            'provider_request_id' => $request->id,
            'status' => 'in_route',
            'actor' => 'Proveedor Quimio',
            'notes' => 'Sale a unidad',
        ]);

        $this->assertDatabaseHas('delivery_routes', [
            'provider_request_id' => $request->id,
            'messenger_profile_id' => $messenger->id,
            'status' => 'assigned',
        ]);

        $route = DeliveryRoute::query()->where('provider_request_id', $request->id)->firstOrFail();

        $this->actingAs($user)
            ->patch(route('provider.delivery-routes.status', ['chemotherapy', $route]), [
                'status' => 'in_route',
                'notes' => 'Ruta iniciada',
            ])
            ->assertRedirect();

        $this->actingAs($user)
            ->patch(route('provider.delivery-routes.status', ['chemotherapy', $route]), [
                'status' => 'delivered',
                'notes' => 'Entrega recibida completa',
                'received_by' => 'Responsable de unidad',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('delivery_routes', ['id' => $route->id, 'status' => 'delivered']);
        $this->assertDatabaseHas('delivery_reports', [
            'delivery_route_id' => $route->id,
            'status' => 'delivered',
            'notes' => 'Entrega recibida completa',
        ]);
        $this->assertSame('Responsable de unidad', $request->fresh()->payload['remission']['received_by']);
    }

    public function test_import_provider_dashboard_uses_native_route(): void
    {
        $user = User::query()->create([
            'name' => 'Proveedor Importacion',
            'username' => 'proveedor.import.test',
            'email' => 'proveedor.import@test.local',
            'role' => 'provider',
            'module' => 'provider_import',
            'status' => 'active',
        ]);

        Provider::query()->create([
            'user_id' => $user->id,
            'name' => 'Proveedor Importacion Test',
            'provider_type' => 'import',
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->get(route('provider.import.dashboard'))
            ->assertOk()
            ->assertSee('Proveedor Importacion')
            ->assertSee('provider-import-native-screen')
            ->assertSee('Tablero de importacion')
            ->assertSee('IMP-20260611-001')
            ->assertSee('Tocilizumab')
            ->assertDontSee('<iframe');
    }
}
