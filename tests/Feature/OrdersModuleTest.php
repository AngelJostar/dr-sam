<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\Patient;
use App\Models\PharmacyProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrdersModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_patient_can_open_native_orders_module(): void
    {
        $user = User::query()->create([
            'name' => 'Paciente Pedidos',
            'username' => 'paciente.pedidos',
            'email' => 'paciente.pedidos@test.local',
            'role' => 'patient',
            'module' => 'patient',
            'status' => 'active',
        ]);

        Patient::query()->create([
            'user_id' => $user->id,
            'platform_number' => 'PAC-ORD-001',
            'full_name' => 'Paciente Pedidos',
            'status' => 'active',
        ]);

        PharmacyProduct::query()->create([
            'name' => 'Paracetamol Tableta',
            'generic_name' => 'Paracetamol',
            'price' => 50,
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->get(route('orders.index'))
            ->assertOk()
            ->assertSee('Farmacia Digital')
            ->assertSee('orders-store-native-screen')
            ->assertSee('orders-store-native-topbar')
            ->assertSee('Ofertas activas')
            ->assertSee('Paracetamol Tableta')
            ->assertDontSee('<iframe');
    }

    public function test_patient_can_create_pharmacy_order(): void
    {
        $user = User::query()->create([
            'name' => 'Paciente Compra',
            'username' => 'paciente.compra',
            'email' => 'paciente.compra@test.local',
            'role' => 'patient',
            'module' => 'patient',
            'status' => 'active',
        ]);

        Patient::query()->create([
            'user_id' => $user->id,
            'platform_number' => 'PAC-ORD-002',
            'full_name' => 'Paciente Compra',
            'status' => 'active',
        ]);

        $product = PharmacyProduct::query()->create([
            'name' => 'Ibuprofeno Capsula',
            'generic_name' => 'Ibuprofeno',
            'price' => 75,
            'status' => 'active',
        ]);

        InventoryItem::query()->create([
            'pharmacy_product_id' => $product->id,
            'warehouse' => 'Farmacia test',
            'quantity' => 10,
            'status' => 'available',
        ]);

        $this->actingAs($user)
            ->post(route('orders.store'), [
                'pharmacy_product_id' => $product->id,
                'quantity' => 2,
                'delivery_mode' => 'Entrega a domicilio',
                'payment_method' => 'Pago digital',
            ])
            ->assertRedirect(route('orders.index'));

        $this->assertDatabaseHas('patient_orders', [
            'status' => 'created',
            'total' => 150,
        ]);

        $this->assertDatabaseHas('patient_order_items', [
            'product_name' => 'Ibuprofeno Capsula',
            'quantity' => 2,
            'total' => 150,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'orders.patient_order.created',
        ]);
    }
}
