<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\PharmacyPrescriptionUpload;
use App\Models\PharmacyProduct;
use App\Models\PharmacyStoreSetting;
use App\Models\Prescription;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PharmacyStoreApiTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role = 'patient'): User
    {
        $id = Str::uuid();

        return User::create(['name' => 'Test', 'username' => 'test-'.$id, 'email' => $id.'@test.local', 'role' => $role, 'status' => 'active']);
    }

    private function patient(): User
    {
        $user = $this->user();
        Patient::create(['user_id' => $user->id, 'full_name' => 'Test patient', 'status' => 'active']);

        return $user;
    }

    private function product(bool $enabled = true): PharmacyProduct
    {
        $provider = Provider::create(['user_id' => $this->user('provider')->id, 'name' => 'Distribuidor test', 'provider_type' => 'medicines', 'status' => 'active', 'digital_pharmacy_enabled' => $enabled]);

        return PharmacyProduct::create(['provider_id' => $provider->id, 'name' => 'Producto test', 'price' => '12.35', 'stock' => 10, 'status' => 'active']);
    }

    private function enable(): void
    {
        PharmacyStoreSetting::create(['id' => 1, 'enabled' => true, 'title' => 'Farmacia', 'shipping_fee' => '20.00']);
    }

    private function payload(PharmacyProduct $p): array
    {
        return ['client_request_id' => (string) Str::uuid(), 'items' => [['product_id' => $p->id, 'quantity' => 2]], 'delivery_method' => 'delivery', 'terms_accepted' => true, 'address' => ['name' => 'Test', 'street' => 'Calle 1', 'neighborhood' => 'Centro', 'postal_code' => '01000']];
    }

    public function test_public_catalog_is_disabled_by_default_and_excludes_unapproved_or_unowned_products(): void
    {
        $p = $this->product();
        $this->getJson('/api/mobile/v1/pharmacy/catalog')->assertOk()->assertJsonPath('data.settings.enabled', false)->assertJsonCount(0, 'data.products');
        $this->getJson('/api/mobile/v1/pharmacy/products/'.$p->id)->assertNotFound();
        $this->enable();
        $disabled = $this->product(false);
        PharmacyProduct::create(['name' => 'Legacy unowned', 'status' => 'active', 'price' => 1]);
        $this->getJson('/api/mobile/v1/pharmacy/catalog')->assertOk()->assertJsonCount(1, 'data.products')->assertJsonPath('data.products.0.id', $p->id);
        $this->getJson('/api/mobile/v1/pharmacy/products/'.$disabled->id)->assertNotFound();
    }

    public function test_disabling_provider_or_owner_immediately_hides_products_even_direct_urls(): void
    {
        $this->enable();
        $p = $this->product();
        $this->getJson('/api/mobile/v1/pharmacy/products/'.$p->id)->assertOk();
        $p->provider->update(['digital_pharmacy_enabled' => false]);
        $this->getJson('/api/mobile/v1/pharmacy/products/'.$p->id)->assertNotFound();
        $p->provider->update(['digital_pharmacy_enabled' => true]);
        $p->provider->user->update(['status' => 'inactive']);
        $this->getJson('/api/mobile/v1/pharmacy/products')->assertJsonCount(0, 'data');
        $this->getJson('/api/mobile/v1/pharmacy/products/'.$p->id)->assertNotFound();
    }

    public function test_orders_require_patient_authentication_and_server_recalculates_idempotently_without_payment_or_stock_reservation(): void
    {
        $this->enable();
        $p = $this->product();
        $data = $this->payload($p);
        $this->postJson('/api/mobile/v1/pharmacy/orders', $data)->assertUnauthorized();
        Sanctum::actingAs($this->patient(), ['mobile:access']);
        $response = $this->postJson('/api/mobile/v1/pharmacy/orders', $data + ['total' => '0.01', 'payment_status' => 'paid']);
        $response->assertCreated()->assertJsonPath('data.subtotal', '24.70')->assertJsonPath('data.shipping_fee', '20.00')->assertJsonPath('data.total', '44.70')->assertJsonPath('data.payment_status', 'not_configured')->assertJsonPath('data.status', 'submitted');
        $id = $response->json('data.id');
        $this->postJson('/api/mobile/v1/pharmacy/orders', $data)->assertOk()->assertJsonPath('data.id', $id);
        $this->assertDatabaseCount('pharmacy_store_orders', 1);
        $this->assertSame(10, $p->fresh()->stock);
        Sanctum::actingAs($this->patient(), ['mobile:access']);
        $this->getJson('/api/mobile/v1/pharmacy/orders/'.$id)->assertNotFound();
        $this->getJson('/api/mobile/v1/pharmacy/orders')->assertJsonCount(0, 'data');
    }

    public function test_submission_rechecks_publication_and_stock_and_requires_prescription(): void
    {
        $this->enable();
        $p = $this->product();
        Sanctum::actingAs($this->patient(), ['mobile:access']);
        $p->update(['stock' => 1]);
        $this->postJson('/api/mobile/v1/pharmacy/orders', $this->payload($p))->assertUnprocessable();
        $p->update(['stock' => 10]);
        $p->provider->update(['digital_pharmacy_enabled' => false]);
        $this->postJson('/api/mobile/v1/pharmacy/orders', $this->payload($p))->assertUnprocessable();
        $p->provider->update(['digital_pharmacy_enabled' => true]);
        $p->update(['requires_prescription' => true]);
        $this->postJson('/api/mobile/v1/pharmacy/orders', $this->payload($p))->assertUnprocessable()->assertJsonStructure(['error' => ['details' => ['fields' => ['prescription_id']]]]);
        $this->assertDatabaseCount('pharmacy_store_orders', 0);
    }

    public function test_prescription_must_belong_to_patient_and_is_never_automatically_approved(): void
    {
        $this->enable();
        $p = $this->product();
        $p->update(['requires_prescription' => true]);
        $owner = $this->patient();
        $other = $this->patient();
        $rx = Prescription::create(['patient_id' => $owner->patient->id, 'status' => 'issued']);
        Sanctum::actingAs($other, ['mobile:access']);
        $this->postJson('/api/mobile/v1/pharmacy/orders', $this->payload($p) + ['prescription_id' => $rx->id])->assertUnprocessable();
        Sanctum::actingAs($owner, ['mobile:access']);
        $this->postJson('/api/mobile/v1/pharmacy/orders', $this->payload($p) + ['prescription_id' => $rx->id])->assertCreated()->assertJsonPath('data.prescription_status', 'pending_review');
    }

    public function test_controlled_and_cold_chain_are_not_public_without_supported_fulfilment(): void
    {
        $this->enable();
        $p = $this->product();
        $p->update(['controlled' => true]);
        $this->getJson('/api/mobile/v1/pharmacy/products/'.$p->id)->assertNotFound();
        $p->update(['controlled' => false, 'cold_chain' => true]);
        $this->getJson('/api/mobile/v1/pharmacy/products/'.$p->id)->assertNotFound();
    }

    public function test_inactive_or_wrong_role_tokens_cannot_submit_and_consent_is_required(): void
    {
        $this->enable();
        $p = $this->product();
        Sanctum::actingAs($this->user('provider'), ['mobile:access']);
        $this->postJson('/api/mobile/v1/pharmacy/orders', $this->payload($p))->assertForbidden();
        $patient = $this->patient();
        $patient->update(['status' => 'inactive']);
        Sanctum::actingAs($patient, ['mobile:access']);
        $this->postJson('/api/mobile/v1/pharmacy/orders', $this->payload($p))->assertForbidden();
        Sanctum::actingAs($this->patient(), ['mobile:access']);
        $data = $this->payload($p);
        $data['terms_accepted'] = false;
        $this->postJson('/api/mobile/v1/pharmacy/orders', $data)->assertUnprocessable();
    }

    public function test_uploaded_prescription_must_match_account_and_patient_and_stays_pending(): void
    {
        $this->enable();
        $product = $this->product();
        $product->update(['requires_prescription' => true]);
        $owner = $this->patient();
        $other = $this->patient();
        $upload = PharmacyPrescriptionUpload::create([
            'user_id' => $owner->id, 'patient_id' => $owner->patient->id,
            'path' => 'private-test.pdf', 'file_name' => 'test.pdf',
            'mime_type' => 'application/pdf', 'size_bytes' => 10, 'status' => 'pending_review',
        ]);
        Sanctum::actingAs($other, ['mobile:access']);
        $this->postJson('/api/mobile/v1/pharmacy/orders', $this->payload($product) + ['prescription_upload_id' => $upload->id])->assertUnprocessable();
        Sanctum::actingAs($owner, ['mobile:access']);
        $this->postJson('/api/mobile/v1/pharmacy/orders', $this->payload($product) + ['prescription_upload_id' => $upload->id])
            ->assertCreated()->assertJsonPath('data.prescription_status', 'pending_review');
        $upload->update(['status' => 'rejected']);
        $this->postJson('/api/mobile/v1/pharmacy/orders', $this->payload($product) + ['prescription_upload_id' => $upload->id])->assertUnprocessable();
    }

    public function test_large_but_valid_lines_cannot_overflow_order_decimal_columns(): void
    {
        $this->enable();
        $product = $this->product();
        $product->update(['price' => '999999.99', 'stock' => 100]);
        $data = $this->payload($product);
        $data['items'] = [];
        for ($i = 0; $i < 11; $i++) {
            $copy = $product->replicate();
            $copy->save();
            $data['items'][] = ['product_id' => $copy->id, 'quantity' => 99];
        }
        Sanctum::actingAs($this->patient(), ['mobile:access']);
        $this->postJson('/api/mobile/v1/pharmacy/orders', $data)->assertUnprocessable();
        $this->assertDatabaseCount('pharmacy_store_orders', 0);
    }

    public function test_amount_limit_accepts_exact_boundary_and_rejects_one_cent_more(): void
    {
        $this->enable();
        $product = $this->product();
        $product->update(['price' => '999999.99']);
        PharmacyStoreSetting::findOrFail(1)->update(['shipping_fee' => '0.01']);
        Sanctum::actingAs($this->patient(), ['mobile:access']);
        $data = $this->payload($product);
        $data['items'][0]['quantity'] = 1;
        $this->postJson('/api/mobile/v1/pharmacy/orders', $data)->assertCreated()->assertJsonPath('data.total', '1000000.00');
        PharmacyStoreSetting::findOrFail(1)->update(['shipping_fee' => '0.02']);
        $data['client_request_id'] = (string) Str::uuid();
        $this->postJson('/api/mobile/v1/pharmacy/orders', $data)->assertUnprocessable();
    }
}
