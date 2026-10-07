<?php
require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$units = App\Models\MedicalUnit::query()->where('external_id', 'demo-hospital-general-dr-sam')->where('name', 'Hospital General Demo Dr. Sam')->update(['name' => 'Hospital General Demo Klini']);
$hospitals = App\Models\Hospital::query()->where('rfc', 'HGD260101AB1')->where('name', 'Hospital General Demo Dr. Sam')->update(['name' => 'Hospital General Demo Klini']);
echo "Nombres de demostracion actualizados: $units unidades, $hospitals hospitales.\n";
