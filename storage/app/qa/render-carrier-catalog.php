<?php
require __DIR__.'/../../../vendor/autoload.php';
$app = require __DIR__.'/../../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['session.driver' => 'array']);
$admin = App\Models\User::query()->where('role', 'superadmin')->firstOrFail();
$countBefore = App\Models\InsuranceCarrier::count();
Illuminate\Support\Facades\DB::beginTransaction();
try {
    foreach (['A' => ['active', 2, 1], 'B' => ['active', 1, 2], 'C' => ['inactive', 1, 0]] as $label => [$status, $gmm, $life]) {
        $carrier = App\Models\InsuranceCarrier::create(['name' => 'Aseguradora '.$label, 'slug' => 'qa-carrier-'.uniqid(), 'status' => $status, 'type' => 'GMM']);
        foreach (['gmm' => $gmm, 'life' => $life] as $category => $count) {
            for ($i = 0; $i < $count; $i++) $carrier->plans()->create(['name' => 'Plan QA '.$label.' '.$i, 'category' => $category, 'status' => 'active', 'is_linked' => false, 'is_demo' => true]);
        }
    }
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    foreach (['all', 'active', 'inactive'] as $status) {
        Illuminate\Support\Facades\Auth::guard('web')->setUser($admin);
        $response = $kernel->handle(Illuminate\Http\Request::create('http://127.0.0.1:8000/superadmin/catalogo/insurance-carriers?view=carriers&status='.$status));
        if ($response->getStatusCode() !== 200) throw new RuntimeException('Render failed: '.$response->getStatusCode());
        $html = preg_replace('/(<input[^>]+name="_token"[^>]+value=")[^"]*/', '$1', $response->getContent());
        $html = preg_replace('/(<meta[^>]+name="csrf-token"[^>]+content=")[^"]*/', '$1', $html);
        file_put_contents(__DIR__.'/carriers-'.$status.'.html', $html);
    }
} finally {
    Illuminate\Support\Facades\DB::rollBack();
}
if (App\Models\InsuranceCarrier::count() !== $countBefore) throw new RuntimeException('Unexpected carrier count');
echo "Rendered three catalog states; temporary records rolled back.\n";
