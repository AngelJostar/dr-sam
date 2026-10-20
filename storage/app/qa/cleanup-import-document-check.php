<?php
require __DIR__.'/../../../vendor/autoload.php';
$app = require __DIR__.'/../../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$nonce = $argv[1] ?? '';
if (!preg_match('/^\d{13}$/', $nonce)) throw new RuntimeException('Identificador de prueba inválido.');
$names = array_map(fn ($group) => 'qa-import-docs-'.$nonce.'-'.$group.'.png', ['patient', 'regulatory', 'logistics']);
$documents = App\Models\Document::whereIn('name', $names)->where('metadata->source', 'import_administration')
    ->where('metadata->folio', 'IMP-2026-005')->whereHas('uploadedBy', fn ($q) => $q->where('username', 'superadmin'))->get();
if ($documents->count() > 3) throw new RuntimeException('Número inesperado de archivos de prueba.');
foreach ($documents as $document) {
    if (!str_starts_with($document->file_path, 'import-documents/'.$document->uploaded_by.'/')) throw new RuntimeException('Ruta de archivo inesperada.');
    Illuminate\Support\Facades\Storage::disk('local')->delete($document->file_path);
    $document->forceDelete();
}
echo 'Archivos temporales de esta comprobación eliminados: '.$documents->count().PHP_EOL;
