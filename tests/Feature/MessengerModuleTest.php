<?php

namespace Tests\Feature;

use App\Models\DeliveryRoute;
use App\Models\MessengerProfile;
use App\Models\Patient;
use App\Models\PatientOrder;
use App\Models\PatientOrderItem;
use App\Models\PharmacyProduct;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessengerModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_messenger_user_can_open_native_dashboard(): void
    {
        $user = User::query()->create([
            'name' => 'Luis Mensajero',
            'username' => 'mensajero.test',
            'email' => 'mensajero@test.local',
            'role' => 'messenger',
            'module' => 'messenger',
            'status' => 'active',
        ]);

        $profile = MessengerProfile::query()->create([
            'user_id' => $user->id,
            'external_id' => 'messenger-test',
            'phone' => '5555551212',
            'vehicle' => 'Unidad test',
            'status' => 'active',
        ]);

        $patient = Patient::query()->create([
            'full_name' => 'Paciente Entrega',
            'status' => 'active',
        ]);

        $order = PatientOrder::query()->create([
            'patient_id' => $patient->id,
            'order_number' => 'PED-MSG-001',
            'status' => 'in_route',
            'subtotal' => 150,
            'total' => 150,
            'ordered_at' => now(),
        ]);

        DeliveryRoute::query()->create([
            'messenger_profile_id' => $profile->id,
            'patient_order_id' => $order->id,
            'route_code' => 'RUTA-PED-MSG-001',
            'origin' => 'Farmacia Digital',
            'destination' => 'Domicilio paciente',
            'status' => 'assigned',
            'scheduled_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('messenger.dashboard'))
            ->assertOk()
            ->assertSee('Modulo Mensajero')
            ->assertSee('messenger-native-screen')
            ->assertSee('Dashboard / Nueva ruta')
            ->assertSee('PED-MSG-001')
            ->assertSee('Paciente Entrega')
            ->assertDontSee('<iframe');
    }

    public function test_messenger_can_report_delivery_and_close_patient_order(): void
    {
        $user = User::query()->create([
            'name' => 'Mensajero Entrega',
            'username' => 'mensajero.entrega',
            'email' => 'mensajero.entrega@test.local',
            'role' => 'messenger',
            'module' => 'messenger',
            'status' => 'active',
        ]);

        $profile = MessengerProfile::query()->create([
            'user_id' => $user->id,
            'status' => 'active',
        ]);

        $patient = Patient::query()->create([
            'full_name' => 'Paciente Recibe',
            'status' => 'active',
        ]);

        $prescription = Prescription::query()->create([
            'patient_id' => $patient->id,
            'code' => 'RX-MSG-002',
            'status' => 'active',
            'issued_at' => now(),
            'metadata' => ['source' => 'external_pharmacy'],
        ]);

        $order = PatientOrder::query()->create([
            'patient_id' => $patient->id,
            'order_number' => 'PED-MSG-002',
            'status' => 'in_route',
            'subtotal' => 200,
            'total' => 200,
            'ordered_at' => now(),
            'metadata' => ['prescription_id' => $prescription->id],
        ]);

        $route = DeliveryRoute::query()->create([
            'messenger_profile_id' => $profile->id,
            'patient_order_id' => $order->id,
            'route_code' => 'RUTA-PED-MSG-002',
            'origin' => 'Farmacia Digital',
            'destination' => 'Domicilio paciente',
            'status' => 'in_route',
            'scheduled_at' => now(),
        ]);

        $this->actingAs($user)
            ->patch(route('messenger.routes.status', $route), [
                'status' => 'delivered',
                'notes' => 'Recibido por paciente',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('delivery_routes', [
            'id' => $route->id,
            'status' => 'delivered',
        ]);

        $this->assertDatabaseHas('delivery_reports', [
            'delivery_route_id' => $route->id,
            'status' => 'delivered',
            'notes' => 'Recibido por paciente',
        ]);

        $this->assertDatabaseHas('patient_orders', [
            'id' => $order->id,
            'status' => 'delivered',
        ]);

        $order->refresh();
        $prescription->refresh();
        $this->assertSame($route->id, $order->metadata['messenger_route_id']);
        $this->assertSame('RUTA-PED-MSG-002', $order->metadata['messenger_route_code']);
        $this->assertSame('delivered', $order->metadata['messenger_route_status']);
        $this->assertSame('Recibido por paciente', $order->metadata['messenger_last_note']);
        $this->assertSame($order->id, $prescription->metadata['messenger_patient_order_id']);
        $this->assertSame('PED-MSG-002', $prescription->metadata['messenger_order_number']);
        $this->assertSame($route->id, $prescription->metadata['messenger_route_id']);
        $this->assertSame('delivered', $prescription->metadata['messenger_route_status']);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'messenger.route.status_updated',
            'auditable_id' => $route->id,
        ]);
    }

    public function test_pharmacy_in_route_status_creates_delivery_route(): void
    {
        $operator = User::query()->create([
            'name' => 'Operador Farmacia',
            'username' => 'operador.ruta',
            'email' => 'operador.ruta@test.local',
            'role' => 'operational',
            'module' => 'digital_pharmacy',
            'status' => 'active',
        ]);

        $messenger = User::query()->create([
            'name' => 'Mensajero Ruta',
            'username' => 'mensajero.ruta',
            'email' => 'mensajero.ruta@test.local',
            'role' => 'messenger',
            'module' => 'messenger',
            'status' => 'active',
        ]);

        $profile = MessengerProfile::query()->create([
            'user_id' => $messenger->id,
            'status' => 'active',
        ]);

        $patient = Patient::query()->create([
            'full_name' => 'Paciente Ruta',
            'status' => 'active',
        ]);

        $product = PharmacyProduct::query()->create([
            'name' => 'Medicamento Ruta',
            'price' => 90,
            'status' => 'active',
        ]);

        $order = PatientOrder::query()->create([
            'patient_id' => $patient->id,
            'order_number' => 'PED-MSG-003',
            'status' => 'preparing',
            'subtotal' => 90,
            'total' => 90,
            'ordered_at' => now(),
        ]);

        PatientOrderItem::query()->create([
            'patient_order_id' => $order->id,
            'pharmacy_product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => 1,
            'unit_price' => 90,
            'total' => 90,
        ]);

        $this->actingAs($operator)
            ->patch(route('pharmacy.orders.status', $order), [
                'status' => 'in_route',
                'notes' => 'Listo para mensajeria',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('delivery_routes', [
            'patient_order_id' => $order->id,
            'messenger_profile_id' => $profile->id,
            'route_code' => 'RUTA-PED-MSG-003',
            'status' => 'assigned',
        ]);
    }
}
