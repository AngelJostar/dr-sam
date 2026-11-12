<?php
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\{User,Provider,PharmacyProduct,PharmacyStoreOrder,PharmacyStoreSetting};
class PharmacyVisualTest extends TestCase {
 use RefreshDatabase;
 public function test_render_fictitious_administrative_pages(): void {
  $admin=User::create(['name'=>'Administración de prueba','username'=>'qa-admin','email'=>'qa-admin@example.test','role'=>'superadmin','status'=>'active']);
  $seller=User::create(['name'=>'Distribuidor de prueba','username'=>'qa-seller','email'=>'qa-seller@example.test','role'=>'provider','status'=>'active','module'=>'provider_medicines']);
  $patient=User::create(['name'=>'Paciente ficticio','username'=>'qa-patient','email'=>'qa-patient@example.test','role'=>'patient','status'=>'active']);
  $provider=Provider::create(['user_id'=>$seller->id,'name'=>'Distribuidora Ejemplo','provider_type'=>'medicines','status'=>'active','digital_pharmacy_enabled'=>true]);
  $product=PharmacyProduct::create(['provider_id'=>$provider->id,'name'=>'Producto de demostración','description'=>'Producto ficticio exclusivo para verificar el diseño.','price'=>249.50,'stock'=>12,'status'=>'active','laboratory'=>'Laboratorio Ejemplo','category'=>'Bienestar']);
  PharmacyStoreSetting::create(['id'=>1,'title'=>'Farmacia digital Klini','subtitle'=>'Solicita tus productos con seguimiento administrativo','enabled'=>true,'shipping_fee'=>50,'accent_color'=>'#176b65']);
  PharmacyStoreOrder::create(['user_id'=>$patient->id,'client_request_id'=>(string)str()->uuid(),'subtotal'=>249.50,'shipping_fee'=>50,'total'=>299.50,'items'=>[['provider_id'=>$provider->id,'product_id'=>$product->id,'name'=>$product->name,'quantity'=>1,'unit_price'=>'249.50','total'=>'249.50']],'address'=>['name'=>'Persona ficticia','street'=>'Calle de Prueba 123','neighborhood'=>'Colonia Ejemplo','postal_code'=>'01000'],'terms_accepted_at'=>now()]);
  $pages=['visual'=>route('superadmin.digital-pharmacy'),'distributors'=>route('superadmin.distributors'),'create'=>route('superadmin.distributors',['section'=>'create']),'orders'=>route('superadmin.digital-pharmacy',['section'=>'orders'])];
  $this->actingAs($admin);
  foreach($pages as $name=>$url) { file_put_contents(storage_path('app/qa/pharmacy-'.$name.'.html'),$this->get($url)->assertOk()->getContent()); }
  $this->actingAs($seller);
  foreach(['product-create'=>route('provider.pharmacy.products',['section'=>'create']),'products'=>route('provider.pharmacy.products'),'provider-orders'=>route('provider.pharmacy.orders')] as $name=>$url) { file_put_contents(storage_path('app/qa/pharmacy-'.$name.'.html'),$this->get($url)->assertOk()->getContent()); }
 }
}
