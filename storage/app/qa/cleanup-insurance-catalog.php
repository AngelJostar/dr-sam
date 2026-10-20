<?php
require __DIR__.'/../../../vendor/autoload.php';
$app = require __DIR__.'/../../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$fixtures = json_decode(file_get_contents(__DIR__.'/insurance-catalog-fixtures.json'), true, flags: JSON_THROW_ON_ERROR);
$deleted = 0;
foreach ($fixtures as $fixture) {
    if (!preg_match('/^QA Insurance \d+ (gmm|life)$/', $fixture['name'])) throw new RuntimeException('Invalid QA fixture.');
    $plan = App\Models\InsurancePlan::query()->whereKey($fixture['id'])->where('name', $fixture['name'])->first();
    if (!$plan) continue;
    if ($plan->is_linked || $plan->documents()->exists()) throw new RuntimeException('QA fixture still linked or has documents.');
    $plan->delete();
    $deleted++;
}
echo "Removed {$deleted} exact temporary QA plans.\n";
