<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Doctor;
use App\Models\MedicalUnit;
use App\Models\Patient;
use App\Models\Provider;
use App\Models\ProviderRequest;
use App\Services\Platform\PlatformAuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ImportExpedientController extends Controller
{
    private function provider(Request $request): ?Provider
    {
        if ($request->user()->role !== 'provider') return null;
        $provider = Provider::query()->where('user_id', $request->user()->id)->where('provider_type', 'import')->where('status', 'active')->first();
        abort_unless($provider, 403);
        return $provider;
    }

    private function candidates(Request $request, array $data)
    {
        $provider = $this->provider($request);
        $name = trim(implode(' ', array_filter([$data['first_name'] ?? '', $data['first_surname'] ?? '', $data['second_surname'] ?? ''])));
        return Patient::query()->when($provider, fn ($query) => $query->whereIn('id', ProviderRequest::query()->select('patient_id')->where('provider_id', $provider->id)->whereNotNull('patient_id')))
            ->where(function ($query) use ($data, $name) {
                $query->where(fn ($match) => $match->where('full_name', $name)->whereDate('birth_date', $data['birth_date'] ?? '1900-01-01'));
                if (!empty($data['curp'])) $query->orWhere('curp', mb_strtoupper($data['curp']));
            })->limit(10)->get();
    }

    public function matches(Request $request): JsonResponse
    {
        $data = $request->validate(['first_name' => 'required|string|max:80', 'first_surname' => 'required|string|max:80',
            'second_surname' => 'nullable|string|max:80', 'birth_date' => 'required|date|before_or_equal:today', 'curp' => 'nullable|string|size:18']);
        return response()->json(['matches' => $this->candidates($request, $data)->map(fn ($patient) => [
            'id' => $patient->id, 'name' => $patient->full_name, 'birth_date' => $patient->birth_date?->format('Y-m-d'),
        ])]);
    }

    private function documents(ProviderRequest $record)
    {
        return Document::query()->where('metadata->source', 'import_expedient')->where('metadata->provider_request_id', $record->id);
    }

    private function documentData(ProviderRequest $record): array
    {
        return $this->documents($record)->get()->map(fn ($document) => ['id' => $document->id, 'name' => $document->name,
            'size' => $document->file_size, 'type' => $document->document_type, 'url' => route('provider.import.documents.view', $document)])->all();
    }

    public function draft(Request $request, ProviderRequest $expedient): JsonResponse
    {
        $provider = $this->provider($request);
        abort_unless($expedient->request_type === 'import' && $expedient->status === 'draft'
            && data_get($expedient->payload, 'source') === 'import_expedient'
            && (!$provider || $expedient->provider_id === $provider->id), 404);
        return response()->json(['id' => $expedient->id, 'folio' => $expedient->external_id,
            'fields' => data_get($expedient->payload, 'expedient', []), 'documents' => $this->documentData($expedient)]);
    }

    public function store(Request $request, PlatformAuditService $audit)
    {
        $provider = $this->provider($request);
        $isDraft = $request->input('intent') === 'draft';
        $required = $isDraft ? 'nullable' : 'required';
        $data = $request->validateWithBag('expedient', [
            'intent' => ['required', Rule::in(['draft', 'create'])], 'draft_id' => 'nullable|integer', 'submission_token' => 'required|uuid',
            'step' => 'required|integer|min:0|max:5',
            'provider_id' => [$required, 'integer', Rule::exists('providers', 'id')->where('provider_type', 'import')->where('status', 'active')],
            'first_name' => "$required|string|max:80", 'first_surname' => "$required|string|max:80", 'second_surname' => 'nullable|string|max:80',
            'birth_date' => "$required|date|before_or_equal:today", 'sex' => ['nullable', Rule::in(['Femenino', 'Masculino', 'Otro', 'No especificado'])],
            'curp' => ['nullable', 'string', 'regex:/^[A-Z][AEIOUX][A-Z]{2}[0-9]{6}[HM][A-Z]{5}[A-Z0-9][0-9]$/i'],
            'phone' => 'nullable|string|max:30', 'email' => 'nullable|email|max:254', 'address' => 'nullable|string|max:500',
            'contact_name' => 'nullable|string|max:180', 'contact_phone' => 'nullable|string|max:30',
            'institution_id' => ['nullable', 'integer', Rule::exists('institutions', 'id')->where('status', 'active')],
            'medical_unit_id' => ['nullable', 'integer', Rule::exists('medical_units', 'id')->where('status', 'active')],
            'new_hospital_name' => 'nullable|string|max:180', 'doctor_id' => ['nullable', 'integer', Rule::exists('doctors', 'id')->where('status', 'active')],
            'specialty' => 'nullable|string|max:180', 'service' => 'nullable|string|max:180',
            'expedient_type' => ['required', Rule::in(['Importación de medicamento', 'Importación de dispositivo médico', 'Otro'])],
            'medication_name' => 'nullable|string|max:180', 'priority' => ['required', Rule::in(['Estándar', 'Alta', 'Urgente'])], 'notes' => 'nullable|string|max:2000',
            'patient_id' => 'nullable|integer', 'confirm_new_patient' => 'nullable|boolean',
            'files' => 'nullable|array|max:20', 'files.*' => 'file|mimes:pdf,jpg,jpeg,png|max:10240',
            'document_types' => 'nullable|array|max:20', 'document_types.*' => ['required', Rule::in(['Receta médica', 'INE', 'Comprobante de domicilio', 'Cédula fiscal', 'CURP', 'Resumen clínico', 'Otro documento'])],
            'removed_documents' => 'nullable|array|max:20', 'removed_documents.*' => 'integer',
        ]);
        if ($provider) {
            abort_if(!empty($data['provider_id']) && (int) $data['provider_id'] !== $provider->id, 403);
            $data['provider_id'] = $provider->id;
        }
        if (!empty($data['curp'])) $data['curp'] = mb_strtoupper($data['curp']);
        $unit = !empty($data['medical_unit_id']) ? MedicalUnit::find($data['medical_unit_id']) : null;
        $doctor = !empty($data['doctor_id']) ? Doctor::find($data['doctor_id']) : null;
        if ($unit && !empty($data['institution_id']) && $unit->institution_id !== (int) $data['institution_id']) {
            throw ValidationException::withMessages(['medical_unit_id' => 'El hospital no pertenece a la institución seleccionada.']);
        }
        if ($doctor?->medical_unit_id && ($doctor->medical_unit_id !== $unit?->id || !empty($data['new_hospital_name']))) {
            throw ValidationException::withMessages(['doctor_id' => 'Selecciona un médico del hospital indicado.']);
        }
        if (!empty($data['new_hospital_name']) && !empty($data['medical_unit_id'])) {
            throw ValidationException::withMessages(['new_hospital_name' => 'Selecciona un hospital o agrega uno nuevo.']);
        }
        $patient = null;
        if (!$isDraft) {
            $matches = $this->candidates($request, $data);
            if (!empty($data['patient_id'])) {
                $patient = $matches->firstWhere('id', (int) $data['patient_id']);
                abort_unless($patient, 403);
            } elseif ($matches->isNotEmpty() && empty($data['confirm_new_patient'])) {
                throw ValidationException::withMessages(['patient_id' => 'Revisa las coincidencias del paciente antes de crear el expediente.']);
            }
            if (!empty($data['curp']) && Patient::withTrashed()->where('curp', $data['curp'])->when($patient, fn ($query) => $query->where('id', '!=', $patient->id))->exists()) {
                throw ValidationException::withMessages(['curp' => 'La CURP ya está registrada. Revisa la vinculación del paciente.']);
            }
            if ($patient && !empty($data['curp']) && $patient->curp && $patient->curp !== $data['curp']) {
                throw ValidationException::withMessages(['curp' => 'La CURP no coincide con el paciente seleccionado.']);
            }
        }
        $paths = [];
        try {
            $record = DB::transaction(function () use ($request, $data, $provider, $isDraft, $unit, $patient, $audit, &$paths) {
                $query = ProviderRequest::query()->where('request_type', 'import')->where('payload->source', 'import_expedient')
                    ->when($provider, fn ($query) => $query->where('provider_id', $provider->id));
                $record = !empty($data['draft_id']) ? (clone $query)->lockForUpdate()->findOrFail($data['draft_id'])
                    : (clone $query)->where('payload->expedient->submission_token', $data['submission_token'])->where('payload->created_by', $request->user()->id)->lockForUpdate()->first();
                if ($record && $record->status !== 'draft') {
                    abort_unless(data_get($record->payload, 'expedient.submission_token') === $data['submission_token'], 409);
                    return $record;
                }
                if ($record?->provider_id && (int) $record->provider_id !== (int) ($data['provider_id'] ?? 0)) {
                    throw ValidationException::withMessages(['provider_id' => 'Conserva el proveedor del borrador.']);
                }
                $removed = $data['removed_documents'] ?? [];
                $documentCount = $record ? $this->documents($record)->whereNotIn('id', $removed)->count() : 0;
                if ($documentCount + count($request->file('files', [])) > 20) throw ValidationException::withMessages(['files' => 'Puedes adjuntar hasta 20 documentos.']);
                $fields = Arr::except($data, ['intent', 'draft_id', 'files', 'document_types', 'removed_documents']);
                $fullName = trim(implode(' ', array_filter([$data['first_name'] ?? '', $data['first_surname'] ?? '', $data['second_surname'] ?? ''])));
                if (!$isDraft && !$patient) {
                    $patient = Patient::create(['first_name' => $data['first_name'], 'last_name' => trim(($data['first_surname'] ?? '').' '.($data['second_surname'] ?? '')),
                        'full_name' => $fullName, 'birth_date' => $data['birth_date'], 'sex' => $data['sex'] ?? null, 'curp' => $data['curp'] ?? null,
                        'phone' => $data['phone'] ?? null, 'email' => $data['email'] ?? null, 'address' => $data['address'] ?? null,
                        'status' => 'active', 'metadata' => ['source' => 'import_expedient', 'created_by' => $request->user()->id]]);
                }
                if (!$isDraft && !empty($data['new_hospital_name'])) {
                    $unit = MedicalUnit::create(['institution_id' => $data['institution_id'] ?? null, 'name' => $data['new_hospital_name'],
                        'status' => 'active', 'metadata' => ['source' => 'import_expedient', 'created_by' => $request->user()->id]]);
                }
                $record ??= new ProviderRequest;
                $record->fill(['provider_id' => $data['provider_id'] ?? null, 'request_type' => 'import', 'status' => $isDraft ? 'draft' : 'documentation',
                    'patient_id' => $isDraft ? null : $patient->id, 'medical_unit_id' => $unit?->id, 'requested_at' => $record->requested_at ?? now(),
                    'payload' => ['source' => 'import_expedient', 'created_by' => data_get($record->payload, 'created_by', $request->user()->id), 'expedient' => $fields,
                        'import_details' => ['patient_name' => $fullName ?: 'Paciente pendiente', 'hospital' => $unit?->name ?? ($data['new_hospital_name'] ?? null)],
                        'prescription_items' => empty($data['medication_name']) ? [] : [['medication_name' => $data['medication_name']]],
                    ],
                ])->save();
                if (!$record->external_id) $record->update(['external_id' => 'IMP-'.now()->year.'-'.str_pad((string) $record->id, 6, '0', STR_PAD_LEFT)]);
                if ($removed) $this->documents($record)->whereIn('id', $removed)->delete();
                foreach ($request->file('files', []) as $index => $file) {
                    $path = $file->store('import-expedients/'.$record->id, 'local');
                    $paths[] = $path;
                    $type = $data['document_types'][$index] ?? 'Otro documento';
                    Document::create(['patient_id' => $record->patient_id, 'name' => $file->getClientOriginalName(), 'document_type' => $type,
                        'file_path' => $path, 'file_mime' => $file->getMimeType(), 'file_size' => $file->getSize(),
                        'uploaded_by' => $request->user()->id, 'loaded_at' => now(),
                        'metadata' => ['source' => 'import_expedient', 'category' => $type, 'provider_request_id' => $record->id, 'folio' => $record->external_id]]);
                }
                if (!$isDraft) $this->documents($record)->update(['patient_id' => $record->patient_id]);
                $record->statusEvents()->create(['status' => $record->status, 'actor' => $request->user()->name,
                    'notes' => $isDraft ? 'Borrador guardado.' : 'Expediente creado.', 'occurred_at' => now()]);
                $audit->record($request, $isDraft ? 'provider.expedient.draft_saved' : 'provider.expedient.created', $record, 'provider_import');
                return $record;
            });
        } catch (\Throwable $exception) {
            foreach ($paths as $path) Storage::disk('local')->delete($path);
            throw $exception;
        }
        $url = route('provider.import.dashboard', ['section' => 1, 'action' => 0]);
        $message = $record->status === 'draft' ? 'Borrador guardado. Puedes continuarlo desde el listado.' : 'Expediente '.$record->external_id.' creado correctamente.';
        if ($request->expectsJson()) {
            if ($record->status !== 'draft') $request->session()->flash('status', $message);
            return response()->json(['id' => $record->id, 'folio' => $record->external_id, 'status' => $record->status, 'message' => $message,
                'documents' => $this->documentData($record), 'redirect' => $url]);
        }
        return redirect($url)->with('status', $message);
    }
}
