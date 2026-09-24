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
use App\Support\MixtureAuthorizationPolicy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OperationalDashboardController extends Controller
{
    public function patientCatalog(Request $request): View
    {
        $request->merge(['section' => 'patients']);

        return $this->index($request);
    }

    public function index(Request $request): View
    {
        $section = $request->string('section')->toString() ?: 'pending';
        $allowedSections = [
            'pending', 'history', 'patients', 'patient-create',
            'services-pending', 'services-history', 'services-preparation', 'services-scheduled', 'service-create', 'service-format',
            'mixes', 'mix-history', 'calendar', 'infusion-rooms', 'infusion-room-calendar', 'infusion-room-catalog',
            'support', 'support-ai', 'support-analytics',
        ];

        if (! in_array($section, $allowedSections, true)) {
            $section = 'pending';
        }

        $isPatientCatalog = in_array($section, ['patients', 'patient-create'], true);

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
            'oncology' => ['chemo', 'chemotherapy'],
            'inpatient-pharmacy' => ['npt', 'chemo', 'chemotherapy', 'import'],
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
            ->when($areaKey !== 'oncology', fn ($query) => $query->limit(12))
            ->get();

        $pendingProviderRequests = $providerRequests
            ->whereNotIn('status', ['draft', 'delivered', 'cancelled', 'rejected'])
            ->when(
                $areaKey === 'oncology',
                fn ($items) => $items->filter(
                    fn (ProviderRequest $item): bool => blank(data_get($item->payload, 'infusion_assignment'))
                ),
            )
            ->values();

        $calendarRequestPool = in_array($areaKey, ['nursing', 'oncology'], true)
            ? ProviderRequest::query()
                ->with(['patient'])
                ->when($unitId, fn ($query) => $query->where('medical_unit_id', $unitId))
                ->whereIn('request_type', $areaRequestTypes[$areaKey])
                ->latest('requested_at')
                ->limit(250)
                ->get()
            : collect();

        $preparationProviderRequests = $areaKey === 'oncology'
            ? $calendarRequestPool->where('status', 'draft')->values()
            : collect();
        $scheduledProviderRequests = $areaKey === 'oncology'
            ? $calendarRequestPool
                ->filter(fn (ProviderRequest $item): bool => filled(data_get($item->payload, 'infusion_assignment'))
                    && ! in_array($item->status, ['draft', 'cancelled', 'rejected'], true))
                ->values()
            : collect();
        $calendarProviderRequests = $areaKey === 'oncology'
            ? $scheduledProviderRequests
            : $calendarRequestPool;

        $historicalProviderRequests = $providerRequests
            ->whereIn('status', ['delivered', 'cancelled', 'rejected', 'requested', 'accepted', 'dispensed', 'preparing', 'ready', 'in_route'])
            ->values();

        $providerPatientIds = ProviderRequest::query()
            ->when($unitId, fn ($query) => $query->where('medical_unit_id', $unitId))
            ->when(! $isPatientCatalog, fn ($query) => $query->whereIn('request_type', $areaRequestTypes[$areaKey] ?? []))
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
            ->when($unitId, function ($query) use ($unitId, $providerPatientIds, $outpatientPrescriptionPatientIds): void {
                $query->where(function ($subQuery) use ($unitId, $providerPatientIds, $outpatientPrescriptionPatientIds): void {
                    $subQuery->where('metadata->medical_unit_id', $unitId)
                        ->orWhereHas('appointments', fn ($appointmentQuery) => $appointmentQuery->where('medical_unit_id', $unitId))
                        ->orWhereIn('id', $providerPatientIds)
                        ->orWhereIn('id', $outpatientPrescriptionPatientIds);
                });
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

        $selectedInfusionRoom = null;
        if ($request->filled('room') && in_array($section, ['infusion-room-calendar', 'infusion-room-catalog'], true)) {
            $selectedInfusionRoom = $infusionRooms->firstWhere('id', $request->integer('room'));
            abort_unless($selectedInfusionRoom, 404);
        }

        if ($section === 'infusion-room-calendar') {
            abort_unless($selectedInfusionRoom, 404);
            $calendarProviderRequests = $calendarProviderRequests
                ->filter(fn (ProviderRequest $item): bool => (int) data_get($item->payload, 'infusion_assignment.procedure_area_id') === (int) $selectedInfusionRoom->id)
                ->values();
        }

        $today = now()->startOfDay();
        $weekStart = $today->copy()->startOfWeek();
        $weekEnd = $today->copy()->endOfWeek();
        $monthStart = $today->copy()->startOfMonth();
        $monthEnd = $today->copy()->endOfMonth();
        $infusionRoomStats = $infusionRooms->mapWithKeys(function (ProcedureArea $room) use ($scheduledProviderRequests, $today, $weekStart, $weekEnd, $monthStart, $monthEnd): array {
            $appointmentDates = $scheduledProviderRequests
                ->filter(fn (ProviderRequest $item): bool => (int) data_get($item->payload, 'infusion_assignment.procedure_area_id') === (int) $room->id)
                ->map(fn (ProviderRequest $item): string => (string) data_get($item->payload, 'infusion_assignment.application_date'))
                ->filter(fn (string $date): bool => preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1)
                ->values();

            $operatingDays = $room->schedules
                ->pluck('day_of_week')
                ->map(fn ($day): int => (int) $day)
                ->unique();
            $monthlyOperatingDays = 0;
            for ($date = $monthStart->copy(); $date->lte($monthEnd); $date->addDay()) {
                if ($operatingDays->contains($date->dayOfWeek)) {
                    $monthlyOperatingDays++;
                }
            }

            $capacity = max(1, (int) $room->simultaneous_capacity);
            $monthAppointments = $appointmentDates->filter(
                fn (string $date): bool => $date >= $monthStart->toDateString() && $date <= $monthEnd->toDateString()
            )->count();
            $monthlyCapacity = $monthlyOperatingDays * $capacity;

            return [$room->id => [
                'today' => $appointmentDates->filter(fn (string $date): bool => $date === $today->toDateString())->count(),
                'week' => $appointmentDates->filter(
                    fn (string $date): bool => $date >= $weekStart->toDateString() && $date <= $weekEnd->toDateString()
                )->count(),
                'month' => $monthAppointments,
                'monthly_capacity' => $monthlyCapacity,
                'monthly_occupancy' => $monthlyCapacity > 0
                    ? min(100, round(($monthAppointments / $monthlyCapacity) * 100, 1))
                    : 0,
            ]];
        });

        return view('operational.dashboard', [
            'section' => $section,
            'areaKey' => $areaKey,
            'profile' => $profile,
            'contextUnit' => $contextUnit,
            'providerRequests' => $providerRequests,
            'calendarProviderRequests' => $calendarProviderRequests,
            'pendingProviderRequests' => $pendingProviderRequests,
            'historicalProviderRequests' => $historicalProviderRequests,
            'preparationProviderRequests' => $preparationProviderRequests,
            'scheduledProviderRequests' => $scheduledProviderRequests,
            'patients' => $patients,
            'inventory' => $inventory,
            'services' => $services,
            'orders' => $orders,
            'outpatientPrescriptions' => $outpatientPrescriptions,
            'infusionRooms' => $infusionRooms,
            'selectedInfusionRoom' => $selectedInfusionRoom,
            'infusionRoomStats' => $infusionRoomStats,
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
        $unit = $this->contextUnitFor($request);
        $patient = Patient::query()->create($this->patientDataPayload($data, medicalUnitId: $unit?->id));

        $audit->record($request, 'operational.patient.created', $patient, 'operational');

        return redirect()->route('operational.patients.index', $unit ? ['unit' => $unit->id] : [])
            ->with('status', 'Paciente registrado correctamente.');
    }

    public function updatePatient(Request $request, Patient $patient, PlatformAuditService $audit): RedirectResponse
    {
        $data = $this->validatePatientData($request, $patient);
        $unit = $this->contextUnitFor($request);
        $patient->update($this->patientDataPayload($data, $patient, $unit?->id));
        $audit->record($request, 'operational.patient.updated', $patient, 'operational');

        return redirect()->route('operational.patients.index', $unit ? ['unit' => $unit->id] : [])
            ->with('status', 'Paciente actualizado correctamente.');
    }

    public function storeServiceRequest(Request $request, PlatformAuditService $audit): RedirectResponse
    {
        if ($request->string('workflow')->toString() === 'oncology_center') {
            return $this->storeOncologyCenterRequest($request, $audit);
        }

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

    private function storeOncologyCenterRequest(Request $request, PlatformAuditService $audit): RedirectResponse
    {
        $isScheduled = $request->string('save_mode')->toString() === 'scheduled';
        $data = $request->validate([
            'workflow' => ['required', Rule::in(['oncology_center'])],
            'save_mode' => ['required', Rule::in(['preparation', 'scheduled'])],
            'patient_id' => ['required', 'exists:patients,id'],
            'service' => ['required', 'string', 'max:255'],
            'required_at' => ['required', 'date'],
            'diagnosis' => ['required', 'string', 'max:250'],
            'notes' => ['nullable', 'string', 'max:500'],
            'priority' => ['nullable', Rule::in(['routine', 'urgent'])],
            'authorization_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'oncology' => ['required', 'array'],
            'oncology.request_date' => ['nullable', 'date'],
            'oncology.facility' => ['required', 'string', 'max:255'],
            'oncology.floor' => ['nullable', 'string', 'max:80'],
            'oncology.bed' => ['nullable', 'string', 'max:80'],
            'oncology.patient_identifier' => ['nullable', 'string', 'max:120'],
            'oncology.sex' => ['required', Rule::in(['Femenino', 'Masculino', 'Otro'])],
            'oncology.age' => ['required', 'integer', 'min:0', 'max:130'],
            'oncology.weight' => ['required', 'numeric', 'min:0', 'max:500'],
            'oncology.height' => ['required', 'numeric', 'min:0', 'max:300'],
            'oncology.birth_date' => ['required', 'date', 'before_or_equal:today'],
            'oncology.body_surface' => ['nullable', 'numeric', 'min:0', 'max:10'],
            'oncology.delivery_method' => ['nullable', 'string', 'max:120'],
            'oncology.doctor_name' => ['required', 'string', 'max:180'],
            'oncology.professional_license' => ['nullable', 'string', 'max:120'],
            'oncology.medications' => ['required', 'array', 'max:8'],
            'oncology.medications.*.medication' => ['nullable', 'string', 'max:180'],
            'oncology.medications.*.dose' => ['nullable', 'string', 'max:120'],
            'oncology.medications.*.diluents' => ['nullable', 'array'],
            'oncology.medications.*.diluents.*' => ['string', Rule::in(['CS', 'DX', 'Otro'])],
            'oncology.medications.*.dilution_volume' => ['nullable', 'numeric', 'min:0'],
            'oncology.medications.*.boluses_per_day' => ['nullable', 'integer', 'min:0'],
            'oncology.medications.*.infusion_minutes' => ['nullable', 'integer', 'min:0'],
            'oncology.medications.*.delivery_dates' => ['nullable', 'array', 'max:3'],
            'oncology.medications.*.delivery_dates.*' => ['nullable', 'date'],
            'assignment' => ['nullable', 'array'],
            'assignment.procedure_area_id' => [Rule::requiredIf($isScheduled), 'nullable', 'integer', 'exists:procedure_areas,id'],
            'assignment.seat' => [Rule::requiredIf($isScheduled), 'nullable', 'string', 'max:40'],
            'assignment.application_date' => [Rule::requiredIf($isScheduled), 'nullable', 'date'],
            'assignment.starts_at' => [Rule::requiredIf($isScheduled), 'nullable', 'date_format:H:i'],
            'assignment.duration_minutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'assignment.nurse' => ['nullable', 'string', 'max:180'],
            'assignment.session_type' => ['nullable', 'string', 'max:120'],
            'assignment.notes' => ['nullable', 'string', 'max:500'],
        ]);

        $unit = $this->contextUnitFor($request);
        if (! $unit) {
            throw ValidationException::withMessages(['unit' => 'Selecciona una unidad medica para crear la solicitud.']);
        }

        $oncologyMedications = collect(data_get($data, 'oncology.medications', []))
            ->filter(fn (array $item): bool => filled($item['medication'] ?? null))
            ->values();
        if ($oncologyMedications->isEmpty()) {
            throw ValidationException::withMessages(['oncology.medications' => 'Agrega al menos un medicamento oncologico.']);
        }

        $patient = Patient::query()->findOrFail($data['patient_id']);
        $provider = Provider::query()->where('provider_type', 'chemo')->where('status', 'active')->first()
            ?? Provider::query()->where('status', 'active')->first();
        $attachment = $request->file('authorization_file');
        $attachmentPath = $attachment?->store('operational-oncology-requests', 'local');
        $assignment = $isScheduled
            ? $this->buildNewInfusionAssignment($request, $unit, $data['assignment'])
            : null;
        $firstMedication = $oncologyMedications->first();
        $mixtureMedications = $oncologyMedications->map(function (array $item) use ($assignment): array {
            $dose = trim((string) ($item['dose'] ?? ''));
            $volume = $item['dilution_volume'] ?? null;
            $minutes = $item['infusion_minutes'] ?? null;

            return [
                'medication_name' => $item['medication'],
                'dose' => $dose !== '' && ! str_contains(strtolower($dose), 'mg') ? $dose.' mg' : $dose,
                'diluent' => implode(', ', $item['diluents'] ?? []),
                'final_volume' => filled($volume) ? $volume.' ml' : null,
                'boluses_per_day' => $item['boluses_per_day'] ?? null,
                'infusion_duration' => filled($minutes) ? $minutes.' min' : null,
                'hour' => data_get($assignment, 'starts_at'),
                'delivery_dates' => collect($item['delivery_dates'] ?? [])->filter()->values()->all(),
            ];
        })->all();
        $status = $isScheduled ? 'requested' : 'draft';
        $requiredAt = $isScheduled
            ? Carbon::parse($assignment['application_date'].' '.$assignment['starts_at'])
            : Carbon::parse($data['required_at'])->startOfDay();
        $clinicalFormat = array_merge($data['oncology'], ['medications' => $oncologyMedications->all()]);
        $payload = [
            'source' => 'oncology_center',
            'service' => $data['service'],
            'requesting_service' => 'Centro Oncologico',
            'doctor' => data_get($data, 'oncology.doctor_name'),
            'diagnosis' => $data['diagnosis'],
            'notes' => $data['notes'] ?? null,
            'medication' => $firstMedication['medication'] ?? null,
            'dose' => $firstMedication['dose'] ?? null,
            'volume' => $firstMedication['dilution_volume'] ?? null,
            'priority' => $data['priority'] ?? 'routine',
            'clinical_format' => $clinicalFormat,
            'mixture_medications' => $mixtureMedications,
            'attachment' => $attachmentPath ? [
                'disk' => 'local',
                'path' => $attachmentPath,
                'original_name' => $attachment?->getClientOriginalName(),
            ] : null,
            'authorizations' => ['oncology' => 'approved', 'pharmacy' => 'pending'],
            'infusion_room_status' => $isScheduled ? 'scheduled' : 'pending',
        ];
        if ($assignment) {
            $payload['infusion_assignment'] = $assignment;
        }

        $providerRequest = DB::transaction(function () use ($provider, $patient, $unit, $status, $requiredAt, $payload, $request): ProviderRequest {
            $providerRequest = ProviderRequest::query()->create([
                'provider_id' => $provider?->id,
                'patient_id' => $patient->id,
                'medical_unit_id' => $unit->id,
                'external_id' => 'ONC-'.str_pad((string) (ProviderRequest::query()->max('id') + 1), 4, '0', STR_PAD_LEFT),
                'request_type' => 'chemo',
                'status' => $status,
                'requested_at' => now(),
                'required_at' => $requiredAt,
                'payload' => $payload,
            ]);

            ProviderRequestStatusEvent::query()->create([
                'provider_request_id' => $providerRequest->id,
                'status' => $status,
                'actor' => $request->user()?->name,
                'occurred_at' => now(),
                'metadata' => ['source' => 'oncology_center'],
            ]);

            return $providerRequest;
        });

        $audit->record($request, 'operational.oncology_request.created', $providerRequest, 'operational', [
            'save_mode' => $data['save_mode'],
        ]);

        if ($isScheduled) {
            return redirect()->route('operational.dashboard', [
                'area' => 'oncology',
                'section' => 'calendar',
                'oncology_track' => 'infusions',
                'unit' => $unit->id,
                'calendar_month' => substr($assignment['application_date'], 0, 7),
            ])->with('status', 'Infusion programada correctamente.');
        }

        return redirect()->route('operational.dashboard', [
            'area' => 'oncology',
            'section' => 'services-preparation',
            'oncology_track' => 'infusions',
            'unit' => $unit->id,
        ])->with('status', 'Solicitud guardada en preparacion.');
    }

    private function buildNewInfusionAssignment(
        Request $request,
        MedicalUnit $unit,
        array $data,
        ?int $excludedRequestId = null,
    ): array
    {
        $room = ProcedureArea::query()
            ->with('schedules')
            ->whereKey($data['procedure_area_id'])
            ->where('medical_unit_id', $unit->id)
            ->where('type', 'infusion')
            ->where('status', 'active')
            ->firstOrFail();
        [$seatRoomId, $seatNumber] = array_pad(explode('-', (string) $data['seat'], 2), 2, null);
        $seatNumber = (int) $seatNumber;

        if ((int) $seatRoomId !== (int) $room->id || $seatNumber < 1 || $seatNumber > max(1, (int) $room->simultaneous_capacity)) {
            throw ValidationException::withMessages(['assignment.seat' => 'Selecciona un sillon o cama disponible de la sala indicada.']);
        }

        $duration = (int) ($data['duration_minutes'] ?? 60);
        $startsAt = Carbon::parse($data['application_date'].' '.$data['starts_at']);
        $endsAt = $startsAt->copy()->addMinutes($duration);
        if (! $startsAt->isSameDay($endsAt)) {
            throw ValidationException::withMessages(['assignment.duration_minutes' => 'La duracion debe terminar el mismo dia.']);
        }

        $startTime = $startsAt->format('H:i');
        $endTime = $endsAt->format('H:i');
        $schedule = $room->schedules->first(fn ($item) =>
            $item->active
            && (int) $item->day_of_week === $startsAt->dayOfWeek
            && substr((string) $item->starts_at, 0, 5) <= $startTime
            && substr((string) $item->ends_at, 0, 5) >= $endTime
        );
        if (! $schedule) {
            throw ValidationException::withMessages(['assignment.procedure_area_id' => 'La sala no esta disponible dentro de ese horario.']);
        }

        $overlappingAssignments = ProviderRequest::query()
            ->where('medical_unit_id', $unit->id)
            ->whereIn('request_type', ['chemo', 'chemotherapy'])
            ->when($excludedRequestId, fn ($query) => $query->where('id', '!=', $excludedRequestId))
            ->whereNotIn('status', ['draft', 'cancelled', 'rejected'])
            ->get()
            ->filter(function (ProviderRequest $item) use ($room, $data, $startTime, $endTime): bool {
                $assignment = data_get($item->payload, 'infusion_assignment');

                return (int) data_get($assignment, 'procedure_area_id') === (int) $room->id
                    && data_get($assignment, 'application_date') === $data['application_date']
                    && data_get($assignment, 'starts_at') < $endTime
                    && data_get($assignment, 'ends_at') > $startTime;
            });

        if ($overlappingAssignments->count() >= max(1, (int) $room->simultaneous_capacity)) {
            throw ValidationException::withMessages(['assignment.procedure_area_id' => 'La sala alcanzo su capacidad simultanea para ese horario.']);
        }
        if ($overlappingAssignments->contains(fn (ProviderRequest $item): bool => (int) data_get($item->payload, 'infusion_assignment.seat_number') === $seatNumber)) {
            throw ValidationException::withMessages(['assignment.seat' => 'El sillon o cama seleccionado ya esta ocupado en ese horario.']);
        }

        return [
            'procedure_area_id' => $room->id,
            'room_number' => $room->unit_number,
            'room_location' => $room->location,
            'seat_number' => $seatNumber,
            'seat_label' => 'Sillon o cama '.$seatNumber,
            'application_date' => $data['application_date'],
            'starts_at' => $startTime,
            'ends_at' => $endTime,
            'duration_minutes' => $duration,
            'nurse' => $data['nurse'] ?? $room->responsible_name,
            'session_type' => $data['session_type'] ?? null,
            'notes' => $data['notes'] ?? null,
            'scheduled_by' => $request->user()?->name,
            'scheduled_at' => now()->toDateTimeString(),
        ];
    }

    public function updateProviderRequestStatus(Request $request, ProviderRequest $providerRequest, PlatformAuditService $audit, DomainStateTransitionService $transitions): RedirectResponse
    {
        $this->authorizeProviderRequestOwnership($request, $providerRequest);

        $data = $request->validate([
            'status' => ['required', Rule::in(['draft', 'requested', 'accepted', 'dispensed', 'preparing', 'ready', 'in_route', 'delivered', 'rejected', 'cancelled'])],
            'notes' => ['nullable', 'string', 'max:500'],
            'provider_name' => ['nullable', 'string', 'max:160'],
            'operating_area' => ['nullable', Rule::in(['nursing', 'oncology', 'inpatient-pharmacy'])],
        ]);

        if ($data['status'] === 'cancelled') {
            $payload = $providerRequest->payload ?? [];
            $allAuthorizationsApproved = MixtureAuthorizationPolicy::allApproved($payload, $providerRequest->request_type);

            abort_if(
                MixtureAuthorizationPolicy::cancellationLockedByCbta($providerRequest->mixtureIntegration?->remote_status),
                422,
                'La solicitud ya fue aprobada en Mezclas y no puede cancelarse desde Dr. Sam.',
            );

            abort_unless(
                ($data['operating_area'] ?? null) === 'inpatient-pharmacy' && $allAuthorizationsApproved,
                403,
                'Solo Farmacia intrahospitalaria puede cancelar una solicitud con todas las autorizaciones aprobadas.',
            );
        }

        if ($data['status'] === 'accepted') {
            $payload = $providerRequest->payload ?? [];
            abort_if(
                in_array($providerRequest->mixtureIntegration?->remote_status, ['ready', 'in_route', 'delivered'], true)
                    || in_array($providerRequest->status, ['ready', 'in_route', 'delivered'], true),
                422,
                'La mezcla ya fue inspeccionada y no puede reenviarse al proveedor.',
            );
            if (in_array($providerRequest->request_type, ['npt', 'chemo'], true)) {
                abort_unless(
                    MixtureAuthorizationPolicy::allApproved($payload, $providerRequest->request_type),
                    422,
                    'La solicitud requiere todas las autorizaciones antes de enviarse al proveedor.',
                );
            } elseif (($required = $payload['authorization_requirements'] ?? []) !== []) {
                $authorizations = $payload['authorizations'] ?? [];
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
            'authorization' => ['required', Rule::in(['nursing', 'operational', 'pharmacy', 'oncology'])],
            'status' => ['required', Rule::in(['pending', 'approved', 'rejected'])],
            'notes' => ['nullable', 'string', 'max:500'],
            'operating_area' => ['nullable', Rule::in(['nursing', 'oncology', 'inpatient-pharmacy'])],
        ]);

        if ($data['authorization'] === 'operational' && $providerRequest->request_type !== 'chemo') {
            $data['authorization'] = 'nursing';
        }

        $contextOperatorRoles = ['superadmin', 'admin'];
        $operatingAreaAuthorization = [
            'nursing' => 'nursing',
            'oncology' => 'oncology',
            'inpatient-pharmacy' => 'pharmacy',
        ][$data['operating_area'] ?? ''] ?? null;

        $operatorProfile = OperationalProfile::query()
            ->with('area')
            ->where('user_id', $request->user()?->id)
            ->first();
        $profileAreaAuthorization = [
            'nursing' => 'nursing',
            'enfermeria' => 'nursing',
            'oncology' => 'oncology',
            'oncologia' => 'oncology',
            'inpatient-pharmacy' => 'pharmacy',
            'farmacia' => 'pharmacy',
        ][$operatorProfile?->area?->key ?? ''] ?? null;

        if (! in_array($request->user()?->role, $contextOperatorRoles, true)) {
            abort_unless(
                $operatingAreaAuthorization === $data['authorization']
                    && ($request->user()?->role !== 'operational' || $profileAreaAuthorization === $data['authorization']),
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
            $authorizations = MixtureAuthorizationPolicy::normalize($payload['authorizations'] ?? [], $providerRequest->request_type);
            $previousStatus = MixtureAuthorizationPolicy::status($authorizations, $data['authorization'], $providerRequest->request_type);
            $authorizations[$data['authorization']] = $data['status'];
            if ($data['authorization'] === 'nursing' && $providerRequest->request_type !== 'chemo' && array_key_exists('operational', $authorizations)) {
                $authorizations['operational'] = $data['status'];
            }
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

            $required = MixtureAuthorizationPolicy::requirements($providerRequest->request_type);
            $payload['authorization_requirements'] = $required;

            if ($data['status'] === 'rejected') {
                $areaLabels = [
                    'nursing' => 'Enfermería',
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
            'section' => 'infusion-room-catalog',
            'oncology_track' => 'infusions',
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
            'section' => 'infusion-room-catalog',
            'oncology_track' => 'infusions',
            'unit' => $unit->id,
        ])->with('status', 'Sala de infusiÃ³n actualizada correctamente.');
    }

    public function assignInfusionRoom(Request $request, ProviderRequest $providerRequest, PlatformAuditService $audit): RedirectResponse
    {
        $this->authorizeProviderRequestOwnership($request, $providerRequest);
        abort_unless(in_array($providerRequest->request_type, ['chemo', 'chemotherapy'], true), 404);

        if ($request->has('assignment')) {
            return $this->assignIncomingOncologyRequest($request, $providerRequest, $audit);
        }

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

    private function assignIncomingOncologyRequest(
        Request $request,
        ProviderRequest $providerRequest,
        PlatformAuditService $audit,
    ): RedirectResponse {
        $data = $request->validate([
            'assignment' => ['required', 'array'],
            'assignment.procedure_area_id' => ['required', 'integer', 'exists:procedure_areas,id'],
            'assignment.seat' => ['required', 'string', 'max:40'],
            'assignment.application_date' => ['required', 'date'],
            'assignment.starts_at' => ['required', 'date_format:H:i'],
            'assignment.duration_minutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'assignment.nurse' => ['nullable', 'string', 'max:180'],
            'assignment.session_type' => ['nullable', 'string', 'max:120'],
            'assignment.notes' => ['nullable', 'string', 'max:500'],
        ]);

        $unit = $providerRequest->medicalUnit;
        abort_unless($unit, 404);

        $assignment = $this->buildNewInfusionAssignment(
            $request,
            $unit,
            $data['assignment'],
            $providerRequest->id,
        );
        $payload = $providerRequest->payload ?? [];
        $payload['infusion_assignment'] = $assignment;
        $payload['infusion_room_status'] = 'scheduled';

        if (blank(data_get($payload, 'mixture_medications'))) {
            $payload['mixture_medications'] = $this->normalizeIncomingMixtureMedications($payload, $assignment);
        }

        DB::transaction(function () use ($providerRequest, $payload, $assignment, $request): void {
            $providerRequest->update([
                'required_at' => Carbon::parse($assignment['application_date'].' '.$assignment['starts_at']),
                'payload' => $payload,
            ]);

            ProviderRequestStatusEvent::query()->create([
                'provider_request_id' => $providerRequest->id,
                'status' => $providerRequest->status,
                'actor' => $request->user()?->name,
                'occurred_at' => now(),
                'metadata' => ['source' => 'oncology_center', 'stage' => 'infusion_assignment'],
            ]);
        });

        $audit->record(
            $request,
            'operational.infusion_room.assigned',
            $providerRequest,
            'operational',
            $assignment,
        );

        return redirect()->route('operational.dashboard', [
            'area' => 'oncology',
            'section' => 'services-scheduled',
            'oncology_track' => 'infusions',
            'unit' => $unit->id,
        ])->with('status', 'Sala de infusion asignada correctamente.');
    }

    private function normalizeIncomingMixtureMedications(array $payload, array $assignment): array
    {
        return collect(data_get($payload, 'clinical_format.medications', []))
            ->filter(fn ($item): bool => is_array($item) && filled($item['medication'] ?? null))
            ->map(function (array $item) use ($assignment): array {
                $dose = trim((string) ($item['dose'] ?? ''));
                $volume = $item['dilution_volume'] ?? null;
                $minutes = $item['infusion_minutes'] ?? null;

                return [
                    'medication_name' => $item['medication'],
                    'dose' => $dose !== '' && ! str_contains(strtolower($dose), 'mg') ? $dose.' mg' : $dose,
                    'diluent' => implode(', ', $item['diluents'] ?? []),
                    'final_volume' => filled($volume) ? $volume.' ml' : null,
                    'boluses_per_day' => $item['boluses_per_day'] ?? null,
                    'infusion_duration' => filled($minutes) ? $minutes.' min' : null,
                    'hour' => $assignment['starts_at'],
                    'delivery_dates' => collect($item['delivery_dates'] ?? [])->filter()->values()->all(),
                ];
            })
            ->values()
            ->all();
    }

    public function updateMixtureSchedule(Request $request, ProviderRequest $providerRequest, PlatformAuditService $audit): RedirectResponse
    {
        $this->authorizeProviderRequestOwnership($request, $providerRequest);
        abort_unless(in_array($providerRequest->request_type, ['chemo', 'chemotherapy'], true), 404);

        $data = $request->validate([
            'medication_index' => ['required', 'integer', 'min:0', 'max:49'],
            'application_date' => ['required', 'date'],
            'oncology_track' => ['nullable', Rule::in(['infusions', 'mixes'])],
        ]);

        $payload = $providerRequest->payload ?? [];
        $medications = data_get($payload, 'mixture_medications') ?: data_get($payload, 'prescription_items') ?: [
            ['medication_name' => 'Paclitaxel'],
            ['medication_name' => 'Ondansetron'],
            ['medication_name' => 'Dexametasona'],
        ];
        $medicationIndex = (int) $data['medication_index'];

        abort_unless(array_key_exists($medicationIndex, array_values($medications)), 422, 'El medicamento seleccionado no existe en la solicitud.');

        $mixtureSchedule = data_get($payload, 'mixture_schedule', []);
        $currentSchedule = $mixtureSchedule[$medicationIndex] ?? [];
        $mixtureSchedule[$medicationIndex] = array_merge($currentSchedule, [
            'medication_index' => $medicationIndex,
            'medication_name' => data_get($medications, $medicationIndex.'.medication_name', 'Medicamento '.($medicationIndex + 1)),
            'application_date' => $data['application_date'],
            'scheduled_by' => $request->user()?->name,
            'scheduled_at' => now()->toDateTimeString(),
        ]);
        $payload['mixture_schedule'] = $mixtureSchedule;

        $providerRequest->update(['payload' => $payload]);

        $audit->record(
            $request,
            'operational.oncology_mixture.scheduled',
            $providerRequest,
            'operational',
            $mixtureSchedule[$medicationIndex],
        );

        return redirect()->route('operational.dashboard', [
            'area' => 'oncology',
            'section' => 'calendar',
            'oncology_track' => $data['oncology_track'] ?? 'mixes',
            'unit' => $providerRequest->medical_unit_id,
            'calendar_month' => substr($data['application_date'], 0, 7),
        ])->with('status', 'Fecha de la mezcla actualizada correctamente.');
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
        $profile = OperationalProfile::query()
            ->where('user_id', $request->user()?->id)
            ->first();

        if (! $request->filled('unit')) {
            return $profile?->medicalUnit;
        }

        $unit = MedicalUnit::query()->with('institution')->findOrFail($request->integer('unit'));
        $user = $request->user();
        $canUseUnit = in_array($user?->role, ['superadmin', 'admin'], true)
            || ($user?->role === 'unit' && $unit->unit_username === $user->username)
            || ($user?->role === 'institution' && (int) $unit->institution_id === (int) $user->institution?->id)
            || ($user?->role === 'operational' && (int) $profile?->medical_unit_id === (int) $unit->id);

        abort_unless($canUseUnit, 403, 'No puedes operar otra unidad.');

        return $unit;
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

    private function patientDataPayload(array $data, ?Patient $patient = null, ?int $medicalUnitId = null): array
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
                'medical_unit_id' => $medicalUnitId ?? data_get($patient?->metadata, 'medical_unit_id'),
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


