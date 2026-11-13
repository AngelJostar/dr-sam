<?php

namespace Tests\Feature;

use App\Models\{MedicationCatalogItem, Patient, PharmacyProduct, PharmacyStoreSetting, Provider, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UniversalPharmacyPublicationTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        return User::create(['name' => 'Prueba', 'username' => (string)Str::uuid(), 'role' => $role, 'status' => 'active', 'module' => $role === 'provider' ? 'provider_medicines' : $role]);
    }

    private function item(array $data = []): MedicationCatalogItem
    {
        return MedicationCatalogItem::create($data + ['name' => 'Producto universal', 'sku' => '100000001', 'description' => 'Descripción oficial', 'presentation' => 'Caja', 'status' => 'active', 'therapeutic_group' => 'ANALGESIA', 'digital_pharmacy_enabled' => true]);
    }

    private function provider(array $data = []): Provider
    {
        return Provider::create($data + ['user_id' => $this->user('provider')->id, 'name' => 'Distribuidor de prueba', 'provider_type' => 'medicines', 'status' => 'active', 'digital_pharmacy_enabled' => true]);
    }

    private function enable(): void
    {
        PharmacyStoreSetting::create(['id' => 1, 'enabled' => true, 'title' => 'Farmacia']);
    }

    private function offer(MedicationCatalogItem $item, Provider $provider, array $data = []): PharmacyProduct
    {
        return PharmacyProduct::create($data + ['medication_catalog_item_id' => $item->id, 'provider_id' => $provider->id, 'name' => $item->name, 'price' => '25.50', 'stock' => 10, 'status' => 'active']);
    }

    public function test_all_four_statuses_are_saved_and_filterable_without_changing_identifiers_or_offers(): void
    {
        $item = $this->item();
        $offer = $this->offer($item, $this->provider());
        $snapshot = $offer->fresh()->getAttributes();
        $this->actingAs($this->user('superadmin'));
        foreach (MedicationCatalogItem::STATUS_LABELS as $key => $label) {
            $this->from(route('superadmin.catalog', 'medications'))->patch(route('superadmin.medications.status', $item), ['status' => $key, 'sku' => 'HACK', 'digital_pharmacy_enabled' => false])->assertSessionHasNoErrors()->assertRedirect();
            $this->assertSame($key, $item->fresh()->status);
            $this->assertSame('100000001', $item->fresh()->sku);
            $this->assertTrue($item->fresh()->digital_pharmacy_enabled);
            foreach ([route('superadmin.catalog', 'medications'), route('superadmin.digital-pharmacy')] as $url) {
                $this->get($url.'?status='.$key)->assertOk()->assertSee('Cambiar estatus')->assertSee($label)->assertSee('Mostrar en farmacia digital')->assertSee('Producto universal');
            }
        }
        $this->assertSame($snapshot, $offer->fresh()->getAttributes());
        $this->patch(route('superadmin.medications.status', $item), ['status' => 'garbage'])->assertSessionHasErrors('status', null, 'medicationStatus');
        $this->assertSame('discontinued', $item->fresh()->status);
    }

    public function test_switch_publication_is_idempotent_and_hides_all_offers_direct_urls_and_new_orders(): void
    {
        $this->enable();
        $item = $this->item(['digital_pharmacy_enabled' => false]);
        $offer = $this->offer($item, $this->provider());
        $this->getJson('/api/mobile/v1/pharmacy/catalog-items')->assertJsonCount(0, 'data');
        $admin = $this->user('superadmin');
        $this->actingAs($admin);
        foreach ([1, 1] as $state) $this->patchJson(route('superadmin.medications.publication', $item), ['digital_pharmacy_enabled' => $state])->assertOk()->assertJsonPath('enabled', true);
        $this->getJson('/api/mobile/v1/pharmacy/catalog')->assertJsonCount(1, 'data.catalog_items')->assertJsonPath('data.catalog_items.0.category', 'Analgesia');
        $this->getJson('/api/mobile/v1/pharmacy/products/'.$offer->id)->assertOk();
        $this->patchJson(route('superadmin.medications.publication', $item), ['digital_pharmacy_enabled' => 0])->assertOk();
        $this->getJson('/api/mobile/v1/pharmacy/catalog')->assertJsonCount(0, 'data.products')->assertJsonCount(0, 'data.catalog_items');
        $this->getJson('/api/mobile/v1/pharmacy/products/'.$offer->id)->assertNotFound();
        $this->getJson('/api/mobile/v1/pharmacy/catalog-items/'.$item->id)->assertNotFound();
        $this->getJson('/api/mobile/v1/pharmacy/products/'.$offer->id.'/image')->assertNotFound();
        $patient = $this->user('patient');
        Patient::create(['user_id' => $patient->id, 'full_name' => 'Prueba', 'status' => 'active']);
        Sanctum::actingAs($patient, ['mobile:access']);
        $this->postJson('/api/mobile/v1/pharmacy/orders', ['client_request_id' => (string)Str::uuid(), 'items' => [['product_id' => $offer->id, 'quantity' => 1]], 'delivery_method' => 'delivery', 'terms_accepted' => true, 'address' => ['name' => 'Prueba', 'street' => 'Calle', 'neighborhood' => 'Centro', 'postal_code' => '01000']])->assertUnprocessable();
        $this->assertDatabaseCount('pharmacy_store_orders', 0);
        $this->assertSame(10, $offer->fresh()->stock);
    }

    public function test_distributor_selection_creates_only_own_linked_offer_and_retries_update_instead_of_duplicate(): void
    {
        $item = $this->item();
        $hidden = $this->item(['sku' => '100000002', 'digital_pharmacy_enabled' => false, 'name' => 'Oculto universal']);
        $provider = $this->provider();
        $foreign = $this->offer($item, $this->provider(), ['price' => '65432.10']);
        $this->actingAs($provider->user)->get(route('provider.pharmacy.catalog'))->assertOk()->assertSee('Producto universal')->assertDontSee('Oculto universal')->assertDontSee('65432.10');
        $url = route('provider.pharmacy.catalog.offer', $item);
        $data = ['price' => '35.50', 'stock' => 7, 'status' => 'active', 'laboratory' => 'Marca de prueba', 'provider_id' => $foreign->provider_id, 'sku' => 'HACK'];
        $this->put($url, $data)->assertRedirect()->assertSessionHasNoErrors();
        $this->put($url, $data)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('pharmacy_products', 2);
        $own = PharmacyProduct::where('provider_id', $provider->id)->firstOrFail();
        $this->assertSame('100000001', $own->sku);
        $this->assertSame('Descripción oficial', $own->description);
        $this->assertSame('Analgesia', $own->category);
        $this->assertSame('65432.10', $foreign->fresh()->price);
        $this->get(route('provider.pharmacy.catalog', ['selection' => 'selected']))->assertSee('Producto universal');
        $filtered = $this->get(route('provider.pharmacy.catalog', ['columns' => json_encode(['sku' => [substr(hash('sha256', 'no-existe'), 0, 16)]])]))->assertOk();
        $this->assertSame(0, $filtered->viewData('records')->total());
        $this->patch(route('provider.pharmacy.products.update', $own), ['price' => '36.00', 'stock' => 8, 'status' => 'active', 'name' => 'Nombre falsificado', 'sku' => 'HACK', 'requires_prescription' => 0])->assertSessionHasNoErrors();
        $this->assertSame('Producto universal', $own->fresh()->name);
        $this->assertSame('100000001', $own->fresh()->sku);
        $this->assertSame('36.00', $own->fresh()->price);
        $this->put($url, array_replace($data, ['status' => 'inactive']))->assertSessionHasNoErrors();
        $this->get(route('provider.pharmacy.catalog', ['selection' => 'selected']))->assertDontSee('Producto universal');
        $this->put(route('provider.pharmacy.catalog.offer', $hidden), $data)->assertNotFound();
        $this->put($url, array_replace($data, ['price' => -1]))->assertSessionHasErrors('price', null, 'catalogOffer');
    }

    public function test_nearest_available_supplier_is_computed_from_real_coordinates_and_missing_locations_are_not_zero_distance(): void
    {
        $this->enable();
        $item = $this->item();
        $far = $this->offer($item, $this->provider(['name' => 'Lejano', 'pharmacy_address' => 'Puebla', 'pharmacy_latitude' => 19.04, 'pharmacy_longitude' => -98.2]));
        $near = $this->offer($item, $this->provider(['name' => 'Cercano', 'pharmacy_address' => 'Centro CDMX', 'pharmacy_latitude' => 19.4326, 'pharmacy_longitude' => -99.1332]));
        $this->offer($item, $this->provider(['name' => 'Ubicación sin registrar']));
        $this->offer($item, $this->provider(['name' => 'Agotado cercano', 'pharmacy_latitude' => 19.4326, 'pharmacy_longitude' => -99.1332]), ['stock' => 0]);
        $this->offer($item, $this->provider(['name' => 'No autorizado', 'digital_pharmacy_enabled' => false]));
        $response = $this->getJson('/api/mobile/v1/pharmacy/catalog-items?latitude=19.4326&longitude=-99.1332')->assertOk()->assertJsonPath('data.0.nearest_offer_id', $near->id)->assertJsonCount(4, 'data.0.offers')->assertJsonPath('data.0.offers.0.provider.name', 'Cercano')->assertJsonPath('data.0.offers.0.provider.address', 'Centro CDMX');
        $offers = collect($response->json('data.0.offers'))->keyBy('id');
        $this->assertGreaterThan(100, $offers[$far->id]['provider']['distance_km']);
        $this->assertNull($offers->firstWhere('provider.name', 'Ubicación sin registrar')['provider']['distance_km']);
        $this->assertArrayNotHasKey('user_id', $offers[$near->id]['provider']);
        $this->getJson('/api/mobile/v1/pharmacy/catalog-items')->assertJsonPath('data.0.nearest_offer_id', null);
        $this->getJson('/api/mobile/v1/pharmacy/catalog-items?latitude=91&longitude=0')->assertUnprocessable();
        $this->getJson('/api/mobile/v1/pharmacy/catalog-items?latitude=10')->assertUnprocessable();
    }

    public function test_catalog_without_offers_is_visible_but_unavailable_and_filters_cover_all_pages(): void
    {
        $this->enable();
        $item = $this->item(['metadata' => ['distinctive_name' => 'Marca A']]);
        $this->getJson('/api/mobile/v1/pharmacy/catalog-items?category=Analgesia&brand=Marca%20A')->assertOk()->assertJsonPath('data.0.available', false)->assertJsonPath('data.0.price_from', null)->assertJsonCount(0, 'data.0.offers');
        $this->getJson('/api/mobile/v1/pharmacy/catalog-items?q=100000001')->assertJsonCount(1, 'data');
        $this->getJson('/api/mobile/v1/pharmacy/catalog-items/'.$item->id)->assertOk()->assertJsonPath('data.id', $item->id)->assertJsonPath('data.available', false);
        $this->getJson('/api/mobile/v1/pharmacy/catalog-items?category=Otra')->assertJsonCount(0, 'data');
        foreach (['inactive', 'shortage', 'discontinued'] as $status) {
            $item->update(['status' => $status]);
            $this->getJson('/api/mobile/v1/pharmacy/catalog-items')->assertJsonCount(0, 'data');
        }
        $item->update(['status' => 'active', 'controlled' => true]);
        $this->getJson('/api/mobile/v1/pharmacy/catalog-items')->assertJsonCount(0, 'data');
    }

    public function test_order_uses_the_offer_selected_by_the_user_and_never_substitutes_the_nearest_distributor(): void
    {
        $this->enable();
        $item = $this->item();
        $near = $this->offer($item, $this->provider(['name' => 'Cerca', 'pharmacy_latitude' => 19.43, 'pharmacy_longitude' => -99.13]), ['price' => '10.00']);
        $selected = $this->offer($item, $this->provider(['name' => 'Elegido', 'pharmacy_latitude' => 20.7, 'pharmacy_longitude' => -103.3]), ['price' => '32.50']);
        $this->getJson('/api/mobile/v1/pharmacy/catalog-items/'.$item->id.'?latitude=19.43&longitude=-99.13')->assertOk()->assertJsonPath('data.nearest_offer_id', $near->id)->assertJsonCount(2, 'data.offers');
        $patient = $this->user('patient');
        Patient::create(['user_id' => $patient->id, 'full_name' => 'Paciente de prueba', 'status' => 'active']);
        Sanctum::actingAs($patient, ['mobile:access']);
        $this->postJson('/api/mobile/v1/pharmacy/orders', ['client_request_id' => (string)Str::uuid(), 'items' => [['product_id' => $selected->id, 'quantity' => 2, 'provider_id' => $near->provider_id, 'unit_price' => 1]], 'delivery_method' => 'delivery', 'terms_accepted' => true, 'address' => ['name' => 'Prueba', 'street' => 'Calle', 'neighborhood' => 'Centro', 'postal_code' => '01000']])
            ->assertCreated()->assertJsonPath('data.items.0.product_id', $selected->id)->assertJsonPath('data.items.0.provider_id', $selected->provider_id)
            ->assertJsonPath('data.items.0.provider_name', 'Elegido')->assertJsonPath('data.items.0.catalog_item_id', $item->id)
            ->assertJsonPath('data.items.0.sku', $item->sku)->assertJsonPath('data.items.0.unit_price', '32.50')->assertJsonPath('data.subtotal', '65.00');
    }

    public function test_only_active_superadmin_changes_publication_and_status_and_location_changes_are_owned_and_validated(): void
    {
        $item = $this->item();
        $provider = $this->provider();
        $other = $this->provider();
        $this->actingAs($provider->user);
        $this->patchJson(route('superadmin.medications.publication', $item), ['digital_pharmacy_enabled' => false])->assertForbidden();
        $this->patchJson(route('superadmin.medications.status', $item), ['status' => 'inactive'])->assertForbidden();
        $this->patch(route('provider.pharmacy.settings'), ['name' => 'Mi farmacia', 'pharmacy_address' => 'Dirección pública', 'pharmacy_latitude' => 19.4, 'pharmacy_longitude' => -99.1, 'id' => $other->id, 'digital_pharmacy_enabled' => false])->assertSessionHasNoErrors();
        $this->assertSame('Dirección pública', $provider->fresh()->pharmacy_address);
        $this->assertNull($other->fresh()->pharmacy_address);
        $this->assertTrue($provider->fresh()->digital_pharmacy_enabled);
        $this->patch(route('provider.pharmacy.settings'), ['name' => 'Mi farmacia', 'pharmacy_latitude' => 200, 'pharmacy_longitude' => 0])->assertSessionHasErrors('pharmacy_latitude');
        $this->patch(route('provider.pharmacy.settings'), ['name' => 'Mi farmacia', 'pharmacy_latitude' => 19.4])->assertSessionHasErrors('pharmacy_longitude');
        $inactive = $this->user('superadmin');
        $inactive->update(['status' => 'inactive']);
        $this->actingAs($inactive)->patchJson(route('superadmin.medications.publication', $item), ['digital_pharmacy_enabled' => false])->assertForbidden();
        $this->actingAs($this->user('patient'))->get(route('provider.pharmacy.catalog'))->assertForbidden();
        $this->assertTrue($item->fresh()->digital_pharmacy_enabled);
    }
}
