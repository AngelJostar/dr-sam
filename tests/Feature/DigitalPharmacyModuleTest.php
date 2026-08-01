<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\DeliveryReport;
use App\Models\DeliveryRoute;
use App\Models\MessengerProfile;
use App\Models\Patient;
use App\Models\PatientOrder;
use App\Models\PharmacyProduct;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DigitalPharmacyModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_operational_user_can_open_native_pharmacy_dashboard(): void
    {
        $user = User::query()->create([
            'name' => 'Farmacia Digital',
            'username' => 'farmacia.digital.test',
            'email' => 'farmacia.digital@test.local',
            'role' => 'operational',
            'module' => 'digital_pharmacy',
            'status' => 'active',
        ]);

        $product = PharmacyProduct::query()->create([
            'cnis' => '010.000.0104.00',
            'name' => 'Paracetamol Tableta',
            'generic_name' => 'Paracetamol',
            'presentation' => '10 tabletas',
            'price' => 50,
            'status' => 'active',
        ]);

        $promotion = PharmacyProduct::query()->create([
            'cnis' => '010.000.0106.00',
            'name' => 'Vitamina D3 2000 UI',
            'generic_name' => 'Vitamina D3',
            'presentation' => '30 capsulas',
            'price' => 149,
            'status' => 'active',
        ]);

        $controlledProduct = PharmacyProduct::query()->create([
            'cnis' => '040.000.2612.00',
            'name' => 'Clonazepam 2 mg',
            'generic_name' => 'Clonazepam',
            'presentation' => 'Tableta 2 mg, caja con 30',
            'price' => 120,
            'requires_prescription' => true,
            'controlled' => true,
            'status' => 'active',
        ]);

        $lowStockProduct = PharmacyProduct::query()->create([
            'cnis' => '060.231.0641',
            'name' => 'A Atas Quirurgicas',
            'generic_name' => 'A Atas Quirurgicas',
            'presentation' => 'Bata quirurgica con punos ajustables',
            'price' => 389,
            'status' => 'active',
        ]);

        InventoryItem::query()->create([
            'pharmacy_product_id' => $product->id,
            'warehouse' => 'Farmacia test',
            'lot' => 'L-DIG-001',
            'quantity' => 20,
            'status' => 'available',
            'expires_at' => now()->addMonths(6),
        ]);

        InventoryItem::query()->create([
            'pharmacy_product_id' => $lowStockProduct->id,
            'warehouse' => 'Refrigerados',
            'lot' => 'L-DIG-002',
            'quantity' => 2,
            'status' => 'available',
            'expires_at' => now()->addMonths(3),
        ]);

        foreach (['Anestesiologia', 'Alergologia', 'Medicina Interna', 'Oncologia'] as $specialty) {
            Service::query()->create([
                'name' => $specialty,
                'specialty' => $specialty,
                'status' => 'active',
            ]);
        }

        $patient = Patient::query()->create([
            'full_name' => 'Claudia Beatriz Salinas Vega',
            'status' => 'active',
        ]);

        $order = PatientOrder::query()->create([
            'patient_id' => $patient->id,
            'order_number' => 'MED-000001-002',
            'status' => 'delivered',
            'subtotal' => 50,
            'total' => 50,
            'ordered_at' => now(),
        ]);

        $order->items()->create([
            'pharmacy_product_id' => $product->id,
            'product_name' => 'Paracetamol 500 mg',
            'quantity' => 1,
            'unit_price' => 50,
            'total' => 50,
        ]);

        $openOrder = PatientOrder::query()->create([
            'patient_id' => $patient->id,
            'order_number' => 'MED-DEMO-101',
            'status' => 'preparing',
            'channel' => 'digital',
            'subtotal' => 199,
            'total' => 199,
            'ordered_at' => now()->subDay(),
            'metadata' => [
                'address' => 'Durango 245, Roma Norte, Cuauhtemoc',
                'postal_code' => '06700',
            ],
        ]);

        $openOrder->items()->create([
            'pharmacy_product_id' => $controlledProduct->id,
            'product_name' => 'Clonazepam 2 mg',
            'quantity' => 2,
            'unit_price' => 99.50,
            'total' => 199,
        ]);

        $messengerUser = User::query()->create([
            'name' => 'Ana Lopez',
            'username' => 'ana.lopez',
            'email' => 'ana.lopez@test.local',
            'role' => 'messenger',
            'status' => 'active',
        ]);

        $messenger = MessengerProfile::query()->create([
            'user_id' => $messengerUser->id,
            'phone' => '55 1000 2201',
            'vehicle' => 'Motocicleta',
            'status' => 'active',
            'metadata' => ['route_name' => 'Ruta Centro', 'shift' => 'Matutino'],
        ]);

        $route = DeliveryRoute::query()->create([
            'messenger_profile_id' => $messenger->id,
            'patient_order_id' => $openOrder->id,
            'route_code' => 'FAR-20260714-101',
            'origin' => 'Operacion propia',
            'destination' => 'Claudia Beatriz Salinas Vega',
            'status' => 'in_route',
            'scheduled_at' => now(),
        ]);

        DeliveryReport::query()->create([
            'delivery_route_id' => $route->id,
            'status' => 'delivered',
            'notes' => 'Firma digital demo',
            'reported_at' => now(),
            'payload' => ['condition' => 'Integra'],
        ]);

        $this->actingAs($user)
            ->get(route('pharmacy.dashboard'))
            ->assertOk()
            ->assertSee('Tablero de farmacia digital')
            ->assertSee('Prioridad operativa')
            ->assertSee('Control y cumplimiento')
            ->assertSee('Envios recientes')
            ->assertSee('digital-pharmacy-native-screen')
            ->assertSee('digital-pharmacy-native-sidebar')
            ->assertSee('digital-pharmacy-native-table')
            ->assertSee('Paracetamol Tableta')
            ->assertSee('MED-000001-002')
            ->assertSee('Entregado')
            ->assertDontSee('<iframe');

        $this->actingAs($user)
            ->get(route('pharmacy.dashboard', ['section' => 'patient-home']))
            ->assertOk()
            ->assertSee('Pagina de inicio paciente')
            ->assertSee('Farmacia digital, pagina de inicio')
            ->assertSee('Vista previa')
            ->assertSee('Banner carrusel')
            ->assertSee('Carrusel de recomendados')
            ->assertSee('Productos con promocion')
            ->assertSee('Vitamina D3 2000 UI');

        $this->actingAs($user)
            ->get(route('pharmacy.dashboard', ['section' => 'catalog']))
            ->assertOk()
            ->assertSee('Catalogo regulatorio de productos')
            ->assertSee('Catalogo maestro')
            ->assertSee('Denominacion generica')
            ->assertSee('060.231.0641')
            ->assertSee('Nuevo producto');

        $this->actingAs($user)
            ->get(route('pharmacy.dashboard', ['section' => 'specialties']))
            ->assertOk()
            ->assertSee('Catalogo de Especialidades')
            ->assertSee('Anestesiologia')
            ->assertSee('Medicina Interna');

        $this->actingAs($user)
            ->get(route('pharmacy.dashboard', ['section' => 'inventory']))
            ->assertOk()
            ->assertSee('Inventario por lote y almacen')
            ->assertSee('Inventario operativo')
            ->assertSee('L-DIG-001')
            ->assertSee('Entrada')
            ->assertSee('Salida');

        $this->actingAs($user)
            ->get(route('pharmacy.dashboard', ['section' => 'missing']))
            ->assertOk()
            ->assertSee('Seguimiento de faltantes')
            ->assertSee('Faltantes y reposicion')
            ->assertSee('A Atas Quirurgicas')
            ->assertSee('Ordenar')
            ->assertSee('Resolver');

        $this->actingAs($user)
            ->get(route('pharmacy.dashboard', ['section' => 'orders']))
            ->assertOk()
            ->assertSee('Gestion de pedidos y envios')
            ->assertSee('Pedidos y envios')
            ->assertSee('MED-DEMO-101')
            ->assertSee('FAR-20260714-101')
            ->assertSee('Trazar')
            ->assertSee('Entregar');

        $this->actingAs($user)
            ->get(route('pharmacy.dashboard', ['section' => 'messengers']))
            ->assertOk()
            ->assertSee('Mensajeros')
            ->assertSee('Ana Lopez')
            ->assertSee('Ruta Centro')
            ->assertSee('Dashboard / Nueva ruta')
            ->assertSee('Escanear');

        $this->actingAs($user)
            ->get(route('pharmacy.dashboard', ['section' => 'traceability']))
            ->assertOk()
            ->assertSee('Trazabilidad de envios y medicamentos')
            ->assertSee('Pedidos trazables')
            ->assertSee('MED-DEMO-101')
            ->assertSee('FAR-20260714-101');

        $this->actingAs($user)
            ->get(route('pharmacy.dashboard', ['section' => 'deliveries']))
            ->assertOk()
            ->assertSee('Registros de entrega de medicamentos')
            ->assertSee('Entregas registradas')
            ->assertSee('Firma digital y georeferencia registrada');

        $this->actingAs($user)
            ->get(route('pharmacy.dashboard', ['section' => 'cold-chain']))
            ->assertOk()
            ->assertSee('Registro de cadena fria')
            ->assertSee('Bitacora de cadena fria')
            ->assertSee('Nueva lectura')
            ->assertSee('Registrar lectura');

        $this->actingAs($user)
            ->get(route('pharmacy.dashboard', ['section' => 'controlled']))
            ->assertOk()
            ->assertSee('Medicamentos controlados')
            ->assertSee('Libro electronico de controlados')
            ->assertSee('Clonazepam 2 mg')
            ->assertSee('Registrar movimiento');

        $this->actingAs($user)
            ->get(route('pharmacy.dashboard', ['section' => 'prescription']))
            ->assertOk()
            ->assertSee('Medicamentos que requieren receta medica')
            ->assertSee('Validacion de recetas')
            ->assertSee('Productos sujetos a receta')
            ->assertSee('Validar')
            ->assertSee('Rechazar');

        $this->actingAs($user)
            ->get(route('pharmacy.dashboard', ['section' => 'reports']))
            ->assertOk()
            ->assertSee('Reportes operativos')
            ->assertSee('Descargas CSV')
            ->assertSee('Catalogo maestro')
            ->assertSee('Controlados')
            ->assertSee('Receta medica');
    }

    public function test_operational_user_can_update_order_status(): void
    {
        $user = User::query()->create([
            'name' => 'Operador Farmacia',
            'username' => 'operador.farmacia',
            'email' => 'operador.farmacia@test.local',
            'role' => 'operational',
            'module' => 'digital_pharmacy',
            'status' => 'active',
        ]);

        $patient = Patient::query()->create([
            'full_name' => 'Paciente Farmacia',
            'status' => 'active',
        ]);

        $order = PatientOrder::query()->create([
            'patient_id' => $patient->id,
            'order_number' => 'PED-TEST-001',
            'status' => 'created',
            'subtotal' => 100,
            'total' => 100,
            'ordered_at' => now(),
        ]);

        $this->actingAs($user)
            ->patch(route('pharmacy.orders.status', $order), [
                'status' => 'preparing',
                'notes' => 'Pedido en preparacion',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('patient_orders', [
            'id' => $order->id,
            'status' => 'preparing',
        ]);
    }
}
