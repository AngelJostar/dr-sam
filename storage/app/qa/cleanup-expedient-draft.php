<?php
require __DIR__.'/../../../vendor/autoload.php';
$app = require __DIR__.'/../../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$token = $argv[1] ?? '';
if (!Illuminate\Support\Str::isUuid($token)) exit(1);
$records = App\Models\ProviderRequest::where('payload->expedient->submission_token', $token)
    ->where('payload->source', 'import_expedient')->where('payload->expedient->first_name', 'QA flujo de expediente')
    ->where('status', 'draft')->whereNull('patient_id')->get();
foreach ($records as $record) {
    $documents = App\Models\Document::withTrashed()->where('metadata->provider_request_id', $record->id)->where('metadata->source', 'import_expedient')->get();
    foreach ($documents as $document) {
        if (str_starts_with($document->file_path, 'import-expedients/'.$record->id.'/')) Illuminate\Support\Facades\Storage::disk('local')->delete($document->file_path);
        $document->forceDelete();
    }
    App\Models\AuditLog::where('auditable_type', App\Models\ProviderRequest::class)->where('auditable_id', $record->id)->where('event', 'provider.expedient.draft_saved')->delete();
    $record->statusEvents()->delete();
    $record->delete();
}
echo "Temporary QA draft cleanup completed.\n";
