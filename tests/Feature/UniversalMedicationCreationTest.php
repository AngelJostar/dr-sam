<?php

namespace Tests\Feature;

use App\Models\MedicationCatalogItem;
use App\Models\User;
use App\Services\MedicationSkuSequence;
use Database\Seeders\UniversalMedicationDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class UniversalMedicationCreationTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create(['name' => 'Admin prueba', 'email' => 'creator@example.test', 'username' => 'med-creator', 'role' => 'superadmin', 'module' => 'superadmin', 'status' => 'active']);
    }

    private function product(array $override = []): array
    {
        return array_replace([
            'name' => 'Producto de prueba', 'generic_name' => 'Genérico', 'cnis' => '010.000.0104.00',
            'description' => 'Descripción de prueba', 'presentation' => 'Envase de prueba',
            'mobile_units' => 'No especificado', 'basic_units' => 'Si', 'cessa' => 'No',
            'status' => 'active', 'catalog_context' => 'medications',
        ], $override);
    }

    public function test_modal_is_present_in_both_menus_and_creation_assigns_the_next_sku_after_the_highest_number(): void
    {
        $this->actingAs($this->admin());
        foreach ([route('superadmin.catalog', 'medications'), route('superadmin.digital-pharmacy')] as $url) {
            $this->get($url)->assertOk()->assertSee('Agregar producto')->assertSee('new-medication-dialog')->assertSee('SKU automático');
        }
        MedicationCatalogItem::create(['name' => 'Mayor', 'sku' => '100000203', 'status' => 'active']);
        MedicationCatalogItem::create(['name' => 'Último id pero SKU menor', 'sku' => '100000002', 'status' => 'active']);
        $response = $this->post(route('superadmin.medications.store'), $this->product(['sku' => '100999999', 'barcode' => '0001234567890', 'barcode_type' => 'commercial', 'distinctive_name' => 'Marca de prueba']));
        $response->assertSessionHasNoErrors()->assertRedirect(route('superadmin.catalog', ['section' => 'medications', 'q' => '100000204']));
        $created = MedicationCatalogItem::where('sku', '100000204')->firstOrFail();
        $this->assertSame('0001234567890', $created->barcode);
        $this->assertSame('Marca de prueba', $created->metadata['distinctive_name']);
        $this->assertSame('Si', $created->metadata['basic_units']);
        $this->assertSame('Captura manual', $created->metadata['catalog']);
        $this->assertDatabaseCount('pharmacy_products', 0);
        $this->post(route('superadmin.medications.store'), $this->product(['name' => 'Segundo', 'catalog_context' => 'pharmacy']))
            ->assertSessionHasNoErrors()->assertRedirect(route('superadmin.digital-pharmacy', ['section' => 'catalog', 'q' => '100000205']));
        $this->assertDatabaseHas('medication_catalog_items', ['name' => 'Segundo', 'sku' => '100000205', 'barcode' => null]);
    }

    public function test_validation_and_rollback_do_not_consume_skus_and_unauthorized_accounts_cannot_create(): void
    {
        $admin = $this->admin();
        $url = route('superadmin.medications.store');
        $this->actingAs($admin)->post($url, $this->product(['name' => '', 'cnis' => 'incorrecto', 'barcode' => 'ABC']))
            ->assertSessionHasErrorsIn('newMedication', ['name', 'cnis', 'barcode']);
        $this->assertDatabaseCount('medication_catalog_items', 0);
        $this->assertSame(100000000, (int) DB::table('medication_catalog_sequences')->value('last_value'));
        try {
            DB::transaction(function () {
                $this->assertSame('100000001', app(MedicationSkuSequence::class)->next());
                throw new RuntimeException('Prueba de rollback');
            });
        } catch (RuntimeException $exception) {
            $this->assertSame('Prueba de rollback', $exception->getMessage());
        }
        $this->post($url, $this->product())->assertSessionHasNoErrors();
        $this->assertDatabaseHas('medication_catalog_items', ['sku' => '100000001']);
        foreach (['provider', 'patient'] as $role) {
            $admin->update(['role' => $role]);
            $this->actingAs($admin)->post($url, $this->product())->assertForbidden();
        }
        $admin->update(['role' => 'superadmin', 'status' => 'inactive']);
        $this->actingAs($admin)->post($url, $this->product())->assertForbidden();
        $this->assertDatabaseCount('medication_catalog_items', 1);
    }

    public function test_demo_skus_get_one_prefix_and_reimport_does_not_add_it_again(): void
    {
        $existing = MedicationCatalogItem::create(['cnis' => '010.000.0104.00', 'name' => 'Medicamento conservado', 'sku' => '00000001', 'status' => 'active']);
        $this->seed(UniversalMedicationDemoSeeder::class);
        $this->assertSame('100000001', $existing->fresh()->sku);
        $this->seed(UniversalMedicationDemoSeeder::class);
        $this->assertSame('100000001', $existing->fresh()->sku);
        $this->assertSame(203, MedicationCatalogItem::where('sku', 'regexp', '^1[0-9]{8}$')->count());
        $this->assertSame(203, MedicationCatalogItem::distinct()->count('sku'));
    }

    public function test_sku_edition_requires_the_prefix_and_creation_respects_manually_changed_skus(): void
    {
        $item = MedicationCatalogItem::create(['name' => 'Existente', 'sku' => '100000001', 'status' => 'active']);
        $this->actingAs($this->admin());
        foreach (['000000001', '200000001', '100001', '1000000000000000'] as $invalid) {
            $this->patch(route('superadmin.medications.identifiers', $item), ['sku' => $invalid])->assertSessionHasErrors('sku');
        }
        $this->patch(route('superadmin.medications.identifiers', $item), ['sku' => '100000500'])->assertSessionHasNoErrors();
        $this->post(route('superadmin.medications.store'), $this->product())->assertSessionHasNoErrors();
        $this->assertDatabaseHas('medication_catalog_items', ['sku' => '100000501']);
    }
}
