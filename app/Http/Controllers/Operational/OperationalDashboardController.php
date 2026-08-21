<?php

namespace App\Http\Controllers\Operational;

use App\Http\Controllers\Controller;
use App\Models\ContractedService;
use App\Models\InventoryItem;
use App\Models\MedicalUnit;
use App\Models\OperationalProfile;
use App\Models\Patient;
use App\Models\PatientOrder;
use App\Models\Prescription;
use App\Models\ProcedureArea;
use App\Models\Provider;
use App\Models\ProviderRequest;
use App\Models\ProviderRequestStatusEvent;
use App\Services\Platform\PlatformAuditService;
use App\Services\Platform\DomainStateTransitionService;
use App\Services\Integrations\Cbta\MixtureIntegrationSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OperationalDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $section = $request->string('section')->toString() ?: 'pending';
        $allowedSections = [
            'pending', 'history', 'patients', 'patient-create',
            'services-pending', 'services-history', 'service-create', 'service-format',
            'mixes', 'mix-history', 'calendar', 'infusion-rooms',
        ];

        if (! in_array($section, $allowedSections, true)) {
            $section = 'pending';
        }

        $areaKey = $request->string('area')->toString() ?: 'nursing';
        $allowedAreas = ['nursing', 'oncology', 'inpatient-pharmacy'];

        if (! in_array($areaKey, $allowedAreas, true)) {
            $areaKey = 'nursing';
        }

        $profile = OperationalProfile::query()
            ->with(['user', 'medicalUnit.institution', 'area'])
            ->where('user_id', $request->user()?->id)
            ->first();

        $contextUnit = $profile?->medicalUnit;
        if ($request->filled('unit')) {
            $requestedUnit = MedicalUnit::query()->with('institution')->findOrFail($request->integer('unit'));
            $user = $request->user();
            $canUseUnit = in_array($user?->role, ['superadmin', 'admin'], true)
                || ($user?->role === 'unit' && $requestedUnit->unit_username === $user->username)
                || ($user?->role === 'institution' && (int) $requestedUnit->institution_id === (int) $user->institution?->id)
                || ($user?->role === 'operational' && (int) $profile?->medical_unit_id === (int) $requestedUnit->id);
            abort_unless($canUseUnit, 403, 'No puedes operar otra unidad.');
            $contextUnit = $requestedUnit;
        }

        $unitId = $contextUnit?->id;
        $areaRequestTypes = [
            'nursing' => ['npt', 'nutrition', 'import'],
            'oncology' => ['chemo'],
            'inpatient-pharmacy' => ['npt', 'chemo', 'import'],
        ];

        $providerRequests = ProviderRequest::query()
            ->with(['provider', 'patient', 'medicalUnit', 'mixtureIntegration', 'statusEvents' => fn ($query) => $query->latest('occurred_at')->latest()])
            ->when($unitId, fn ($query) => $query->where('medical_unit_id', $unitId))
            ->when($areaRequestTypes[$areaKey] ?? null, fn ($query, $types) => $query->whereIn('request_type', $types))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = '%'.$request->string('search')->toString().'%';
                $query->where(function ($subQuery) use ($search): void {
                    $subQuery->where('external_id', 'like', $search)
                        ->orWhere('request_type', 'like', $search)
                        ->orWhereHas('patient', fn ($patientQuery) => $patientQuery->where('full_name', 'like', $search));
                });
            })
            ->latest('requested_at')
            ->limit(12)
            ->get();

        $pendingProviderRequests = $providerRequests
            ->whereNotIn('status', ['delivered', 'cancelled', 'rejected'])
            ->values();

        $historicalProviderRequests = $providerRequests
            ->whereIn('status', ['delivered', 'cancelled', 'rejected', 'requested', 'accepted', 'preparing', 'in_route'])
            ->values();

        $providerPatientIds = ProviderRequest::query()
            ->when($unitId, fn ($query) => $query->where('medical_unit_id', $unitId))
            ->when($areaRequestTypes[$areaKey] ?? null, fn ($query, $types) => $query->whereIn('request_type', $types))
            ->whereNotNull('patient_id')
            ->pluck('patient_id');

        $outpatientPrescriptions = Prescription::query()
            ->with(['patient', 'doctor', 'items'])
            ->where('status', 'active')
            ->where('metadata->source', 'outpatient_module')
            ->when($unitId, function ($query) use ($unitId): void {
                $query->where(function ($subQuery) use ($unitId): void {
                    $subQuery->where('metadata->medical_unit_id', $unitId)
                        ->orWhereHas('doctor', fn ($doctorQuery) => $doctorQuery->where('medical_unit_id', $unitId));
                });
            })
            ->latest('issued_at')
            ->limit(20)
            ->get();

        $outpatientPrescriptionPatientIds = $outpatientPrescriptions->pluck('patient_id')->filter();

        $patients = Patient::query()
            ->where(function ($query) use ($unitId, $providerPatientIds, $outpatientPrescriptionPatientIds): void {
                $query
                    ->when($unitId, fn ($subQuery) => $subQuery->whereHas('appointments', fn ($appointmentQuery) => $appointmentQuery->where('medical_unit_id', $unitId)))
                    ->orWhereIn('id', $providerPatientIds)
                    ->orWhereIn('id', $outpatientPrescriptionPatientIds);
            })
            ->orderBy('full_name')
            ->limit(30)
            ->get();

        $inventory = InventoryItem::query()
            ->with(['product', 'medicalUnit'])
            ->when($unitId, fn ($query) => $query->where('medical_unit_id', $unitId))
            ->latest()
            ->limit(12)
            ->get();

        $services = ContractedService::query()
            ->with(['service', 'institution', 'medicalUnit'])
            ->when($unitId, fn ($query) => $query->where('medical_unit_id', $unitId))
            ->where('status', 'active')
            ->orderBy('ends_at')
            ->limit(12)
            ->get();

        $orders = PatientOrder::query()
            ->with(['patient', 'items.product'])
            ->latest('ordered_at')
            ->limit(8)
            ->get();

        $infusionRooms = $contextUnit?->procedureAreas()
            ->with(['schedules' => fn ($query) => $query->where('active', true)->orderBy('day_of_week')->orderBy('starts_at')])
            ->where('type', 'infusion')
            ->orderBy('unit_number')
            ->get() ?? collect();

        return view('operational.dashboard', [
            'section' => $section,
            'areaKey' => $areaKey,
            'profile' => $profile,
            'contextUnit' => $contextUnit,
            'providerRequests' => $providerRequests,
            'pendingProviderRequests' => $pendingProviderRequests,
            'historicalProviderRequests' => $historicalProviderRequests,
            'patients' => $patients,
            'inventory' => $inventory,
            'services' => $services,
            'orders' => $orders,
            'outpatientPrescriptions' => $outpatientPrescriptions,
            'infusionRooms' => $infusionRooms,
            'metrics' => [
                'Solicitudes proveedor' => ProviderRequest::query()
                    ->when($unitId, fn ($query) => $query->where('medical_unit_id', $unitId))
                    ->count(),
                'Solicitudes abiertas' => ProviderRequest::query()
                    ->when($unitId, fn ($query) => $query->where('medical_unit_id', $unitId))
                    ->whereNotIn('status', ['delivered', 'cancelled', 'rejected'])
                    ->count(),
                'Inventario disponible' => InventoryItem::query()
                    ->when($unitId, fn ($query) => $query->where('medical_unit_id', $unitId))
                    ->where('status', 'available')
                    ->sum('quantity'),
                'Servicios activos' => ContractedService::query()
                    ->when($unitId, fn ($query) => $query->where('medical_unit_id', $unitId))
                    ->where('status', 'active')
                    ->count(),
            ],
        ]);
    }

    public function storePatient(Request $request, PlatformAuditService $audit): RedirectResponse
    {
        $data = $this->validatePatientData($request);
        $patient = Patient::query()->create($this->patientDataPayload($data));

        $audit->record($request, 'operational.patient.created', $patient, 'operational');

        return redirect()->route('operational.dashboard', [
            'area' => $request->string('area')->toString() ?: 'nursing',
            'section' => 'patients',
        ])->with('status', 'Paciente registrado correctamente.');
    }

    public function updatePatient(Request $request, Patient $patient, PlatformAuditService $audit): RedirectResponse
    {
        $data = $this->validatePatientData($request, $patient);
        $patient->update($this->patientDataPayload($data, $patient));
        $audit->record($request, 'operational.patient.updated', $patient, 'operational');

        return redirect()->route('operational.dashboard', [
            'area' => $request->string('area')->toString() ?: 'nursing',
            'section' => 'patients',
        ])->with('status', 'Paciente actualizado correctamente.');
    }

    public function storeServiceRequest(Request $request, PlatformAuditService $audit): RedirectResponse
    {
        $data = $request->validate([
            'patient_id' => ['required', 'exists:patients,id'],
            'prescription_id' => ['nullable', 'exists:prescriptions,id'],
            'service' => ['required', 'string', 'max:160'],
            'doctor' => ['required', 'string', 'max:160'],
            'volume' => ['nullable', 'numeric', 'min:0'],
            'diagnosis' => ['nullable', 'string', 'max:500'],
            'required_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $unit = $this->contextUnitFor($request);
        $prescription = null;

        if (! empty($data['prescription_id'])) {
            $prescription = Prescription::query()->with(['patient', 'doctor', 'items'])->findOrFail($data['prescription_id']);
            $prescriptionUnitId = (int) (data_get($prescription->metadata, 'medical_unit_id') ?: $prescription->doctor?->medical_unit_id);
            abort_unless(! $unit || $prescriptionUnitId === (int) $unit->id, 404);
            abort_unless((int) $prescription->patient_id === (int) $data['patient_id'], 422, 'La receta no corresponde al paciente seleccionado.');
        }

        $provider = Provider::query()->where('provider_type', 'chemo')->first()
            ?? Provider::query()->where('status', 'active')->first();

        $payload = [
            'service' => $data['service'],
            'doctor' => $data['doctor'],
            'volume' => $data['volume'] ?? null,
            'diagnosis' => $data['diagnosis'] ?? data_get($prescription?->metadata, 'diagnosis'),
            'notes' => $data['notes'] ?? null,
            'mix_status' => 'Protocolo por validar',
            'authorizations' => [
                'oncology' => 'pending',
                'pharmacy' => 'pending',
            ],
            'authorization_requirements' => ['oncology', 'pharmacy'],
        ];

        if ($prescription) {
            $payload['outpatient_source'] = true;
            $payload['prescription_id'] = $prescription->id;
            $payload['prescription_code'] = $prescription->code;
            $payload['prescription_issued_at'] = $prescription->issued_at?->toDateString();
            $payload['prescription_items'] = $prescription->items->map(fn ($item) => [
                'medication_name' => $item->medication_name,
                'dose' => $item->dose,
                'frequency' => $item->frequency,
                'duration' => $item->duration,
                'instructions' => $item->instructions,
                'cnis' => data_get($item->metadata, 'cnis'),
                'presentation' => data_get($item->metadata, 'presentation'),
                'route' => data_get($item->metadata, 'route'),
                'quantity' => data_get($item->metadata, 'quantity'),
            ])->values()->all();
        }

        $providerRequest = DB::transaction(function () use ($provider, $data, $unit, $payload, $prescription): ProviderRequest {
            $providerRequest = ProviderRequest::query()->create([
                'provider_id' => $provider?->id,
                'patient_id' => $data['patient_id'],
                'medical_unit_id' => $unit?->id,
                'external_id' => 'QT-'.str_pad((string) (ProviderRequest::query()->max('id') + 1), 4, '0', STR_PAD_LEFT),
                'request_type' => 'chemo',
                'status' => 'requested',
                'requested_at' => now(),
                'required_at' => $data['required_at'] ?? null,
                'payload' => $payload,
            ]);

            if ($prescription) {
                $metadata = $prescription->metadata ?? [];
                $metadata['operational_status'] = 'requested';
                $metadata['operational_provider_request_id'] = $providerRequest->id;
                $metadata['operational_requested_at'] = now()->toDateTimeString();
                $prescription->update(['metadata' => $metadata]);
            }

            return $providerRequest;
        });

        $audit->record($request, 'operational.service_request.created', $providerRequest, 'operational');

        return redirect()->route('operational.dashboard', ['area' => 'oncology', 'section' => 'services-pending'])
            ->with('status', 'Servicio oncolÃƒÂ³gico solicitado correctamente.');
    }

    public function updateProviderRequestStatus(Request $request, ProviderRequest $providerRequest, PlatformAuditService $audit, DomainStateTransitionService $transitions): RedirectResponse
    {
        $this->authorizeProviderRequestOwnership($request, $providerRequest);

        $data = $request->validate([
            'status' => ['required', Rule::in(['draft', 'requested', 'accepted', 'preparing', 'in_route', 'delivered', 'rejected', 'cancelled'])],
            'notes' => ['nullable', 'string', 'max:500'],
            'provider_name' => ['nullable', 'string', 'max:160'],
            'operating_area' => ['nullable', Rule::in(['nursing', 'oncology', 'inpatient-pharmacy'])],
        ]);

        if ($data['status'] === 'cancelled') {
            $payload = $providerRequest->payload ?? [];
            $required = $payload['authorization_requirements']
                ?? ($providerRequest->request_type === 'chemo' ? ['oncology', 'pharmacy'] : ['operational', 'pharmacy']);
            $authorizations = $payload['authorizations'] ?? [];
            $allAuthorizationsApproved = collect($required)
                ->every(fn ($key) => ($authorizations[$key] ?? 'pending') === 'approved');

            abort_unless(
                ($data['operating_area'] ?? null) === 'inpatient-pharmacy' && $allAuthorizationsApproved,
                403,
                'Solo Farmacia intrahospitalaria puede cancelar una solicitud con todas las autorizaciones aprobadas.',
            );
        }

        if ($data['status'] === 'accepted') {
            $payload = $providerRequest->payload ?? [];
            $required = $payload['authorization_requirements'] ?? [];
            $authorizations = $payload['authorizations'] ?? [];

            if ($required !== []) {
                abort_unless(
                    collect($required)->every(fn ($key) => ($authorizations[$key] ?? 'pending') === 'approved'),
                    422,
                    'La solicitud requiere todas las autorizaciones antes de enviarse al proveedor.',
                );
            }
        }

        $transitions->assertAllowed('provider_request', $providerRequest->status, $data['status']);

        $update = ['status' => $data['status']];
        if ($data['status'] === 'accepted' && filled($data['provider_name'] ?? null)) {
            $payload = $providerRequest->payload ?? [];
            $payload['provider_assignment'] = [
                'name' => $data['provider_name'],
                'selected_by' => $request->user()?->name,
                'selected_at' => now()->toISOString(),
            ];
            $payload['provider_dispatch_status'] = 'sent-to-provider';
            $payload['sent_to_provider_at'] = now()->toISOString();
            $update['payload'] = $payload;
        }

        $providerRequest->update($update);

        $event = ProviderRequestStatusEvent::query()->create([
            'provider_request_id' => $providerRequest->id,
            'status' => $data['status'],
            'actor' => $request->user()?->name,
            'notes' => $data['notes'] ?? null,
            'occurred_at' => now(),
            'metadata' => [
                'source' => 'operational_dashboard',
                'user_id' => $request->user()?->id,
            ],
        ]);

        if ($prescriptionId = data_get($providerRequest->payload, 'prescription_id')) {
            $prescription = Prescription::query()->find($prescriptionId);
            if ($prescription) {
                $metadata = $prescription->metadata ?? [];
                $metadata['operational_status'] = $providerRequest->status;
                $metadata['operational_provider_request_id'] = $providerRequest->id;
                $metadata['operational_provider_request_status'] = $providerRequest->status;
                $metadata['operational_provider_request_status_at'] = now()->toDateTimeString();
                $metadata['operational_provider_request_status_event_id'] = $event->id;
                if ($providerRequest->status === 'delivered') {
                    $metadata['operational_delivered_at'] = now()->toDateTimeString();
                } elseif (in_array($providerRequest->status, ['rejected', 'cancelled'], true)) {
                    $metadata['operational_closed_at'] = now()->toDateTimeString();
                }

                $prescription->update(['metadata' => $metadata]);
            }
        }

        $audit->record($request, 'operational.provider_request.status_updated', $providerRequest, 'operational', [
            'status_event_id' => $event->id,
            'status' => $providerRequest->status,
        ]);

        return back()->with('status', 'Solicitud de proveedor actualizada.');
    }

    public function updateProviderRequestAuthorization(Request $request, ProviderRequest $providerRequest, PlatformAuditService $audit, MixtureIntegrationSyncService $mixtureSync): RedirectResponse
    {
        $this->authorizeProviderRequestOwnership($request, $providerRequest);

        $data = $request->validate([
            'authorization' => ['required', Rule::in(['operational', 'pharmacy', 'oncology'])],
            'status' => ['required', Rule::in(['pending', 'approved', 'rejected'])],
            'notes' => ['nullable', 'string', 'max:500'],
            'operating_area' => ['nullable', Rule::in(['nursing', 'oncology', 'inpatient-pharmacy'])],
        ]);

        $contextOperatorRoles = ['superadmin', 'admin'];
        $operatingAreaAuthorization = [
            'nursing' => 'operational',
            'oncology' => 'oncology',
            'inpatient-pharmacy' => 'pharmacy',
        ][$data['operating_area'] ?? ''] ?? null;

        if (! in_array($request->user()?->role, $contextOperatorRoles, true)) {
            abort_unless(
                $operatingAreaAuthorization === $data['authorization'],
                403,
                'Solo puedes modificar la autorización del área activa en Operar como.',
            );
        }

        abort_if(
            in_array($providerRequest->status, ['delivered', 'cancelled', 'rejected'], true),
            422,
            'Las autorizaciones de una solicitud cerrada ya no pueden modificarse.',
        );

        DB::transaction(function () use ($data, $providerRequest, $request): void {
            $payload = $providerRequest->payload ?? [];
            $authorizations = $payload['authorizations'] ?? [];
            $previousStatus = $authorizations[$data['authorization']] ?? 'pending';
            $authorizations[$data['authorization']] = $data['status'];
            $payload['authorizations'] = $authorizations;
            $authorizationEvent = [
                'status' => $data['status'],
                'actor' => $request->user()?->name,
                'notes' => $data['notes'] ?? null,
                'occurred_at' => now()->toISOString(),
            ];
            $history = $payload['authorization_history'][$data['authorization']] ?? [];
            if (isset($history['status'])) {
                $history = [$history];
            }
            $history[] = $authorizationEvent;
            $payload['authorization_history'][$data['authorization']] = $history;

            $required = $payload['authorization_requirements']
                ?? ($providerRequest->request_type === 'chemo' ? ['oncology', 'pharmacy'] : ['operational', 'pharmacy']);
            $payload['authorization_requirements'] = $required;

            if ($data['status'] === 'rejected') {
                $areaLabels = [
                    'operational' => 'Enfermería',
                    'pharmacy' => 'Farmacia intrahospitalaria',
                    'oncology' => 'Centro Oncológico',
                ];
                $areaLabel = $areaLabels[$data['authorization']] ?? 'Área operativa';
                $reason = trim((string) ($data['notes'] ?? ''));
                $message = 'Solicitud cancelada por '.$areaLabel.'.'.($reason !== '' ? ' '.$reason : '');

                $providerRequest->status = 'cancelled';
                $payload['cancellation'] = [
                    'area' => $data['authorization'],
                    'area_label' => $areaLabel,
                    'actor' => $request->user()?->name,
                    'reason' => $reason,
                    'message' => $message,
                    'occurred_at' => now()->toISOString(),
                ];
            }

            $providerRequest->payload = $payload;
            $providerRequest->save();

            ProviderRequestStatusEvent::query()->create([
                'provider_request_id' => $providerRequest->id,
                'status' => 'authorization_'.$data['status'],
                'actor' => $request->user()?->name,
                'notes' => $data['notes'] ?? null,
                'occurred_at' => now(),
                'metadata' => [
                    'type' => 'authorization',
                    'authorization' => $data['authorization'],
                    'previous_status' => $previousStatus,
                    'request_status' => $providerRequest->status,
                ],
            ]);
        });

        $integration = $providerRequest->fresh()->mixtureIntegration;
        $statusMessage = 'Autorización actualizada.';
        if ($integration && ! $integration->cbta_request_id) {
            if ($data['status'] === 'rejected') {
                $integration->update([
                    'sync_status' => 'cancelled',
                    'last_error' => 'La solicitud fue rechazada antes de enviarse a Mezclas.',
                ]);
            } elseif ($mixtureSync->hasRequiredAuthorizations($integration)) {
                $integration->update(['sync_status' => 'prevalidated', 'last_error' => null]);
                $statusMessage = $mixtureSync->sync($integration->fresh())
                    ? 'Autorización actualizada. Solicitud creada en Mezclas.'
                    : 'Autorización actualizada. La prevalidación final o el envío a Mezclas falló y se reintentará.';
            } else {
                $integration->update(['sync_status' => 'awaiting_authorizations', 'last_error' => null]);
            }
        }

        $audit->record($request, 'operational.provider_request.authorization_updated', $providerRequest, 'operational', [
            'authorization' => $data['authorization'],
            'status' => $data['status'],
        ]);

        return back()->with('status', $statusMessage);
    }

    public function storeInfusionRoom(Request $request, PlatformAuditService $audit): RedirectResponse
    {
        $unit = $this->contextUnitFor($request);
        abort_unless($unit, 404);
        $data = $this->validateInfusionRoom($request, $unit);

        $room = DB::transaction(function () use ($unit, $data): ProcedureArea {
            $room = $unit->procedureAreas()->create($this->infusionRoomPayload($data));
            $this->persistInfusionRoomSchedule($room, $data['schedule'] ?? []);

            return $room;
        });

        $audit->record($request, 'operational.infusion_room.created', $room, 'operational');

        return redirect()->route('operational.dashboard', [
            'area' => 'oncology',
            'section' => 'infusion-rooms',
            'unit' => $unit->id,
        ])->with('status', 'Sala de infusiÃ³n registrada correctamente.');
    }

    public function updateInfusionRoom(Request $request, ProcedureArea $room, PlatformAuditService $audit): RedirectResponse
    {
        $unit = $this->contextUnitFor($request);
        abort_unless($unit && $room->medical_unit_id === $unit->id && $room->type === 'infusion', 404);
        $data = $this->validateInfusionRoom($request, $unit, $room);

        DB::transaction(function () use ($room, $data): void {
            $room->update($this->infusionRoomPayload($data, $room));
            $this->persistInfusionRoomSchedule($room, $data['schedule'] ?? []);
        });

        $audit->record($request, 'operational.infusion_room.updated', $room, 'operational');

        return redirect()->route('operational.dashboard', [
            'area' => 'oncology',
            'section' => 'infusion-rooms',
            'unit' => $unit->id,
        ])->with('status', 'Sala de infusiÃ³n actualizada correctamente.');
    }

    public function assignInfusionRoom(Request $request, ProviderRequest $providerRequest, PlatformAuditService $audit): RedirectResponse
    {
        $this->authorizeProviderRequestOwnership($request, $providerRequest);
        abort_unless($providerRequest->request_type === 'chemo', 404);

        $data = $request->validate([
            'procedure_area_id' => ['required', 'integer', 'exists:procedure_areas,id'],
            'application_date' => ['required', 'date'],
            'starts_at' => ['required', 'date_format:H:i'],
            'ends_at' => ['required', 'date_format:H:i', 'after:starts_at'],
        ]);

        $room = ProcedureArea::query()
            ->with('schedules')
            ->whereKey($data['procedure_area_id'])
            ->where('medical_unit_id', $providerRequest->medical_unit_id)
            ->where('type', 'infusion')
            ->where('status', 'active')
            ->firstOrFail();

        $date = Carbon::parse($data['application_date']);
        $schedule = $room->schedules->first(fn ($item) =>
            $item->active
            && (int) $item->day_of_week === $date->dayOfWeek
            && substr((string) $item->starts_at, 0, 5) <= $data['starts_at']
            && substr((string) $item->ends_at, 0, 5) >= $data['ends_at']
        );

        if (! $schedule) {
            return back()->withErrors(['infusion_room' => 'La sala no estÃ¡ disponible dentro de ese horario.']);
        }

        $overlapping = ProviderRequest::query()
            ->where('medical_unit_id', $providerRequest->medical_unit_id)
            ->where('request_type', 'chemo')
            ->where('id', '!=', $providerRequest->id)
            ->get()
            ->filter(function (ProviderRequest $item) use ($room, $data): bool {
                $assignment = data_get($item->payload, 'infusion_assignment');

                return (int) data_get($assignment, 'procedure_area_id') === $room->id
                    && data_get($assignment, 'application_date') === $data['application_date']
                    && data_get($assignment, 'starts_at') < $data['ends_at']
                    && data_get($assignment, 'ends_at') > $data['starts_at'];
            })
            ->count();

        if ($overlapping >= $room->simultaneous_capacity) {
            return back()->withErrors(['infusion_room' => 'La sala alcanzÃ³ su capacidad simultÃ¡nea para ese horario.']);
        }

        $payload = $providerRequest->payload ?? [];
        $payload['infusion_assignment'] = [
            'procedure_area_id' => $room->id,
            'room_number' => $room->unit_number,
            'room_location' => $room->location,
            'application_date' => $data['application_date'],
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'],
            'scheduled_by' => $request->user()?->name,
            'scheduled_at' => now()->toDateTimeString(),
        ];
        $payload['infusion_room_status'] = 'scheduled';
        $providerRequest->update([
            'required_at' => Carbon::parse($data['application_date'].' '.$data['starts_at']),
            'payload' => $payload,
        ]);

        $audit->record($request, 'operational.infusion_room.assigned', $providerRequest, 'operational', $payload['infusion_assignment']);

        return redirect()->route('operational.dashboard', [
            'area' => 'oncology',
            'section' => 'calendar',
            'unit' => $providerRequest->medical_unit_id,
        ])->with('status', 'Sala de infusiÃ³n asignada correctamente.');
    }

    private function authorizeProviderRequestOwnership(Request $request, ProviderRequest $providerRequest): void
    {
        $user = $request->user();

        if (in_array($user?->role, ['superadmin', 'admin'], true)) {
            return;
        }

        if ($user?->role === 'institution') {
            abort_unless(
                $user->institution
                    && (int) $providerRequest->medicalUnit?->institution_id === (int) $user->institution->id,
                403,
                'No puedes modificar solicitudes de otra institución.',
            );

            return;
        }

        if ($user?->role === 'unit') {
            abort_unless(
                $providerRequest->medicalUnit
                    && $providerRequest->medicalUnit->unit_username === $user->username,
                403,
                'No puedes modificar solicitudes de otra unidad.',
            );

            return;
        }

        $profile = OperationalProfile::query()
            ->where('user_id', $user?->id)
            ->first();

        if (! $profile?->medical_unit_id) {
            return;
        }

        abort_unless(
            (int) $providerRequest->medical_unit_id === (int) $profile->medical_unit_id,
            403,
            'No puedes modificar solicitudes de otra unidad.',
        );
    }

    private function contextUnitFor(Request $request): ?MedicalUnit
    {
        if ($request->filled('unit')) {
            return MedicalUnit::query()->find($request->integer('unit'));
        }

        return OperationalProfile::query()
            ->where('user_id', $request->user()?->id)
            ->first()?->medicalUnit;
    }

    private function validateInfusionRoom(Request $request, MedicalUnit $unit, ?ProcedureArea $room = null): array
    {
        return $request->validate([
            'location' => ['required', 'string', 'max:180'],
            'floor' => ['nullable', 'string', 'max:40'],
            'unit_number' => [
                'required',
                'string',
                'max:80',
                Rule::unique('procedure_areas', 'unit_number')
                    ->where('medical_unit_id', $unit->id)
                    ->where('type', 'infusion')
                    ->ignore($room),
            ],
            'simultaneous_capacity' => ['required', 'integer', 'min:1', 'max:99'],
            'responsible_name' => ['nullable', 'string', 'max:160'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'schedule' => ['nullable', 'array'],
            'schedule.*.enabled' => ['nullable', 'boolean'],
            'schedule.*.start' => ['nullable', 'date_format:H:i'],
            'schedule.*.end' => ['nullable', 'date_format:H:i'],
        ]);
    }

    private function validatePatientData(Request $request, ?Patient $patient = null): array
    {
        return $request->validate([
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:160'],
            'birth_date' => ['nullable', 'date'],
            'sex' => ['nullable', Rule::in(['female', 'male', 'other'])],
            'curp' => ['nullable', 'string', 'size:18', Rule::unique('patients', 'curp')->ignore($patient)],
            'platform_number' => ['nullable', 'string', 'max:80', Rule::unique('patients', 'platform_number')->ignore($patient)],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:160'],
            'nss_federal' => ['nullable', 'string', 'max:40'],
            'nss_estatal' => ['nullable', 'string', 'max:40'],
            'state' => ['nullable', 'string', 'max:100'],
        ]);
    }

    private function patientDataPayload(array $data, ?Patient $patient = null): array
    {
        return [
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'full_name' => trim($data['first_name'].' '.$data['last_name']),
            'birth_date' => $data['birth_date'] ?? null,
            'sex' => $data['sex'] ?? null,
            'curp' => $data['curp'] ?? null,
            'platform_number' => $data['platform_number'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'status' => $patient?->status ?? 'active',
            'metadata' => [
                ...($patient?->metadata ?? []),
                'nss_federal' => $data['nss_federal'] ?? null,
                'nss_estatal' => $data['nss_estatal'] ?? null,
                'state' => $data['state'] ?? 'Mexico',
                'source' => 'operational_module',
            ],
        ];
    }

    private function infusionRoomPayload(array $data, ?ProcedureArea $room = null): array
    {
        return [
            'type' => 'infusion',
            'external_id' => $room?->external_id ?: $data['unit_number'],
            'location' => $data['location'],
            'floor' => $data['floor'] ?? null,
            'unit_number' => $data['unit_number'],
            'simultaneous_capacity' => $data['simultaneous_capacity'],
            'responsible_name' => $data['responsible_name'] ?? null,
            'status' => $data['status'],
            'metadata' => [...($room?->metadata ?? []), 'source' => 'operational_module'],
        ];
    }

    private function persistInfusionRoomSchedule(ProcedureArea $room, array $schedule): void
    {
        $days = ['monday' => 1, 'tuesday' => 2, 'wednesday' => 3, 'thursday' => 4, 'friday' => 5, 'saturday' => 6, 'sunday' => 0];
        $room->schedules()->delete();

        foreach ($schedule as $day => $range) {
            if (! ($range['enabled'] ?? false) || ! isset($days[$day], $range['start'], $range['end'])) {
                continue;
            }

            if ($range['end'] <= $range['start']) {
                continue;
            }

            $room->schedules()->create([
                'day_of_week' => $days[$day],
                'starts_at' => $range['start'],
                'ends_at' => $range['end'],
                'active' => true,
            ]);
        }
    }
}


