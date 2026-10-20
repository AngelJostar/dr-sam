<?php

namespace App\Services\Platform;

use App\Models\Document;
use App\Models\ProviderRequest;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class ImportExpedientDocuments
{
    public const GROUPS = [
        'patient' => ['title' => 'Documentos del paciente', 'items' => [
            'ine' => 'INE', 'address' => 'Comprobante de domicilio', 'tax' => 'Cédula fiscal',
            'curp' => 'CURP', 'prescription' => 'Receta médica',
        ]],
        'regulatory' => ['title' => 'Documentos Regulatorio', 'items' => [
            'power' => 'Poder Simple (Cofepris)', 'reason' => 'Carta Motivo (Cofepris)', 'permit' => 'Permiso Cofepris',
            'address_verification' => 'Pantalla de verificación de domicilio localizado',
            'sat_opinion' => 'Opinión favorable del SAT reciente, cada inicio de mes solicitarla en adelante',
            'sat_authorization' => 'Encargo conferido dado de alta ante el SAT',
            'entrustment' => 'Carta encomienda',
            'contact_details' => 'Escrito de datos de contacto',
            'truth_declaration' => 'Manifestación bajo protesta de decir verdad, artículos 69-B y 49 Bis CFF',
            'assets_declaration' => 'Declaración de bienes muebles e inmuebles para desarrollar sus actividades',
            'vucem_seal' => 'Sello VUCEM (archivo .KEY, archivo .CER, clave del sello y clave de Web Service)',
            'rule_conformity' => 'Escrito de conformidad con la regla 1.4.14',
            'address_photos' => 'Fotos de la fachada e interior del domicilio declarado',
        ]],
        'logistics' => ['title' => 'Documentos Logística', 'items' => [
            'entrustment' => 'Carta Encomienda (Agencia Aduanal)', 'responsibility' => 'Carta Responsiva (Agencia Aduanal)',
        ]],
    ];

    public static function uploadTypes(string $group): array
    {
        return self::GROUPS[$group]['items'] + ($group === 'patient' ? ['summary' => 'Resumen clínico'] : []);
    }

    public static function canUploadFromSection(int $section, string $group): bool
    {
        return isset(self::GROUPS[$group]) && (in_array($section, [1, 6], true)
            || (in_array($section, [4, 5], true) && $group === 'logistics'));
    }

    // The caller supplies a record already scoped to the current provider.
    public function listing(?ProviderRequest $record, string $folio, User $viewer): array
    {
        $linked = Document::query()->whereIn('metadata->source', ['import_administration', 'import_expedient'])
            ->where(function ($query) use ($record, $folio, $viewer) {
                if ($record) {
                    $query->where('metadata->provider_request_id', $record->id);
                } else {
                    $query->whereNull('metadata->provider_request_id')->where('metadata->folio', $folio)->where('uploaded_by', $viewer->id);
                }
            })->get();
        $patientDocuments = ($record?->patient?->documents ?? collect())->reject(fn ($document) =>
            in_array(data_get($document->metadata, 'source'), ['import_administration', 'import_expedient'], true));
        $documents = $linked->concat($patientDocuments)->unique('id')->sortByDesc('id');

        return collect(self::GROUPS)->map(function ($group, $key) use ($documents) {
            return collect($group['items'])->map(function ($name, $type) use ($documents, $key) {
                $document = $documents->first(fn ($item) => data_get($item->metadata, 'document_group', 'patient') === $key
                    && (data_get($item->metadata, 'document_key') === $type
                        || mb_strtolower($item->document_type) === mb_strtolower($name)
                        || mb_strtolower(data_get($item->metadata, 'category', '')) === mb_strtolower($name))
                    && $item->file_path && Storage::disk('local')->exists($item->file_path));

                return ['key' => $type, 'name' => $name, 'filename' => $document?->name,
                    'url' => $document ? route('provider.import.documents.view', $document) : null];
            })->values()->all();
        })->all();
    }
}
