<?php

namespace Tests\Feature;

use App\Models\MedicationCatalogItem;
use App\Models\PharmacyProduct;
use App\Models\User;
use Database\Seeders\UniversalMedicationDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UniversalMedicationAdministrationTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create(['name' => 'Administrador de prueba', 'email' => 'catalog-test@example.test', 'username' => 'catalog-test', 'role' => 'superadmin', 'module' => 'superadmin', 'status' => 'active']);
    }

    public function test_demo_import_adds_203_official_presentations_without_overwriting_existing_data_or_publishing_products(): void
    {
        $existing = MedicationCatalogItem::create(['cnis' => '010.000.0104.00', 'name' => 'Nombre capturado', 'description' => 'Descripción capturada', 'sku' => '100050001', 'barcode' => '0001234567890', 'barcode_type' => 'commercial', 'status' => 'inactive', 'metadata' => ['custom' => 'Conservar']]);
        $snapshot = $existing->fresh()->getAttributes();
        $this->seed(UniversalMedicationDemoSeeder::class);
        $this->assertDatabaseCount('medication_catalog_items', 203);
        $this->assertDatabaseCount('pharmacy_products', 0);
        $after = $existing->fresh()->getAttributes();
        unset($snapshot['metadata'], $snapshot['updated_at'], $after['metadata'], $after['updated_at']);
        $this->assertSame($snapshot, $after);
        $this->assertSame('Conservar', $existing->fresh()->metadata['custom']);
        $this->assertSame('Si', $existing->fresh()->metadata['mobile_units']);
        $this->assertSame(203, MedicationCatalogItem::distinct()->count('sku'));
        $this->assertSame(203, MedicationCatalogItem::distinct()->count('cnis'));
        $new = MedicationCatalogItem::where('cnis', '010.000.0106.00')->firstOrFail();
        $this->assertSame('No', $new->metadata['mobile_units']);
        $this->assertSame('Si', $new->metadata['basic_units']);
        $this->assertStringContainsString('100 MG', $new->description);
        $this->assertSame('internal', $new->barcode_type);
        $this->assertMatchesRegularExpression('/^1[0-9]{8}$/', $new->sku);
        $this->assertMatchesRegularExpression('/^9900[0-9]{12}$/', $new->barcode);
        $this->assertSame(1, $new->metadata['source_page']);
        $new->update(['sku' => '100070001', 'status' => 'inactive']);
        $this->seed(UniversalMedicationDemoSeeder::class);
        $this->assertDatabaseCount('medication_catalog_items', 203);
        $this->assertSame('100070001', $new->fresh()->sku);
        $this->assertSame('inactive', $new->fresh()->status);
    }

    public function test_both_menus_share_the_same_catalog_and_search_all_pages_by_identifiers(): void
    {
        $this->seed(UniversalMedicationDemoSeeder::class);
        $this->actingAs($this->admin());
        $last = MedicationCatalogItem::orderByDesc('cnis')->firstOrFail();
        foreach ([route('superadmin.digital-pharmacy'), route('superadmin.catalog', 'medications')] as $url) {
            $this->get($url)->assertOk()->assertSee('203 presentaciones')->assertSee('SKU interno')->assertSee('Código de barras numérico')->assertSee('Código de barras imagen')->assertSee('Editar identificadores');
            $this->get($url.'?q='.urlencode($last->sku))->assertOk()->assertSee('1 presentaciones')->assertSee($last->name)->assertSee($last->barcode);
            $this->get($url.'?q='.$last->barcode)->assertOk()->assertSee('1 presentaciones')->assertSee($last->sku);
            $this->get($url.'?q=no-existe-xyz')->assertOk()->assertSee('No hay medicamentos que coincidan');
        }
        $this->get(route('superadmin.digital-pharmacy', ['section' => 'visual']))->assertOk()->assertSee('Guardar presentación');
        $this->get(route('superadmin.digital-pharmacy', ['section' => 'orders']))->assertOk()->assertSee('Control de solicitudes');
    }

    public function test_identifier_changes_preserve_leading_zeroes_and_update_both_views_without_modifying_distributor_products(): void
    {
        $medication = MedicationCatalogItem::create(['name' => 'Paracetamol', 'cnis' => '010.000.0104.00', 'status' => 'active']);
        $product = PharmacyProduct::create(['name' => 'Producto del distribuidor', 'sku' => 'SKU-DISTRIBUIDOR', 'cnis' => $medication->cnis, 'price' => 100, 'stock' => 8, 'status' => 'inactive']);
        $snapshot = $product->fresh()->getAttributes();
        $this->actingAs($this->admin())->from(route('superadmin.digital-pharmacy'))->patch(route('superadmin.medications.identifiers', $medication), ['sku' => '100000001', 'barcode' => '0001234567890', 'barcode_type' => 'commercial', 'name' => 'No modificar', 'status' => 'inactive'])->assertRedirect(route('superadmin.digital-pharmacy'))->assertSessionHasNoErrors();
        $this->assertSame('0001234567890', $medication->fresh()->barcode);
        $this->assertSame('Paracetamol', $medication->fresh()->name);
        $this->assertSame('active', $medication->fresh()->status);
        $this->assertSame($snapshot, $product->fresh()->getAttributes());
        $this->get(route('superadmin.catalog', 'medications'))->assertOk()->assertSee('100000001')->assertSee('0001234567890');
        $this->get(route('superadmin.digital-pharmacy'))->assertOk()->assertSee('100000001')->assertSee('0001234567890');
        $image = $this->get(route('superadmin.medications.barcode', $medication))->assertOk()->assertHeader('Content-Type', 'image/svg+xml')->assertSee('0001234567890');
        $this->assertStringContainsString('<rect x="10"', $image->getContent());
        $csv = $this->get(route('superadmin.catalog.csv', ['section' => 'medications']))->assertOk()->streamedContent();
        $rows = array_values(array_filter(explode("\n", trim($csv))));
        $row = array_combine(str_getcsv($rows[0]), str_getcsv($rows[1]));
        $this->assertSame('100000001', $row['SKU interno']);
        $this->assertSame('0001234567890', $row['Código de barras numérico']);
        $this->assertSame(route('superadmin.medications.barcode', $medication), $row['Código de barras imagen']);
    }

    public function test_invalid_and_duplicate_identifiers_are_rejected_and_non_admins_have_no_access(): void
    {
        $medication = MedicationCatalogItem::create(['name' => 'Medicamento', 'status' => 'active']);
        MedicationCatalogItem::create(['name' => 'Otro', 'sku' => '100000999', 'status' => 'active']);
        $admin = $this->admin();
        $url = route('superadmin.medications.identifiers', $medication);
        $this->actingAs($admin)->patch($url, ['sku' => '100000999'])->assertSessionHasErrors('sku');
        foreach (['ABC123', '123-456', '12.34', '1e8', str_repeat('1', 33)] as $invalidSku) {
            $this->patch($url, ['sku' => $invalidSku])->assertSessionHasErrors('sku');
        }
        foreach (['123', '<script>1</script>', '00123456a', str_repeat('1', 33)] as $invalid) {
            $this->patch($url, ['barcode' => $invalid, 'barcode_type' => 'internal'])->assertSessionHasErrors('barcode');
        }
        $this->patch($url, ['barcode' => '0001234567890'])->assertSessionHasErrors('barcode_type');
        $this->assertNull($medication->fresh()->barcode);
        $this->get(route('superadmin.medications.barcode', $medication))->assertNotFound();
        foreach (['provider', 'patient'] as $role) {
            $admin->update(['role' => $role]);
            $this->actingAs($admin)->get(route('superadmin.digital-pharmacy'))->assertForbidden();
            $this->patch($url, ['sku' => 'DENEGADO'])->assertForbidden();
            $this->get(route('superadmin.medications.barcode', $medication))->assertForbidden();
        }
        $admin->update(['role' => 'superadmin', 'status' => 'inactive']);
        $this->actingAs($admin)->patch($url, ['sku' => 'DENEGADO'])->assertForbidden();
        $this->assertNull($medication->fresh()->sku);
    }
}
