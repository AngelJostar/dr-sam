<?php
require __DIR__.'/../../../vendor/autoload.php';
$app = require __DIR__.'/../../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['session.driver' => 'array']);
session()->put('status', 'Institución de prueba actualizada.');
view()->share('errors', new Illuminate\Support\ViewErrorBag);
file_put_contents(__DIR__.'/superadmin-feedback-success.html', view('superadmin._feedback')->render());
session()->forget('status');
view()->share('errors', (new Illuminate\Support\ViewErrorBag)->put('default', new Illuminate\Support\MessageBag(['name' => ['El nombre de la institución es obligatorio.']])));
file_put_contents(__DIR__.'/superadmin-feedback-error.html', view('superadmin._feedback')->render());
echo "Feedback fixtures rendered.\n";
