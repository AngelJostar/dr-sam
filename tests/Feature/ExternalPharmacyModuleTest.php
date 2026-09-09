<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\Doctor;
use App\Models\MedicalUnit;
use App\Models\MessengerProfile;
use App\Models\OperationalArea;
use App\Models\OperationalProfile;
use App\Models\Patient;
use App\Models\PatientOrder;
use App\Models\PatientOrderItem;
use App\Models\PharmacyProduct;
use App\Models\PharmacyWarehouse;
use App\Models\Prescription;
use App\Models\User;
use App\Services\PrescriptionPharmacyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExternalPharmacyModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_operational_user_can_open_native_external_pharmacy_dashboard(): void
    {
        $user = User::query()->create([
            'name' => 'Farmacia Externa',
            'username' => 'farmacia.externa.test',
            'email' => 'farmacia.externa@test.local',
            'role' => 'operational',
            'module' => 'external_pharmacy',
            'status' => 'active',
        ]);

        $area = OperationalArea::query()->create([
            'key' => 'farmacia-externa-test',
            'label' => 'Farmacia Externa',
        ]);

        OperationalProfile::query()->create([
            'user_id' => $user->id,
            'operational_area_id' => $area->id,
            'status' => 'active',
        ]);

        $patient = Patient::query()->create([
            'full_name' => 'Paciente Externo',
            'status' => 'active',
        ]);

        $product = PharmacyProduct::query()->create([
            'cnis' => '010.000.0104.00',
            'name' => 'Medicamento Externo',
            'generic_name' => 'PARACETAMOL',
            'presentation' => 'Tableta cadatableta contiene paracetamol',
            'price' => 120,
            'status' => 'active',
        ]);

        InventoryItem::query()->create([
            'pharmacy_product_id' => $product->id,
            'warehouse' => 'Externo',
            'lot' => 'L-83084',
            'quantity' => 30,
            'status' => 'available',
            'expires_at' => now()->addMonths(8),
        ]);

        $order = PatientOrder::query()->create([
            'patient_id' => $patient->id,
            'order_number' => 'PED-EXT-001',
            'status' => 'received',
            'total' => 120,
            'ordered_at' => now(),
        ]);

        PatientOrderItem::query()->create([
            'patient_order_id' => $order->id,
            'pharmacy_product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => 1,
            'unit_price' => 120,
            'total' => 120,
        ]);

        $filledOrder = PatientOrder::query()->create([
            'patient_id' => $patient->id,
            'order_number' => 'RX-EXT-002',
            'status' => 'delivered',
            'total' => 240,
            'ordered_at' => now()->subDay(),
        ]);

        PatientOrderItem::query()->create([
            'patient_order_id' => $filledOrder->id,
            'pharmacy_product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => 2,
            'unit_price' => 120,
            'total' => 240,
        ]);

        $this->actingAs($user)
            ->get(route('external-pharmacy.dashboard'))
            ->assertOk()
            ->assertSee('Recetas y surtimiento')
            ->assertSee('Recetas activas')
            ->assertSee('operational-native-screen')
            ->assertSee('operational-native-topbar')
            ->assertSee('operational-native-sidebar')
            ->assertSee('external-pharmacy-module-carousel')
            ->assertSee('class="operational-oncology-carousel operational-compact-carousel external-pharmacy-module-carousel"', false)
            ->assertSee('operational-section-tabs')
            ->assertSee('operational-native-table')
            ->assertSee('Hospitalizacion')
            ->assertSee('Centro Oncologico')
            ->assertSee('Farmacia intrahospitalaria')
            ->assertSee('Consulta Externa')
            ->assertSee('Farmacia Externa')
            ->assertSee('Catalogo de pacientes')
            ->assertSee('Inventario')
            ->assertSee('Movimientos')
            ->assertSee('Almacenes')
            ->assertSee('PED-EXT-001')
            ->assertSee('Medicamento Externo')
            ->assertSee('1 recetas - 1 medicamentos visibles de 1')
            ->assertSee('Surtir')
            ->assertDontSee('Panel independiente')
            ->assertDontSee('Pedido manual')
            ->assertDontSee('<iframe');

        $this->actingAs($user)
            ->get(route('external-pharmacy.dashboard', ['search' => 'Paciente Externo', 'status' => 'received']))
            ->assertOk()
            ->assertSee('PED-EXT-001')
            ->assertSee('Surtir');

        $this->actingAs($user)
            ->get(route('external-pharmacy.dashboard', ['section' => 'filled']))
            ->assertOk()
            ->assertSee('Recetas surtidas')
            ->assertSee('RX-EXT-002')
            ->assertSee('Surtida');

        $this->actingAs($user)
            ->get(route('external-pharmacy.dashboard', ['section' => 'prescription']))
            ->assertOk()
            ->assertSee('Formato de Receta Medica')
            ->assertSee('RX-CE-000000')
            ->assertSee('Medicamentos indicados')
            ->assertSee('data-add-prescription-medication', false)
            ->assertSee('data-remove-prescription-medication', false)
            ->assertSee('Frecuencia');

        $this->actingAs($user)
            ->get(route('external-pharmacy.dashboard', ['section' => 'inventory']))
            ->assertOk()
            ->assertSee('Inventario de farmacia externa')
            ->assertSee('Inventario operativo')
            ->assertSee('L-83084')
            ->assertSee('Entrada')
            ->assertSee('Salida');

        $this->actingAs($user)
            ->get(route('external-pharmacy.dashboard', ['section' => 'movements']))
            ->assertOk()
            ->assertSee('Bitacora de movimientos')
            ->assertSee('Movimientos de inventario')
            ->assertSee('Sin movimientos registrados.');

        $this->actingAs($user)
            ->get(route('external-pharmacy.dashboard', ['section' => 'warehouses']))
            ->assertOk()
            ->assertSee('Almacenes externos')
            ->assertSee('Nuevo almacen')
            ->assertSee('Almacenes activos')
            ->assertSee('Guardar almacen');

        $this->actingAs($user)
            ->get(route('external-pharmacy.dashboard', ['section' => 'catalog']))
            ->assertOk()
            ->assertSee('Catalogo de farmacia')
            ->assertSee('010.000.0104.00')
            ->assertSee('Medicamento Externo');
    }

    public function test_external_pharmacy_can_mark_order_in_route_and_create_delivery_route(): void
    {
        $user = User::query()->create([
            'name' => 'Farmacia Externa Ruta',
            'username' => 'farmacia.externa.ruta',
            'email' => 'farmacia.externa.ruta@test.local',
            'role' => 'operational',
            'module' => 'external_pharmacy',
            'status' => 'active',
        ]);

        $messengerUser = User::query()->create([
            'name' => 'Mensajero Externo',
            'username' => 'mensajero.externo',
            'email' => 'mensajero.externo@test.local',
            'role' => 'messenger',
            'module' => 'messenger',
            'status' => 'active',
        ]);

        $messenger = MessengerProfile::query()->create([
            'user_id' => $messengerUser->id,
            'status' => 'active',
        ]);

        $patient = Patient::query()->create([
            'full_name' => 'Paciente Ruta Externa',
            'status' => 'active',
        ]);

        $order = PatientOrder::query()->create([
            'patient_id' => $patient->id,
            'order_number' => 'PED-EXT-002',
            'status' => 'preparing',
            'total' => 200,
            'ordered_at' => now(),
        ]);

        $this->actingAs($user)
            ->patch(route('external-pharmacy.orders.status', $order), [
                'status' => 'in_route',
                'notes' => 'Sale desde farmacia externa',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('patient_orders', [
            'id' => $order->id,
            'status' => 'in_route',
        ]);

        $this->assertDatabaseHas('delivery_routes', [
            'patient_order_id' => $order->id,
            'messenger_profile_id' => $messenger->id,
            'route_code' => 'RUTA-EXT-PED-EXT-002',
            'status' => 'assigned',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'external_pharmacy.order.status_updated',
            'auditable_id' => $order->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'external_pharmacy.delivery_route.assigned',
        ]);
    }

    public function test_outpatient_prescription_is_received_and_can_be_dispensed_partially_from_inventory(): void
    {
        $user = User::query()->create(['name' => 'Farmacia Recetas', 'username' => 'farmacia.recetas', 'role' => 'operational', 'module' => 'external_pharmacy', 'status' => 'active']);
        $unit = MedicalUnit::query()->create(['name' => 'Unidad Farmacia', 'code' => 'FAR-01', 'status' => 'active']);
        OperationalProfile::query()->create(['user_id' => $user->id, 'medical_unit_id' => $unit->id, 'status' => 'active']);
        $patient = Patient::query()->create(['full_name' => 'Paciente Surtimiento', 'status' => 'active']);
        $doctor = Doctor::query()->create(['medical_unit_id' => $unit->id, 'full_name' => 'Dra. Surtimiento', 'status' => 'active']);
        $product = PharmacyProduct::query()->create(['cnis' => '010.000.0104.00', 'name' => 'Paracetamol', 'generic_name' => 'Paracetamol', 'price' => 10, 'status' => 'active']);
        $inventory = InventoryItem::query()->create(['pharmacy_product_id' => $product->id, 'medical_unit_id' => $unit->id, 'warehouse' => 'Farmacia externa', 'lot' => 'RX-LOT', 'quantity' => 10, 'status' => 'available', 'expires_at' => now()->addYear()]);
        $prescription = Prescription::query()->create(['patient_id' => $patient->id, 'doctor_id' => $doctor->id, 'code' => 'RX-CE-100', 'status' => 'active', 'issued_at' => now(), 'metadata' => ['medical_unit_id' => $unit->id, 'diagnosis' => 'Dolor']]);
        $prescription->items()->create(['medication_name' => 'Paracetamol', 'dose' => '500 mg', 'metadata' => ['cnis' => '010.000.0104.00', 'quantity' => '3']]);
        $order = app(PrescriptionPharmacyService::class)->sync($prescription);
        $item = $order->items->first();

        $prescription->refresh();
        $this->assertSame($order->id, data_get($prescription->metadata, 'pharmacy_order_id'));
        $this->assertSame('RX-CE-100', data_get($prescription->metadata, 'pharmacy_order_number'));
        $this->assertSame('received', data_get($prescription->metadata, 'pharmacy_status'));
        $this->assertNotEmpty(data_get($prescription->metadata, 'pharmacy_synced_at'));

        $this->actingAs($user)->get(route('external-pharmacy.dashboard'))->assertOk()->assertSee('RX-CE-100')->assertSee('3 piezas');
        $this->actingAs($user)->patch(route('external-pharmacy.items.dispense', $item), ['quantity' => 1])->assertRedirect()->assertSessionHas('status', 'Surtimiento parcial registrado.');
        $this->assertSame(9, $inventory->fresh()->quantity);
        $this->assertSame('preparing', $order->fresh()->status);
        $prescription->refresh();
        $this->assertSame('pending', $prescription->status);
        $this->assertSame('partial', data_get($prescription->metadata, 'pharmacy_status'));
        $this->assertNotEmpty(data_get($prescription->metadata, 'pharmacy_last_dispensed_at'));
        $this->assertSame(1, data_get($item->fresh()->metadata, 'filled_quantity'));

        $this->actingAs($user)->patch(route('external-pharmacy.items.dispense', $item), ['quantity' => 2])->assertRedirect()->assertSessionHas('status', 'Receta surtida completamente.');
        $this->assertSame(7, $inventory->fresh()->quantity);
        $this->assertSame('delivered', $order->fresh()->status);
        $prescription->refresh();
        $this->assertSame('filled', $prescription->status);
        $this->assertSame('filled', data_get($prescription->metadata, 'pharmacy_status'));
        $this->assertNotEmpty(data_get($prescription->metadata, 'pharmacy_filled_at'));
        $this->assertDatabaseHas('audit_logs', ['event' => 'external_pharmacy.prescription_item.dispensed', 'auditable_id' => $item->id]);
    }

    public function test_external_pharmacy_persists_warehouses_and_manual_inventory_movements(): void
    {
        $user = User::query()->create([
            'name' => 'Responsable Farmacia',
            'username' => 'responsable.farmacia',
            'role' => 'operational',
            'module' => 'external_pharmacy',
            'status' => 'active',
        ]);
        $unit = MedicalUnit::query()->create(['name' => 'Unidad Farmacia Operativa', 'code' => 'UFO-01', 'status' => 'active']);
        OperationalProfile::query()->create(['user_id' => $user->id, 'medical_unit_id' => $unit->id, 'status' => 'active']);
        $product = PharmacyProduct::query()->create(['cnis' => '010.000.0200.00', 'name' => 'Solucion Operativa', 'price' => 20, 'status' => 'active']);
        $inventory = InventoryItem::query()->create([
            'pharmacy_product_id' => $product->id,
            'medical_unit_id' => $unit->id,
            'warehouse' => 'Ventanilla principal',
            'lot' => 'LOT-200',
            'quantity' => 10,
            'status' => 'available',
        ]);

        $this->actingAs($user)
            ->post(route('external-pharmacy.warehouses.store'), [
                'name' => 'Ventanilla principal',
                'type' => 'dispensing',
                'responsible' => 'Jefatura de farmacia',
            ])
            ->assertRedirect(route('external-pharmacy.dashboard', ['section' => 'warehouses']));

        $warehouse = PharmacyWarehouse::query()->firstOrFail();
        $this->assertSame($unit->id, $warehouse->medical_unit_id);

        $this->actingAs($user)
            ->get(route('external-pharmacy.dashboard', ['section' => 'warehouses']))
            ->assertOk()
            ->assertSee('Almacenes activos')
            ->assertSee('1 almacen')
            ->assertSee('Ventanilla principal')
            ->assertSee('Dispensacion externa')
            ->assertSee('Jefatura de farmacia')
            ->assertSee('Activo')
            ->assertSee('Inactivar');

        $this->actingAs($user)
            ->post(route('external-pharmacy.inventory.movements.store', $inventory), [
                'type' => 'entry',
                'quantity' => 5,
                'notes' => 'Recepcion de proveedor',
            ])
            ->assertRedirect(route('external-pharmacy.dashboard', ['section' => 'movements']));

        $this->assertSame(15, $inventory->fresh()->quantity);
        $this->assertDatabaseHas('inventory_movements', [
            'inventory_item_id' => $inventory->id,
            'type' => 'entry',
            'stock_before' => 10,
            'stock_after' => 15,
        ]);

        $this->actingAs($user)
            ->get(route('external-pharmacy.dashboard', ['section' => 'movements']))
            ->assertOk()
            ->assertSee('Recepcion de proveedor')
            ->assertSee('10')
            ->assertSee('15');

        $this->actingAs($user)
            ->patch(route('external-pharmacy.warehouses.status', $warehouse), ['status' => 'inactive'])
            ->assertRedirect();

        $this->assertSame('inactive', $warehouse->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['event' => 'external_pharmacy.inventory.movement_created']);
        $this->assertDatabaseHas('audit_logs', ['event' => 'external_pharmacy.warehouse.status_updated']);
    }
}

