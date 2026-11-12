<?php
namespace Tests\Feature;

use App\Models\{User, Provider, PharmacyProduct};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PharmacyStoreAdminTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role = 'provider'): User
    {
        static $number = 0;
        $number++;
        return User::create(['name'=>'Farmacia prueba', 'username'=>'pharm'.$number, 'email'=>'pharm'.$number.'@test.local', 'role'=>$role, 'module'=>$role === 'provider' ? 'provider_medicines' : 'superadmin', 'status'=>'active']);
    }

    private function provider(User $user): Provider
    {
        return Provider::create(['user_id'=>$user->id, 'name'=>'Distribuidor', 'provider_type'=>'medicines', 'status'=>'active', 'digital_pharmacy_enabled'=>false]);
    }

    public function test_admin_pages_render_and_distributor_cannot_change_publication(): void
    {
        $admin = $this->user('superadmin');
        $this->actingAs($admin)->get(route('superadmin.digital-pharmacy'))->assertOk()->assertSee('Farmacia digital');
        $this->get(route('superadmin.digital-pharmacy', ['section'=>'orders']))->assertOk();
        $this->get(route('superadmin.distributors'))->assertOk();
        $this->get(route('superadmin.distributors', ['section'=>'create']))->assertOk();
        $user = $this->user(); $provider = $this->provider($user);
        $this->actingAs($user)->patch(route('superadmin.distributors.publication', $provider), ['digital_pharmacy_enabled'=>1])->assertForbidden();
        $this->assertFalse((bool)$provider->fresh()->digital_pharmacy_enabled);
    }

    public function test_missing_profile_denies_and_products_are_isolated(): void
    {
        $user = $this->user();
        $this->actingAs($user)->get(route('provider.pharmacy.products'))->assertNotFound();
        $own = $this->provider($user);
        $other = $this->provider($this->user());
        $product = PharmacyProduct::create(['provider_id'=>$other->id, 'name'=>'Producto ajeno', 'price'=>20, 'stock'=>5, 'status'=>'active']);
        $this->get(route('provider.pharmacy.products'))->assertOk()->assertDontSee('Producto ajeno');
        $this->patch(route('provider.pharmacy.products.update', $product), ['name'=>'Cambio'])->assertNotFound();
        $this->assertSame('Producto ajeno', $product->fresh()->name);
        $this->post(route('provider.pharmacy.products.store'), ['provider_id'=>$other->id, 'name'=>'Producto propio', 'price'=>25, 'stock'=>4, 'status'=>'active', 'requires_prescription'=>false])->assertRedirect();
        $this->assertDatabaseHas('pharmacy_products', ['name'=>'Producto propio', 'provider_id'=>$own->id]);
    }

    public function test_admin_distributor_creation_is_unpublished_and_password_is_hashed(): void
    {
        $this->actingAs($this->user('superadmin'))->post(route('superadmin.distributors.store'), ['name'=>'Nuevo', 'username'=>'nuevo_distribuidor', 'email'=>'nuevo@test.local', 'password'=>'TestPassword123!', 'password_confirmation'=>'TestPassword123!'])->assertRedirect();
        $user = User::where('email','nuevo@test.local')->firstOrFail();
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('TestPassword123!', $user->password));
        $this->assertFalse((bool)$user->provider->digital_pharmacy_enabled);
        $this->assertFalse($user->passwordless_review);
    }

    public function test_settings_persist_shipping_and_reject_credentials_in_urls(): void
    {
        $this->actingAs($this->user('superadmin'));
        $data = ['title'=>'Mi farmacia', 'subtitle'=>'Catálogo', 'accent_color'=>'#123456', 'enabled'=>true, 'shipping_fee'=>'49.50', 'delivery_estimate'=>'Por confirmar', 'purchase_terms'=>'https://example.com/terms', 'banner_image_url'=>'https://example.com/banner.png'];
        $this->put(route('superadmin.digital-pharmacy.settings'), $data)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('pharmacy_store_settings', ['id'=>1, 'title'=>'Mi farmacia', 'shipping_fee'=>49.50, 'enabled'=>true]);
        foreach (['banner_image_url', 'purchase_terms'] as $field) {
            $invalid = array_replace($data, [$field=>'https://user:password@example.com/image']);
            $this->putJson(route('superadmin.digital-pharmacy.settings'), $invalid)->assertUnprocessable()->assertJsonValidationErrors($field);
        }
        $this->assertDatabaseHas('pharmacy_store_settings', ['banner_image_url'=>'https://example.com/banner.png', 'purchase_terms'=>'https://example.com/terms']);
    }

    public function test_product_featured_persists_and_credential_url_is_rejected(): void
    {
        $user = $this->user(); $this->provider($user); $this->actingAs($user);
        $data = ['name'=>'Destacado', 'laboratory'=>'Laboratorio prueba', 'price'=>25, 'stock'=>5, 'status'=>'active', 'requires_prescription'=>false, 'featured'=>true, 'image_url'=>'https://example.com/product.png'];
        $this->post(route('provider.pharmacy.products.store'), $data)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('pharmacy_products', ['name'=>'Destacado', 'featured'=>true, 'laboratory'=>'Laboratorio prueba']);
        $this->postJson(route('provider.pharmacy.products.store'), array_replace($data, ['image_url'=>'https://user@example.com/image']))->assertUnprocessable()->assertJsonValidationErrors('image_url');
        $this->assertSame(1, PharmacyProduct::count());
    }

    public function test_secondary_admin_is_denied_every_administration_action(): void
    {
        $provider = $this->provider($this->user());
        $this->actingAs($this->user('admin'));
        $this->get(route('superadmin.digital-pharmacy'))->assertForbidden();
        $this->get(route('superadmin.distributors'))->assertForbidden();
        $this->put(route('superadmin.digital-pharmacy.settings'), [])->assertForbidden();
        $this->post(route('superadmin.distributors.store'), [])->assertForbidden();
        $this->patch(route('superadmin.distributors.publication', $provider), ['digital_pharmacy_enabled'=>1])->assertForbidden();
    }

    public function test_order_transitions_are_administrative_and_cancelled_is_terminal(): void
    {
        $admin = $this->user('superadmin');
        $order = \App\Models\PharmacyStoreOrder::create(['user_id'=>$this->user('patient')->id, 'client_request_id'=>(string)\Illuminate\Support\Str::uuid(), 'subtotal'=>25, 'total'=>25, 'items'=>[], 'address'=>[], 'terms_accepted_at'=>now()]);
        $this->actingAs($this->user('admin'))->patch(route('superadmin.digital-pharmacy.orders.status', $order), ['status'=>'reviewing'])->assertForbidden();
        $this->actingAs($admin)->patch(route('superadmin.digital-pharmacy.orders.status', $order), ['status'=>'ready'])->assertUnprocessable();
        $this->patch(route('superadmin.digital-pharmacy.orders.status', $order), ['status'=>'reviewing'])->assertRedirect();
        $this->assertSame('reviewing', $order->fresh()->status);
        $this->patch(route('superadmin.digital-pharmacy.orders.status', $order), ['status'=>'ready'])->assertRedirect();
        $this->patch(route('superadmin.digital-pharmacy.orders.status', $order), ['status'=>'cancelled'])->assertRedirect();
        foreach (['reviewing', 'ready', 'cancelled'] as $status) {
            $this->patch(route('superadmin.digital-pharmacy.orders.status', $order), ['status'=>$status])->assertUnprocessable();
        }
        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame('not_configured', $order->fresh()->payment_status);
    }
    public function test_prescription_download_requires_superadmin_and_exact_owner_patient_link(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        $patientUser = $this->user('patient');
        $patient = \App\Models\Patient::create(['user_id'=>$patientUser->id, 'full_name'=>'Paciente prueba']);
        $upload = \App\Models\PharmacyPrescriptionUpload::create(['user_id'=>$patientUser->id, 'patient_id'=>$patient->id, 'path'=>'pharmacy-prescriptions/test.pdf', 'file_name'=>'receta.pdf', 'mime_type'=>'application/pdf', 'size_bytes'=>20, 'status'=>'pending_review']);
        \Illuminate\Support\Facades\Storage::disk('local')->put($upload->path, 'private-test-content');
        $order = \App\Models\PharmacyStoreOrder::create(['user_id'=>$patientUser->id, 'prescription_upload_id'=>$upload->id, 'client_request_id'=>(string)\Illuminate\Support\Str::uuid(), 'subtotal'=>25, 'total'=>25, 'items'=>[], 'address'=>[], 'terms_accepted_at'=>now()]);
        $url = route('superadmin.digital-pharmacy.orders.prescription', $order);
        foreach (['provider', 'admin'] as $role) {
            $this->actingAs($this->user($role))->get($url)->assertForbidden();
        }
        $admin = $this->user('superadmin');
        $response = $this->actingAs($admin)->get($url)->assertOk()->assertDownload('receta.pdf')
            ->assertHeader('Content-Type','application/octet-stream')
            ->assertHeader('X-Content-Type-Options','nosniff')
            ->assertHeader('Content-Security-Policy', "default-src 'none'; sandbox");
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $audit = \App\Models\AuditLog::where('event','pharmacy.prescription.admin_downloaded')->firstOrFail();
        $this->assertStringNotContainsString('private-test-content', json_encode($audit->payload));
        $upload->update(['user_id'=>$this->user('patient')->id]);
        $this->get($url)->assertNotFound();
        $upload->update(['user_id'=>$patientUser->id]);
        $patient->update(['user_id'=>null]);
        $this->get($url)->assertNotFound();
    }
    public function test_order_contact_and_address_are_visible_only_to_superadmin(): void
    {
        $patient = $this->user('patient');
        \App\Models\PharmacyStoreOrder::create(['user_id'=>$patient->id, 'client_request_id'=>(string)\Illuminate\Support\Str::uuid(), 'subtotal'=>25, 'total'=>25, 'items'=>[], 'address'=>['name'=>'Destinatario de prueba', 'street'=>'Calle Privada 123', 'neighborhood'=>'Colonia Ejemplo', 'postal_code'=>'12345', 'references'=>'Puerta verde'], 'terms_accepted_at'=>now()]);
        $url = route('superadmin.digital-pharmacy', ['section'=>'orders']);
        $this->actingAs($this->user('superadmin'))->get($url)->assertOk()->assertSee($patient->name)->assertSee($patient->email)->assertSee('Destinatario de prueba')->assertSee('Calle Privada 123')->assertSee('Colonia Ejemplo')->assertSee('12345')->assertSee('Puerta verde');
        $this->actingAs($this->user('provider'))->get($url)->assertForbidden()->assertDontSee('Calle Privada 123');
    }
    public function test_provider_order_view_exposes_only_own_lines_and_subtotal(): void
    {
        $userA = $this->user(); $providerA = $this->provider($userA);
        $userB = $this->user(); $providerB = $this->provider($userB);
        $patient = $this->user('patient');
        $order = \App\Models\PharmacyStoreOrder::create(['user_id'=>$patient->id, 'client_request_id'=>(string)\Illuminate\Support\Str::uuid(), 'subtotal'=>927, 'total'=>977, 'shipping_fee'=>50, 'items'=>[
            ['provider_id'=>$providerA->id, 'product_id'=>1, 'name'=>'Producto A exclusivo', 'quantity'=>2, 'unit_price'=>'25.00', 'total'=>'50.00'],
            ['provider_id'=>$providerB->id, 'product_id'=>2, 'name'=>'Producto B secreto', 'quantity'=>1, 'unit_price'=>'877.00', 'total'=>'877.00'],
        ], 'address'=>['name'=>'Destinatario secreto','street'=>'Direccion secreta'], 'terms_accepted_at'=>now()]);
        $url = route('provider.pharmacy.orders');
        $response = $this->actingAs($userA)->get($url)->assertOk()->assertSee('Producto A exclusivo')->assertSee('50.00')->assertDontSee('Producto B secreto')->assertDontSee('977.00')->assertDontSee('Direccion secreta')->assertDontSee($patient->email);
        $row = $response->viewData('orders')->items()[0];
        $this->assertSame(['id','status','created_at','items','subtotal'], array_keys($row));
        $this->assertCount(1, $row['items']);
        $this->assertArrayNotHasKey('provider_id', $row['items'][0]);
        $this->actingAs($userB)->get($url)->assertOk()->assertSee('Producto B secreto')->assertDontSee('Producto A exclusivo')->assertDontSee('Direccion secreta');
        $this->actingAs($this->user())->get($url)->assertNotFound();
        $this->actingAs($this->user('patient'))->get($url)->assertForbidden();
    }
}