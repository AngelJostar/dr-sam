<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\DeliveryRoute;
use App\Models\DeliveryReport;
use App\Models\MessengerProfile;
use App\Models\Provider;
use App\Models\ProviderRequest;
use App\Models\ProviderRequestStatusEvent;
use App\Services\Platform\PlatformAuditService;
use App\Services\Platform\DomainStateTransitionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProviderPortalController extends Controller
{
    public function storeImportPatientDetails(Request $request): RedirectResponse
    {
        $data = $request->validate(['folio' => ['required', 'string'], 'details' => ['required', 'array'], 'details.*' => ['nullable', 'string', 'max:500']]);
        $provider = $this->resolveProvider($request, 'import');
        $record = ProviderRequest::query()->where('external_id', $data['folio'])->where('request_type', 'import')->when($provider, fn ($query) => $query->where('provider_id', $provider->id))->first();
        abort_unless($record || (in_array($data['folio'], ['IMP-2026-001', 'IMP-2026-002', 'IMP-2026-003', 'IMP-2026-004', 'IMP-2026-005'], true) && !ProviderRequest::query()->where('external_id', $data['folio'])->exists()), 404);
        $allowed = ['Nombre', 'CURP', 'Fecha de nacimiento', 'Correo electrónico', 'Teléfono', 'Dirección'];
        $details = array_intersect_key($data['details'], array_flip($allowed));
        // Keep administrative draft details separate from the clinical identity.
        $metadata = $request->user()->metadata ?? [];
        $metadata['import_patient_details'][$data['folio']] = $details;
        $request->user()->update(['metadata' => $metadata]);
        return redirect()->route('provider.import.dashboard', ['section' => 6, 'action' => 0])->with('status', 'Datos del expediente guardados.')->with('patient_details_saved', true);
    }

    public function storeImportPatientDocuments(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'folio' => ['required', 'string'],
            'files' => ['required', 'array', 'min:1'],
            'files.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);
        $names = ['ine' => 'INE', 'address' => 'Comprobante de domicilio', 'tax' => 'Cédula fiscal', 'curp' => 'CURP', 'prescription' => 'Receta médica', 'summary' => 'Resumen clínico'];
        abort_unless(count(array_diff(array_keys($data['files']), array_keys($names))) === 0, 422);
        $provider = $this->resolveProvider($request, 'import');
        $record = ProviderRequest::query()->where('external_id', $data['folio'])->where('request_type', 'import')
            ->when($provider, fn ($query) => $query->where('provider_id', $provider->id))->first();
        // Reference rows can accept private drafts without inventing a patient.
        abort_unless($record || (in_array($data['folio'], ['IMP-2026-001', 'IMP-2026-002', 'IMP-2026-003', 'IMP-2026-004', 'IMP-2026-005'], true)
            && !ProviderRequest::query()->where('external_id', $data['folio'])->exists()), 404);
        foreach ($data['files'] as $key => $file) {
            $path = $file->store('import-documents/'.$request->user()->id, 'local');
            \App\Models\Document::query()->create([
                'patient_id' => $record?->patient_id, 'name' => $file->getClientOriginalName(),
                'document_type' => $names[$key], 'file_path' => $path, 'file_mime' => $file->getMimeType(),
                'file_size' => $file->getSize(), 'uploaded_by' => $request->user()->id, 'loaded_at' => now(),
                'metadata' => ['category' => $names[$key], 'provider_request_id' => $record?->id, 'folio' => $data['folio'], 'source' => 'import_administration', 'pending_patient' => !$record?->patient_id],
            ]);
        }
        return redirect()->route('provider.import.dashboard', ['section' => 6, 'action' => 0])->with('status', 'Documentos del paciente guardados.');
    }

    public function viewImportDocument(Request $request, \App\Models\Document $document)
    {
        $provider = $this->resolveProvider($request, 'import');
        $ownsDraft = !$document->patient_id && $document->uploaded_by === $request->user()->id && data_get($document->metadata, 'source') === 'import_administration';
        abort_unless($ownsDraft || ($document->patient_id && ProviderRequest::query()->where('patient_id', $document->patient_id)
            ->where('request_type', 'import')->when($provider, fn ($query) => $query->where('provider_id', $provider->id))->exists()), 403);
        abort_unless($document->file_path && \Illuminate\Support\Facades\Storage::disk('local')->exists($document->file_path), 404);
        if ($request->boolean('download')) {
            return \Illuminate\Support\Facades\Storage::disk('local')->download($document->file_path, $document->name);
        }
        return response()->file(\Illuminate\Support\Facades\Storage::disk('local')->path($document->file_path));
    }

    private const TYPES = [
        'npt' => [
            'label' => 'Proveedor NPT',
            'module' => 'provider_npt',
            'request_types' => ['npt'],
            'provider_types' => ['npt'],
        ],
        'chemotherapy' => [
            'label' => 'Proveedor Quimioterapias',
            'module' => 'provider_chemo',
            'request_types' => ['chemotherapy', 'chemo'],
            'provider_types' => ['chemotherapy', 'chemo'],
        ],
        'import' => [
            'label' => 'Proveedor Importacion',
            'module' => 'provider_import',
            'request_types' => ['import'],
            'provider_types' => ['import'],
        ],
        'medicines' => [
            'label' => 'Distribuidor de medicamentos',
            'module' => 'provider_medicines',
            'request_types' => ['medicines', 'medication'],
            'provider_types' => ['medicines', 'medication', 'distributor'],
        ],
        'clinical-labs' => [
            'label' => 'Proveedor analisis clinicos',
            'module' => 'provider_clinical_labs',
            'request_types' => ['clinical_labs', 'clinical-labs', 'laboratory'],
            'provider_types' => ['clinical_labs', 'clinical-labs', 'laboratory'],
        ],
    ];

    public function index(Request $request, string $type): View
    {
        abort_unless(isset(self::TYPES[$type]), 404);

        $provider = $this->resolveProvider($request, $type);
        $typeConfig = self::TYPES[$type];
        $requestTypes = $typeConfig['request_types'];

        $requestsQuery = ProviderRequest::query()
            ->with([
                'patient.documents',
                'medicalUnit.institution',
                'provider.user',
                'mixtureIntegration',
                'statusEvents' => fn ($query) => $query->latest('occurred_at')->latest(),
                'deliveryRoutes.messenger.user',
            ])
            ->where(function ($query) use ($provider, $requestTypes): void {
                $query->whereIn('request_type', $requestTypes);

                if ($provider) {
                    $query->orWhere('provider_id', $provider->id);
                }
            })
            ->when($provider, fn ($query) => $query->where('provider_id', $provider->id))
            ->latest('requested_at')
            ->latest();

        $requests = $requestsQuery
            ->paginate(10)
            ->withQueryString();

        $deliveryRoutes = DeliveryRoute::query()
            ->with(['messenger.user', 'providerRequest.medicalUnit.institution'])
            ->whereHas('providerRequest', function ($query) use ($provider, $requestTypes): void {
                $query->whereIn('request_type', $requestTypes)
                    ->when($provider, fn ($providerQuery) => $providerQuery->where('provider_id', $provider->id));
            })
            ->latest('scheduled_at')
            ->latest()
            ->get();

        $messengers = MessengerProfile::query()
            ->with(['user', 'deliveryRoutes' => function ($query) use ($provider, $requestTypes): void {
                $query->with('providerRequest.medicalUnit.institution')
                    ->whereHas('providerRequest', function ($requestQuery) use ($provider, $requestTypes): void {
                        $requestQuery->whereIn('request_type', $requestTypes)
                            ->when($provider, fn ($providerQuery) => $providerQuery->where('provider_id', $provider->id));
                    })
                    ->latest('scheduled_at');
            }])
            ->whereHas('deliveryRoutes.providerRequest', function ($query) use ($provider, $requestTypes): void {
                $query->whereIn('request_type', $requestTypes)
                    ->when($provider, fn ($providerQuery) => $providerQuery->where('provider_id', $provider->id));
            })
            ->orderBy('id')
            ->get();

        return view('provider.dashboard', [
            'type' => $type,
            'typeConfig' => $typeConfig,
            'provider' => $provider,
            'requests' => $requests,
            'deliveryRoutes' => $deliveryRoutes,
            'messengers' => $messengers,
            'importRecords' => $type === 'import' ? (clone $requestsQuery)->get() : collect(),
            'importSuppliers' => $type === 'import' ? Provider::query()->where('provider_type', 'import')
                ->where('metadata->source', 'international_supplier')
                ->when($request->user()->role === 'provider', fn ($query) => $query->where('metadata->created_by', $request->user()->id))
                ->latest()->get() : collect(),
            'metrics' => [
                'Solicitudes' => (clone $requestsQuery)->count(),
                'Abiertas' => (clone $requestsQuery)->whereNotIn('status', ['delivered', 'cancelled', 'rejected'])->count(),
                'En ruta' => (clone $requestsQuery)->where('status', 'in_route')->count(),
                'Entregadas' => (clone $requestsQuery)->where('status', 'delivered')->count(),
            ],
        ]);
    }

    public function storeImportSupplier(Request $request): RedirectResponse
    {
        $data = $request->validateWithBag('supplier', [
            'legal_name' => ['required', 'string', 'max:180'],
            'trade_name' => ['nullable', 'string', 'max:180'],
            'country' => ['required', Rule::in(['Estados Unidos', 'Alemania', 'Japón', 'Suiza', 'Reino Unido', 'España', 'Corea del Sur', 'Países Bajos', 'México', 'Otro'])],
            'supplier_type' => ['required', Rule::in(['Fabricante', 'Distribuidor', 'Comercializador'])],
            'categories' => ['required', 'array', 'min:1'],
            'categories.*' => [Rule::in(['Biotecnológicos', 'Oncológicos', 'Inmunológicos', 'Terapias avanzadas', 'Equipos médicos', 'Genéricos', 'Dispositivos médicos', 'Vacunas'])],
            'contact_name' => ['required', 'string', 'max:180'],
            'email' => ['required', 'email', 'max:180'],
            'phone' => ['required', 'string', 'max:50'],
            'address' => ['required', 'string', 'max:500'],
            'currency' => ['required', Rule::in(['USD', 'EUR', 'MXN', 'JPY', 'CHF', 'GBP', 'KRW'])],
            'response_time' => ['required', Rule::in(['12 h', '24 h', '36 h', '48 h', '72 h'])],
            'payment_terms' => ['required', Rule::in(['Anticipado', 'Contado', 'Crédito 30 días', 'Crédito 60 días'])],
            'sanitary_document' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:10240'],
            'framework_contract' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:10240'],
            'initial_status' => ['required', Rule::in(['active', 'preferred', 'pending', 'document_risk'])],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);
        unset($data['sanitary_document'], $data['framework_contract']);
        foreach (['sanitary_document', 'framework_contract'] as $field) {
            $data['documents'][$field] = $request->file($field)->store('import-suppliers', 'local');
        }
        Provider::query()->create([
            'name' => $data['legal_name'], 'provider_type' => 'import',
            'status' => 'active',
            'metadata' => [...$data, 'source' => 'international_supplier', 'created_by' => $request->user()->id],
        ]);

        return redirect()->route('provider.import.dashboard', ['section' => 3, 'action' => 0])->with('status', 'Proveedor registrado correctamente.');
    }

    public function updateRequestStatus(Request $request, string $type, ProviderRequest $providerRequest, PlatformAuditService $audit, DomainStateTransitionService $transitions): RedirectResponse
    {
        abort_unless(isset(self::TYPES[$type]), 404);

        $provider = $this->resolveProvider($request, $type);

        if ($provider) {
            abort_unless((int) $providerRequest->provider_id === (int) $provider->id, 403);
        }

        $data = $request->validate([
            'status' => ['required', Rule::in(['requested', 'accepted', 'dispensed', 'preparing', 'ready', 'in_route', 'delivered', 'rejected', 'cancelled'])],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $transitions->assertAllowed('provider_request', $providerRequest->status, $data['status']);

        $providerRequest->update([
            'status' => $data['status'],
        ]);

        $event = ProviderRequestStatusEvent::query()->create([
            'provider_request_id' => $providerRequest->id,
            'status' => $data['status'],
            'actor' => $request->user()?->name,
            'notes' => $data['notes'] ?? null,
            'occurred_at' => now(),
            'metadata' => [
                'source' => 'provider_portal',
                'provider_type' => $type,
                'user_id' => $request->user()?->id,
            ],
        ]);

        $audit->record($request, 'provider.request.status_updated', $providerRequest, 'provider', [
            'provider_type' => $type,
            'status_event_id' => $event->id,
            'status' => $providerRequest->status,
        ]);

        if ($data['status'] === 'in_route') {
            $route = $this->createDeliveryRouteFor($providerRequest, $type, $request);
            $audit->record($request, 'provider.delivery_route.assigned', $route, 'provider', [
                'provider_request_id' => $providerRequest->id,
                'provider_type' => $type,
            ]);
        }

        return back()->with('status', 'Solicitud actualizada por proveedor.');
    }

    public function updateDeliveryRouteStatus(Request $request, string $type, DeliveryRoute $deliveryRoute, PlatformAuditService $audit, DomainStateTransitionService $transitions): RedirectResponse
    {
        abort_unless(isset(self::TYPES[$type]), 404);

        $provider = $this->resolveProvider($request, $type);
        $deliveryRoute->loadMissing('providerRequest.provider');
        $providerRequest = $deliveryRoute->providerRequest;

        abort_unless($providerRequest, 404);
        abort_unless(in_array($providerRequest->request_type, self::TYPES[$type]['request_types'], true), 403);
        if ($provider) {
            abort_unless((int) $providerRequest->provider_id === (int) $provider->id, 403);
        }

        $data = $request->validate([
            'status' => ['required', Rule::in(['picked_up', 'in_route', 'delivered', 'failed'])],
            'notes' => ['nullable', 'string', 'max:1000'],
            'received_by' => ['nullable', 'string', 'max:150'],
        ]);

        $transitions->assertAllowed('delivery_route', $deliveryRoute->status, $data['status']);
        $deliveryRoute->update(['status' => $data['status']]);

        $report = DeliveryReport::query()->create([
            'delivery_route_id' => $deliveryRoute->id,
            'status' => $data['status'],
            'notes' => $data['notes'] ?? null,
            'payload' => [
                'source' => 'provider_portal',
                'provider_type' => $type,
                'reported_by' => $request->user()?->id,
                'received_by' => $data['received_by'] ?? null,
            ],
            'reported_at' => now(),
        ]);

        $requestStatus = $data['status'] === 'delivered' ? 'delivered' : 'in_route';
        if ($providerRequest->status !== $requestStatus) {
            $transitions->assertAllowed('provider_request', $providerRequest->status, $requestStatus);
        }

        $payload = $providerRequest->payload ?? [];
        if ($data['status'] === 'delivered') {
            $payload['remission'] = [
                'route_code' => $deliveryRoute->route_code,
                'delivered_at' => now()->toIso8601String(),
                'received_by' => $data['received_by'] ?? null,
                'notes' => $data['notes'] ?? null,
                'report_id' => $report->id,
            ];
        }

        $providerRequest->update([
            'status' => $requestStatus,
            'payload' => $payload,
        ]);

        ProviderRequestStatusEvent::query()->create([
            'provider_request_id' => $providerRequest->id,
            'status' => $requestStatus,
            'actor' => $request->user()?->name,
            'notes' => $data['notes'] ?? null,
            'occurred_at' => now(),
            'metadata' => ['source' => 'provider_route', 'delivery_route_id' => $deliveryRoute->id],
        ]);

        $audit->record($request, 'provider.delivery_route.status_updated', $deliveryRoute, 'provider', [
            'provider_request_id' => $providerRequest->id,
            'delivery_report_id' => $report->id,
            'status' => $data['status'],
        ]);

        return back()->with('status', $data['status'] === 'delivered' ? 'Entrega y remision registradas.' : 'Ruta actualizada.');
    }

    private function resolveProvider(Request $request, string $type): ?Provider
    {
        $user = $request->user();

        $typeConfig = self::TYPES[$type];

        return Provider::query()
            ->where('user_id', $user?->id)
            ->whereIn('provider_type', $typeConfig['provider_types'])
            ->first();
    }

    private function createDeliveryRouteFor(ProviderRequest $providerRequest, string $type, Request $request): DeliveryRoute
    {
        $messenger = MessengerProfile::query()
            ->where('status', 'active')
            ->orderBy('id')
            ->first();

        return DeliveryRoute::query()->updateOrCreate(
            ['provider_request_id' => $providerRequest->id],
            [
                'messenger_profile_id' => $messenger?->id,
                'route_code' => 'RUTA-PROV-'.$providerRequest->id,
                'origin' => $providerRequest->provider?->name ?? self::TYPES[$type]['label'],
                'destination' => $providerRequest->medicalUnit?->name ?? $providerRequest->patient?->full_name ?? 'Destino pendiente',
                'status' => 'assigned',
                'scheduled_at' => now(),
                'metadata' => [
                    'source' => 'provider_portal',
                    'provider_type' => $type,
                    'created_by' => $request->user()?->id,
                ],
            ],
        );
    }
}
