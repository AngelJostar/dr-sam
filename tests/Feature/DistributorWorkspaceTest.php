<?php

namespace Tests\Feature;

use App\Models\{PharmacyProduct, PharmacyStoreOrder, PharmacyStoreSetting, Provider, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DistributorWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    private function distributor(): Provider
    {
        $user = User::create(['name' => 'Distribuidor prueba', 'username' => (string) Str::uuid(), 'role' => 'provider', 'module' => 'provider_medicines', 'status' => 'active']);
        return Provider::create(['user_id' => $user->id, 'name' => 'Mi distribuidora', 'provider_type' => 'medicines', 'status' => 'active', 'digital_pharmacy_enabled' => true]);
    }

    private function product(Provider $provider, array $changes = []): PharmacyProduct
    {
        return PharmacyProduct::create(array_replace(['provider_id' => $provider->id, 'name' => 'Producto propio', 'price' => '50.00', 'stock' => 10, 'status' => 'active'], $changes));
    }

    public function test_catalog_groups_complete_presentations_and_filters_only_own_products(): void
    {
        $provider = $this->distributor();
        foreach (range(1, 18) as $pack) {
            $this->product($provider, ['name' => 'Medicamento A · Paquete de '.$pack, 'presentation' => $pack.' cajas', 'price' => $pack * 20, 'stock' => $pack === 1 ? 0 : 12]);
        }
        $this->product($provider, ['name' => 'Medicamento A · Paquete de 1', 'laboratory' => 'Otro laboratorio']);
        $this->product($this->distributor(), ['name' => 'Catálogo ajeno secreto']);
        $response = $this->actingAs($provider->user)->get(route('provider.pharmacy.products'))->assertOk()->assertDontSee('Catálogo ajeno secreto');
        $this->assertSame(2, $response->viewData('metrics')['products']);
        $this->assertSame(19, $response->viewData('metrics')['presentations']);
        $group = collect($response->viewData('groups')->items())->firstWhere('laboratory', null);
        $this->assertCount(18, $group['items']);
        $filtered = $this->get(route('provider.pharmacy.products', ['q' => 'Medicamento', 'stock' => 'empty', 'status' => 'active']))->assertOk();
        $this->assertSame([0], $filtered->viewData('products')->pluck('stock')->all());
        $this->assertSame(19, $filtered->viewData('metrics')['presentations']);
        $this->get(route('provider.pharmacy.products', ['q' => 'inexistente']))->assertOk()->assertSee('Sin coincidencias');
    }

    public function test_menu_sections_are_available_and_reject_other_profiles_and_foreign_editors(): void
    {
        $provider = $this->distributor();
        $foreign = $this->product($this->distributor(), ['name' => 'Producto secreto externo']);
        $this->actingAs($provider->user);
        foreach (['summary', 'inventory', 'deliveries', 'store', 'reports', 'settings'] as $section) {
            $this->get(route('provider.pharmacy.workspace', $section))->assertOk()->assertSee('Solicitudes y pedidos')->assertSee('Configuración')->assertDontSee('Producto secreto externo');
        }
        $this->get(route('provider.pharmacy.products', ['edit' => $foreign->id]))->assertNotFound();
        $this->get('/providers/medicines/panel/invalid')->assertNotFound();
        $other = User::create(['name' => 'Otro proveedor', 'username' => (string) Str::uuid(), 'role' => 'provider', 'module' => 'provider_npt', 'status' => 'active']);
        $this->actingAs($other)->get(route('provider.pharmacy.workspace', 'summary'))->assertNotFound();
        $other->update(['role' => 'patient']);
        $this->get(route('provider.pharmacy.workspace', 'summary'))->assertForbidden();
    }

    public function test_inventory_changes_are_owned_validated_and_detect_stale_stock(): void
    {
        $provider = $this->distributor();
        $product = $this->product($provider);
        $foreign = $this->product($this->distributor());
        $this->actingAs($provider->user);
        $this->patch(route('provider.pharmacy.inventory.update', $product), ['stock' => 8, 'current_stock' => 10, 'provider_id' => $foreign->provider_id, 'price' => 1])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(8, $product->fresh()->stock);
        $this->assertSame('50.00', $product->fresh()->price);
        $this->patchJson(route('provider.pharmacy.inventory.update', $product), ['stock' => 7, 'current_stock' => 10])->assertUnprocessable();
        $this->patchJson(route('provider.pharmacy.inventory.update', $product), ['stock' => -1, 'current_stock' => 8])->assertUnprocessable();
        $this->patchJson(route('provider.pharmacy.inventory.update', $foreign), ['stock' => 0, 'current_stock' => 10])->assertNotFound();
        $this->assertSame(8, $product->fresh()->stock);
        $this->assertSame(10, $foreign->fresh()->stock);
    }

    public function test_store_visibility_respects_both_authorizations_and_product_rules(): void
    {
        $provider = $this->distributor();
        $visible = $this->product($provider);
        $this->product($provider, ['name' => 'Inactivo', 'status' => 'inactive']);
        $this->product($provider, ['name' => 'Controlado', 'controlled' => true]);
        $this->product($this->distributor(), ['name' => 'Ajeno']);
        $this->actingAs($provider->user);
        $url = route('provider.pharmacy.workspace', ['section' => 'store', 'visibility' => 'visible']);
        $this->assertCount(0, $this->get($url)->assertOk()->viewData('products'));
        PharmacyStoreSetting::create(['id' => 1, 'enabled' => true]);
        $this->assertSame([$visible->id], $this->get($url)->assertOk()->viewData('products')->pluck('id')->all());
        $provider->update(['digital_pharmacy_enabled' => false]);
        $this->assertCount(0, $this->get($url)->assertOk()->viewData('products'));
    }

    public function test_reports_and_delivery_followup_never_expose_other_suppliers_or_patient_data(): void
    {
        $provider = $this->distributor();
        $other = $this->distributor();
        $patient = User::create(['name' => 'Paciente secreto', 'username' => (string) Str::uuid(), 'email' => 'secreto@test.local', 'role' => 'patient', 'status' => 'active']);
        foreach (['submitted', 'ready', 'cancelled'] as $status) {
            PharmacyStoreOrder::create(['user_id' => $patient->id, 'client_request_id' => (string) Str::uuid(), 'subtotal' => 950, 'total' => 950, 'status' => $status, 'items' => [
                ['provider_id' => $provider->id, 'name' => 'Producto propio de solicitud', 'quantity' => 2, 'unit_price' => 25],
                ['provider_id' => $other->id, 'name' => 'Producto ajeno secreto', 'quantity' => 1, 'unit_price' => 900],
            ], 'address' => ['street' => 'Dirección privada'], 'terms_accepted_at' => now()]);
        }
        $this->actingAs($provider->user);
        foreach (['summary', 'reports', 'deliveries'] as $section) {
            $response = $this->get(route('provider.pharmacy.workspace', $section))->assertOk()->assertDontSee('Producto ajeno secreto')->assertDontSee('Dirección privada')->assertDontSee('secreto@test.local');
            foreach ($response->viewData('orders') as $order) {
                $this->assertSame('50.00', $order['subtotal']);
                $this->assertCount(1, $order['items']);
            }
            if ($section === 'deliveries') {
                $this->assertCount(2, $response->viewData('orders'));
                $response->assertSee('todavía no hay guías ni entregas registradas');
            }
        }
        $response = $this->get(route('provider.pharmacy.orders', ['order_status' => 'ready', 'q' => 'propio']))->assertOk();
        $this->assertCount(1, $response->viewData('orders'));
        $this->assertSame('ready', $response->viewData('orders')->items()[0]['status']);
    }

    public function test_configuration_cannot_modify_publication_or_account_permissions(): void
    {
        $provider = $this->distributor();
        $provider->update(['digital_pharmacy_enabled' => false]);
        $this->actingAs($provider->user)->patch(route('provider.pharmacy.settings'), ['name' => 'Nuevo nombre comercial', 'digital_pharmacy_enabled' => true, 'role' => 'superadmin', 'status' => 'inactive'])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('Nuevo nombre comercial', $provider->fresh()->name);
        $this->assertFalse($provider->fresh()->digital_pharmacy_enabled);
        $this->assertSame('active', $provider->fresh()->status);
        $this->assertSame('provider', $provider->user->fresh()->role);
    }

    public function test_validation_reopens_the_correct_editor_and_restores_input(): void
    {
        $provider = $this->distributor();
        $product = $this->product($provider);
        $url = route('provider.pharmacy.products', ['section' => 'create']);
        $this->actingAs($provider->user)->from($url)->patch(route('provider.pharmacy.products.update', $product), [
            '_editor' => $product->id, 'name' => 'Nombre pendiente de guardar', 'price' => -1,
            'stock' => 8, 'status' => 'active', 'requires_prescription' => false,
        ])->assertRedirect($url)->assertSessionHasErrors('price');
        $html = $this->get($url)->assertOk()->assertSee('Nombre pendiente de guardar')->getContent();
        preg_match_all('/<dialog[^>]*data-distributor-auto-open[^>]*>/', $html, $dialogs);
        $this->assertCount(1, $dialogs[0]);
        $this->assertStringContainsString('id="distributor-product-'.$product->id.'"', $dialogs[0][0]);
        $this->assertSame('Producto propio', $product->fresh()->name);
    }

    private function columnValue($response, string $column, string $label): string
    {
        return collect($response->viewData('columnFilters')['columns'][$column]['options'])->firstWhere('label', $label)['value'];
    }

    public function test_column_filters_cover_all_pages_combine_columns_and_keep_provider_scope(): void
    {
        $provider = $this->distributor();
        $oldest = $this->product($provider, ['name' => 'Primero', 'stock' => 0]);
        $second = $this->product($provider, ['name' => 'Segundo', 'stock' => 2]);
        foreach (range(1, 18) as $number) {
            $this->product($provider, ['name' => 'Resto '.$number, 'stock' => 8]);
        }
        $this->product($provider, ['name' => 'Inactivo', 'stock' => 0, 'status' => 'inactive']);
        $this->product($this->distributor(), ['name' => 'Catálogo ajeno confidencial', 'stock' => 999]);
        $url = route('provider.pharmacy.workspace', 'inventory');
        $first = $this->actingAs($provider->user)->get($url)->assertOk()->assertDontSee('Catálogo ajeno confidencial');
        $this->assertFalse($first->viewData('products')->contains('id', $oldest->id));
        $selected = [
            'stock' => [$this->columnValue($first, 'stock', '0 · Agotado'), $this->columnValue($first, 'stock', '2 ud.')],
            'status' => [$this->columnValue($first, 'status', 'Activo')],
        ];
        $filtered = $this->get($url.'?'.http_build_query(['columns' => json_encode($selected)]))->assertOk();
        $this->assertEqualsCanonicalizing([$oldest->id, $second->id], $filtered->viewData('products')->pluck('id')->all());
        $this->assertSame(2, $filtered->viewData('products')->total());
        $this->assertSame(21, $filtered->viewData('metrics')['presentations']);
        $filtered->assertSee('2 columnas filtradas')->assertSee('Limpiar filtros de columnas');
        $none = $this->get($url.'?'.http_build_query(['columns' => json_encode(['stock' => []])]))->assertOk();
        $this->assertSame(0, $none->viewData('products')->total());
        $this->assertNotEmpty($none->viewData('columnFilters')['columns']['stock']['options']);
        $active = json_encode(['status' => $selected['status']]);
        $page = $this->get($url.'?'.http_build_query(['columns' => $active, 'page' => 2]))->assertOk();
        $this->assertSame(20, $page->viewData('products')->total());
        parse_str(parse_url($page->viewData('products')->previousPageUrl(), PHP_URL_QUERY), $query);
        $this->assertSame($active, $query['columns']);
    }

    public function test_column_filters_apply_to_presentations_before_grouping_and_store_visibility(): void
    {
        $provider = $this->distributor();
        $small = $this->product($provider, ['name' => 'Medicamento · Paquete de 2', 'price' => 20]);
        $large = $this->product($provider, ['name' => 'Medicamento · Paquete de 4', 'price' => 40, 'status' => 'inactive']);
        $this->actingAs($provider->user);
        $catalog = $this->get(route('provider.pharmacy.products'))->assertOk();
        $selected = ['price' => [$this->columnValue($catalog, 'price', '$20.00')]];
        $filtered = $this->get(route('provider.pharmacy.products', ['columns' => json_encode($selected)]))->assertOk();
        $this->assertSame([$small->id], $filtered->viewData('products')->pluck('id')->all());
        $this->assertCount(1, $filtered->viewData('groups')->items()[0]['items']);
        PharmacyStoreSetting::updateOrCreate(['id' => 1], ['enabled' => true]);
        $store = $this->get(route('provider.pharmacy.workspace', 'store'))->assertOk();
        $selected = ['visibility' => [$this->columnValue($store, 'visibility', 'No visible')]];
        $filtered = $this->get(route('provider.pharmacy.workspace', ['section' => 'store', 'columns' => json_encode($selected)]))->assertOk();
        $this->assertSame([$large->id], $filtered->viewData('products')->pluck('id')->all());
    }

    public function test_order_column_filters_use_only_owned_lines_in_all_order_tables(): void
    {
        $provider = $this->distributor();
        $other = $this->distributor();
        $patient = User::create(['name' => 'Paciente privado', 'username' => (string) Str::uuid(), 'role' => 'patient', 'status' => 'active']);
        $wanted = null;
        foreach (range(1, 18) as $number) {
            $order = PharmacyStoreOrder::create(['user_id' => $patient->id, 'client_request_id' => (string) Str::uuid(), 'subtotal' => 950, 'total' => 950,
                'status' => $number === 1 ? 'ready' : 'submitted', 'items' => [
                    ['provider_id' => $provider->id, 'name' => $number === 1 ? 'Producto especial' : 'Producto común', 'quantity' => 2, 'unit_price' => 25],
                    ['provider_id' => $other->id, 'name' => 'Producto ajeno privado', 'quantity' => 1, 'unit_price' => 900],
                ], 'address' => ['street' => 'Domicilio privado'], 'terms_accepted_at' => now()]);
            if ($number === 1) $wanted = $order;
        }
        $this->actingAs($provider->user);
        foreach ([route('provider.pharmacy.orders'), route('provider.pharmacy.workspace', 'deliveries'), route('provider.pharmacy.workspace', 'summary')] as $url) {
            $first = $this->get($url)->assertOk()->assertDontSee('Producto ajeno privado')->assertDontSee('Paciente privado')->assertDontSee('Domicilio privado');
            $selected = [
                'products' => [$this->columnValue($first, 'products', 'Producto especial')],
                'subtotal' => [$this->columnValue($first, 'subtotal', '$50.00')],
                'order_status' => [$this->columnValue($first, 'order_status', 'Listo para seguimiento')],
            ];
            $filtered = $this->get($url.'?'.http_build_query(['columns' => json_encode($selected)]))->assertOk();
            $this->assertSame([$wanted->id], $filtered->viewData('orders')->pluck('id')->all());
        }
    }

    public function test_empty_tables_keep_column_buttons_and_reject_invalid_filter_payloads(): void
    {
        $provider = $this->distributor();
        $this->actingAs($provider->user);
        foreach ([route('provider.pharmacy.products'), route('provider.pharmacy.orders'), route('provider.pharmacy.workspace', 'inventory'), route('provider.pharmacy.workspace', 'store'), route('provider.pharmacy.workspace', 'summary'), route('provider.pharmacy.workspace', 'deliveries')] as $url) {
            $response = $this->get($url)->assertOk();
            $this->assertSame($url === route('provider.pharmacy.workspace', 'store') ? 6 : 4, substr_count($response->getContent(), 'data-distributor-column='));
        }
        foreach (['{invalid', 'null', '"text"', '{"unknown":[]}', '{"stock":"all"}', '{"stock":[["invalid"]]}'] as $payload) {
            $this->getJson(route('provider.pharmacy.workspace', ['section' => 'inventory', 'columns' => $payload]))->assertUnprocessable();
        }
    }

    public function test_store_switch_persists_only_own_product_status_and_updates_mobile_visibility(): void
    {
        $provider = $this->distributor();
        $product = $this->product($provider);
        $foreign = $this->product($this->distributor());
        PharmacyStoreSetting::updateOrCreate(['id' => 1], ['enabled' => true]);
        $store = route('provider.pharmacy.workspace', 'store');
        $url = route('provider.pharmacy.products.status', $product);
        $this->actingAs($provider->user)->get($store)->assertOk()->assertSee('role="switch"', false)->assertSee('aria-checked="true"', false);
        $this->from($store)->patch($url, ['status' => 'inactive', 'price' => 1, 'stock' => 0, 'provider_id' => $foreign->provider_id])->assertRedirect($store)->assertSessionHasNoErrors();
        $product->refresh();
        $this->assertSame('inactive', $product->status);
        $this->assertSame('50.00', $product->price);
        $this->assertSame(10, $product->stock);
        $this->assertSame($provider->id, $product->provider_id);
        $this->getJson('/api/mobile/v1/pharmacy/products/'.$product->id)->assertNotFound();
        $this->get($store)->assertOk()->assertSee('aria-checked="false"', false);
        foreach (range(1, 2) as $attempt) {
            $this->patch($url, ['status' => 'active'])->assertRedirect()->assertSessionHasNoErrors();
            $this->assertSame('active', $product->fresh()->status);
        }
        $this->getJson('/api/mobile/v1/pharmacy/products/'.$product->id)->assertOk();
        $this->patchJson(route('provider.pharmacy.products.status', $foreign), ['status' => 'inactive'])->assertNotFound();
        $this->assertSame('active', $foreign->fresh()->status);
        $this->patchJson($url, ['status' => 'published'])->assertUnprocessable();
        $this->patchJson($url, [])->assertUnprocessable();
        $this->actingAs(User::create(['name' => 'Paciente', 'username' => (string) Str::uuid(), 'role' => 'patient', 'status' => 'active']))
            ->patchJson($url, ['status' => 'inactive'])->assertForbidden();
    }

    public function test_activating_a_product_does_not_bypass_store_or_provider_publication_rules(): void
    {
        $provider = $this->distributor();
        $product = $this->product($provider, ['status' => 'inactive']);
        $this->actingAs($provider->user)->patch(route('provider.pharmacy.products.status', $product), ['status' => 'active'])->assertRedirect();
        $this->getJson('/api/mobile/v1/pharmacy/products/'.$product->id)->assertNotFound();
        PharmacyStoreSetting::updateOrCreate(['id' => 1], ['enabled' => true]);
        $provider->update(['digital_pharmacy_enabled' => false]);
        $this->patch(route('provider.pharmacy.products.status', $product), ['status' => 'active'])->assertRedirect();
        $this->getJson('/api/mobile/v1/pharmacy/products/'.$product->id)->assertNotFound();
        $provider->update(['digital_pharmacy_enabled' => true]);
        $product->update(['controlled' => true]);
        $this->patch(route('provider.pharmacy.products.status', $product), ['status' => 'active'])->assertRedirect();
        $this->getJson('/api/mobile/v1/pharmacy/products/'.$product->id)->assertNotFound();
    }

    public function test_sku_is_editable_unique_per_distributor_and_filterable_in_first_store_column(): void
    {
        $provider = $this->distributor();
        $other = $this->distributor();
        $this->product($other, ['sku' => 'SKU-001']);
        $legacy = $this->product($provider);
        $payload = ['name' => 'Producto con SKU', 'sku' => 'SKU-001', 'price' => 12, 'stock' => 3, 'status' => 'active', 'requires_prescription' => false];
        $this->actingAs($provider->user)->post(route('provider.pharmacy.products.store'), $payload)->assertRedirect()->assertSessionHasNoErrors();
        $created = PharmacyProduct::where('provider_id', $provider->id)->where('sku', 'SKU-001')->firstOrFail();
        $this->patch(route('provider.pharmacy.products.update', $created), $payload)->assertRedirect()->assertSessionHasNoErrors();
        $this->patchJson(route('provider.pharmacy.products.update', $legacy), $payload)->assertUnprocessable()->assertJsonValidationErrors('sku');
        $this->postJson(route('provider.pharmacy.products.store'), $payload)->assertUnprocessable()->assertJsonValidationErrors('sku');
        $this->patchJson(route('provider.pharmacy.products.update', $created), array_replace($payload, ['sku' => str_repeat('x', 101)]))->assertUnprocessable()->assertJsonValidationErrors('sku');
        $url = route('provider.pharmacy.workspace', 'store');
        $store = $this->get($url)->assertOk()->assertSee('Sin SKU')->assertSee('SKU-001');
        preg_match('/<thead>.*?<th[^>]*>(.*?)<\/th>/s', $store->getContent(), $header);
        $this->assertStringContainsString('Filtrar SKU', $header[1]);
        $selection = json_encode(['sku' => [$this->columnValue($store, 'sku', 'SKU-001')]]);
        $filtered = $this->get($url.'?'.http_build_query(['columns' => $selection]))->assertOk();
        $this->assertSame([$created->id], $filtered->viewData('products')->pluck('id')->all());
        $this->patch(route('provider.pharmacy.products.update', $created), array_replace($payload, ['sku' => null]))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertNull($created->fresh()->sku);
    }
}
