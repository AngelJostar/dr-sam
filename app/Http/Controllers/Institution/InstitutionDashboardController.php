<?php

namespace App\Http\Controllers\Institution;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\ContractedService;
use App\Models\Institution;
use App\Models\InventoryItem;
use App\Models\MedicalUnit;
use App\Models\MedicationCatalogItem;
use App\Models\ProviderRequest;
use App\Models\Service;
use App\Models\User;
use App\Services\Platform\PlatformAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InstitutionDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $institution = $this->resolveInstitution($request);

        $institution->load([
            'owner',
            'medicalUnits.contractedServices.service',
            'medicalUnits.doctors',
            'medicalUnits.operationalProfiles.area',
            'services.service',
        ]);

        $unitIds = $institution->medicalUnits->pluck('id');

        $appointments = Appointment::query()
            ->with(['patient', 'doctor', 'medicalUnit'])
            ->whereIn('medical_unit_id', $unitIds)
            ->latest('starts_at')
            ->get();

        $providerRequests = ProviderRequest::query()
            ->with(['provider', 'patient', 'medicalUnit'])
            ->whereIn('medical_unit_id', $unitIds)
            ->latest('requested_at')
            ->get();

        $inventory = InventoryItem::query()
            ->with(['product', 'medicalUnit'])
            ->whereIn('medical_unit_id', $unitIds)
            ->latest()
            ->limit(10)
            ->get();

        return view('institution.dashboard', [
            'institution' => $institution,
            'availableInstitutions' => in_array($request->user()?->role, ['superadmin', 'admin'], true)
                ? Institution::query()->orderBy('name')->get()
                : collect([$institution]),
            'servicesCatalog' => $this->institutionServiceCatalog($institution),
            'appointments' => $appointments,
            'providerRequests' => $providerRequests,
            'inventory' => $inventory,
            'catalogItems' => MedicationCatalogItem::query()
                ->where('institution_id', $institution->id)
                ->orderBy('cnis')
                ->get(),
            'metrics' => [
                'Unidades' => $institution->medicalUnits->count(),
                'Camas' => $institution->medicalUnits->sum('beds'),
                'Medicos' => $institution->medicalUnits->sum(fn ($unit) => $unit->doctors->count()),
                'Areas operativas' => $institution->medicalUnits->sum(fn ($unit) => $unit->operationalProfiles->count()),
                'Servicios activos' => ContractedService::query()
                    ->where('institution_id', $institution->id)
                    ->where('status', 'active')
                    ->count(),
                'Citas programadas' => Appointment::query()
                    ->whereIn('medical_unit_id', $unitIds)
                    ->where('status', 'scheduled')
                    ->count(),
                'Solicitudes abiertas' => ProviderRequest::query()
                    ->whereIn('medical_unit_id', $unitIds)
                    ->whereNotIn('status', ['delivered', 'cancelled', 'rejected'])
                    ->count(),
            ],
        ]);
    }

    public function updateUnit(Request $request, MedicalUnit $unit, PlatformAuditService $audit): RedirectResponse
    {
        $institution = $this->resolveInstitution($request);
        abort_unless($unit->institution_id === $institution->id, 404);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'], 'clues' => ['nullable', 'string', 'max:120'], 'code' => ['nullable', 'string', 'max:120'],
            'status' => ['required', Rule::in(['active', 'inactive', 'maintenance', 'suspended'])], 'unit_username' => ['nullable', 'string', 'max:120'],
            'entity' => ['nullable', 'string', 'max:120'], 'municipality' => ['nullable', 'string', 'max:120'], 'care_level' => ['nullable', 'string', 'max:120'],
            'typology' => ['nullable', 'string', 'max:120'], 'type' => ['nullable', 'string', 'max:120'], 'beds' => ['nullable', 'integer', 'min:0'],
            'address' => ['nullable', 'string'], 'latitude' => ['nullable', 'numeric'], 'longitude' => ['nullable', 'numeric'], 'unit_password' => ['nullable', 'string', 'max:255'],
        ]);
        $metadata = $unit->metadata ?? [];
        if (filled($data['unit_password'] ?? null)) $metadata['demo_password'] = $data['unit_password'];
        $unit->update([...collect($data)->except('unit_password')->all(), 'metadata' => $metadata]);
        $audit->record($request, 'institution.unit.updated', $unit, 'institution', ['institution_id' => $institution->id]);
        return redirect()->route('institution.dashboard', ['institution' => $institution->id])->with('status', 'Unidad actualizada.');
    }

    public function updateUnitPassword(Request $request, MedicalUnit $unit, PlatformAuditService $audit): RedirectResponse
    {
        $institution = $this->resolveInstitution($request);
        abort_unless($unit->institution_id === $institution->id, 404);

        $data = $request->validate([
            'unit_password' => ['required', 'string', 'min:6', 'max:255'],
            'form_context' => ['nullable', 'string'],
            'unit_id' => ['nullable', 'integer'],
        ]);

        $unitUser = $this->resolveUnitUser($unit);
        if (! $unitUser) {
            return back()
                ->withInput()
                ->withErrors(['unit_password' => 'No se encontró el usuario asociado a esta unidad.']);
        }

        DB::transaction(function () use ($data, $unit, $unitUser): void {
            $metadata = $unit->metadata ?? [];
            $metadata['demo_password'] = $data['unit_password'];
            $metadata['user_id'] = $unitUser->id;

            $unit->update(['metadata' => $metadata]);
            $unitUser->update(['password' => Hash::make($data['unit_password'])]);
        });

        $audit->record($request, 'institution.unit.password.updated', $unit, 'institution', [
            'institution_id' => $institution->id,
            'user_id' => $unitUser->id,
        ]);

        return redirect()
            ->route('institution.dashboard', ['institution' => $institution->id])
            ->with('status', 'Contraseña de unidad actualizada.');
    }

    private function institutionServiceCatalog(Institution $institution)
    {
        $definitions = [
            'consulta-externa' => ['category' => 'Atencion medica', 'specialty' => 'Consulta Externa', 'name' => 'Consulta externa'],
            'hemodinamia' => ['category' => 'Atencion medica', 'specialty' => 'Hemodinamia', 'name' => 'Hemodinamia'],
            'laboratorio' => ['category' => 'Diagnostico', 'specialty' => 'Laboratorio clinico', 'name' => 'Laboratorio'],
            'nutricion-parenteral' => ['category' => 'Farmaceuticos', 'specialty' => 'Central de Mezclas de Nutricion Parenteral', 'name' => 'Nutricion Parenteral'],
            'quimioterapias' => ['category' => 'Farmaceuticos', 'specialty' => 'Central de Mezclas Oncologicas', 'name' => 'Quimioterapia'],
            'central-de-mezclas' => ['category' => 'Farmaceuticos', 'specialty' => 'Central de mezclas', 'name' => 'Central de mezclas'],
            'mantenimiento-equipo-medico' => ['category' => 'Soporte clinico', 'specialty' => 'Mantenimiento a equipo medico', 'name' => 'Mantenimiento a equipo medico'],
            'osteosintesis' => ['category' => 'Traumatologia', 'specialty' => 'Osteosintesis', 'name' => 'Osteosintesis'],
        ];

        foreach ($definitions as $externalId => $definition) {
            Service::query()->updateOrCreate(
                ['external_id' => $externalId],
                $definition + ['status' => 'active'],
            );
        }

        $services = Service::query()
            ->with(['contractedServices' => fn ($query) => $query
                ->with('medicalUnit')
                ->where('institution_id', $institution->id)])
            ->whereIn('external_id', array_keys($definitions))
            ->get()
            ->keyBy('external_id');

        return collect(array_keys($definitions))
            ->map(fn (string $externalId) => $services->get($externalId))
            ->filter()
            ->values();
    }

    public function updateUnitStatus(Request $request, MedicalUnit $unit, PlatformAuditService $audit): RedirectResponse
    {
        $this->authorizeUnitOwnership($request, $unit);

        $data = $request->validate([
            'status' => ['required', Rule::in(['active', 'inactive', 'maintenance', 'suspended'])],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $metadata = $unit->metadata ?? [];
        $metadata['last_status_note'] = $data['notes'] ?? null;
        $metadata['last_status_changed_by'] = $request->user()?->id;
        $metadata['last_status_changed_at'] = now()->toISOString();

        $unit->update([
            'status' => $data['status'],
            'metadata' => $metadata,
        ]);

        $audit->record($request, 'institution.unit.status_updated', $unit, 'institution', [
            'status' => $unit->status,
        ]);

        return back()->with('status', 'Unidad actualizada desde institucion.');
    }

    public function syncServiceUnits(Request $request, Service $service, PlatformAuditService $audit): RedirectResponse
    {
        $institution = $this->resolveInstitution($request);
        $data = $request->validate([
            'unit_ids' => ['nullable', 'array'],
            'unit_ids.*' => ['integer'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'sla' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'conditions' => ['nullable', 'string', 'max:5000'],
        ]);

        $unitIds = collect($data['unit_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->values();
        $ownedUnitIds = $institution->medicalUnits()->whereIn('id', $unitIds)->pluck('id');
        abort_unless($ownedUnitIds->count() === $unitIds->count(), 403, 'No puedes asignar unidades de otra institucion.');

        ContractedService::query()
            ->where('institution_id', $institution->id)
            ->where('service_id', $service->id)
            ->whereNotIn('medical_unit_id', $unitIds)
            ->update(['status' => 'inactive']);

        foreach ($unitIds as $unitId) {
            $contract = ContractedService::query()->firstOrNew(
                [
                    'institution_id' => $institution->id,
                    'medical_unit_id' => $unitId,
                    'service_id' => $service->id,
                ],
            );
            $metadata = $contract->metadata ?? [];
            $metadata['sla'] = $data['sla'] ?? null;
            $metadata['conditions'] = $data['conditions'] ?? null;
            $contract->fill([
                'status' => $data['status'] ?? 'active',
                'starts_at' => $data['starts_at'] ?? null,
                'ends_at' => $data['ends_at'] ?? null,
                'metadata' => $metadata,
            ])->save();
        }

        $audit->record($request, 'institution.service.units_synced', $service, 'institution', [
            'institution_id' => $institution->id,
            'unit_ids' => $unitIds->all(),
        ]);

        return redirect()
            ->route('institution.dashboard', ['institution' => $institution->id, 'section' => 'services'])
            ->with('status', 'Servicio habilitado en las unidades seleccionadas.');
    }

    public function updateService(Request $request, Service $service, PlatformAuditService $audit): RedirectResponse
    {
        $institution = $this->resolveInstitution($request);
        $data = $request->validate([
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'summary' => ['nullable', 'string', 'max:5000'],
            'conditions' => ['nullable', 'string', 'max:5000'],
            'sla' => ['nullable', 'string', 'max:120'],
            'contract_pdf' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'contract_provider' => ['nullable', 'string', 'max:180'],
            'contract_validity' => ['nullable', 'string', 'max:180'],
            'unit_ids' => ['nullable', 'array'],
            'unit_ids.*' => ['integer'],
        ]);

        $unitIds = collect($data['unit_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->values();
        $ownedUnitIds = $institution->medicalUnits()->whereIn('id', $unitIds)->pluck('id');
        abort_unless($ownedUnitIds->count() === $unitIds->count(), 403, 'No puedes asignar unidades de otra institucion.');

        DB::transaction(function () use ($request, $data, $institution, $service, $unitIds): void {
            $metadata = $service->metadata ?? [];
            $metadata['summary'] = $data['summary'] ?? null;
            $metadata['conditions'] = $data['conditions'] ?? null;
            $metadata['sla'] = $data['sla'] ?? null;
            $metadata['contract_provider'] = $data['contract_provider'] ?? data_get($metadata, 'contract_provider');
            $metadata['contract_validity'] = $data['contract_validity'] ?? data_get($metadata, 'contract_validity');

            if ($request->hasFile('contract_pdf')) {
                $file = $request->file('contract_pdf');
                $metadata['contract_pdf'] = $file->store('service-contracts', 'public');
                $metadata['contract_filename'] = $file->getClientOriginalName();
            }

            $service->update([
                'status' => $data['status'],
                'metadata' => $metadata,
            ]);

            ContractedService::query()
                ->where('institution_id', $institution->id)
                ->where('service_id', $service->id)
                ->whereNotIn('medical_unit_id', $unitIds)
                ->update(['status' => 'inactive']);

            foreach ($unitIds as $unitId) {
                $contract = ContractedService::query()->firstOrNew([
                    'institution_id' => $institution->id,
                    'medical_unit_id' => $unitId,
                    'service_id' => $service->id,
                ]);
                $contractMetadata = $contract->metadata ?? [];
                $contractMetadata['sla'] = $data['sla'] ?? null;
                $contract->fill([
                    'status' => 'active',
                    'metadata' => $contractMetadata,
                ]);
                $contract->save();
            }
        });

        $audit->record($request, 'institution.service.updated', $service, 'institution', [
            'institution_id' => $institution->id,
            'unit_ids' => $unitIds->all(),
        ]);

        return redirect()
            ->route('institution.dashboard', ['institution' => $institution->id, 'section' => 'services'])
            ->with('status', 'Datos del servicio actualizados.');
    }

    public function storeService(Request $request, PlatformAuditService $audit): RedirectResponse
    {
        $institution = $this->resolveInstitution($request);
        $data = $request->validate([
            'unit_ids' => ['nullable', 'array'],
            'unit_ids.*' => ['integer'],
            'category' => ['required', 'string', 'max:120'],
            'specialty' => ['required', 'string', 'max:180'],
            'name' => ['required', 'string', 'max:255'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after_or_equal:starts_at'],
            'sla' => ['required', 'string', 'max:120'],
        ]);

        $unitIds = collect($data['unit_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->values();
        $ownedUnitIds = $institution->medicalUnits()->whereIn('id', $unitIds)->pluck('id');
        abort_unless($ownedUnitIds->count() === $unitIds->count(), 403, 'No puedes asignar unidades de otra institucion.');

        $service = DB::transaction(function () use ($data, $institution, $unitIds): Service {
            $service = Service::query()->create([
                'category' => $data['category'],
                'specialty' => $data['specialty'],
                'name' => $data['name'],
                'status' => 'active',
                'metadata' => [
                    'institution_ids' => [$institution->id],
                    'sla' => $data['sla'],
                ],
            ]);

            foreach ($unitIds as $unitId) {
                ContractedService::query()->create([
                    'institution_id' => $institution->id,
                    'medical_unit_id' => $unitId,
                    'service_id' => $service->id,
                    'starts_at' => $data['starts_at'],
                    'ends_at' => $data['ends_at'],
                    'status' => 'active',
                    'metadata' => ['sla' => $data['sla']],
                ]);
            }

            return $service;
        });

        $audit->record($request, 'institution.service.created', $service, 'institution', [
            'institution_id' => $institution->id,
            'unit_ids' => $unitIds->all(),
        ]);

        return redirect()
            ->route('institution.dashboard', ['institution' => $institution->id, 'section' => 'services'])
            ->with('status', 'Servicio creado correctamente.');
    }

    public function storeMedication(Request $request, PlatformAuditService $audit): RedirectResponse
    {
        $institution = $this->resolveInstitution($request);
        $data = $request->validate([
            'cnis' => ['required', 'string', 'max:120', Rule::unique('medication_catalog_items', 'cnis')->where('institution_id', $institution->id)],
            'therapeutic_group' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:3000'],
            'mobile_units' => ['required', Rule::in(['undefined', 'yes', 'no'])],
            'basic_units' => ['required', Rule::in(['undefined', 'yes', 'no'])],
            'cessa' => ['required', Rule::in(['undefined', 'yes', 'no'])],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $availability = fn (string $value) => match ($value) {
            'yes' => 'Si',
            'no' => 'No',
            default => 'Sin definir',
        };

        $medication = MedicationCatalogItem::query()->create([
            'institution_id' => $institution->id,
            'cnis' => $data['cnis'],
            'name' => $data['name'],
            'generic_name' => $data['name'],
            'therapeutic_group' => $data['therapeutic_group'],
            'description' => $data['description'],
            'status' => $data['status'],
            'metadata' => [
                'group' => $data['therapeutic_group'],
                'mobile_units' => $availability($data['mobile_units']),
                'basic_units' => $availability($data['basic_units']),
                'cessa' => $availability($data['cessa']),
                'catalog' => $institution->name,
            ],
        ]);

        $audit->record($request, 'institution.medication.created', $medication, 'institution', [
            'institution_id' => $institution->id,
            'cnis' => $medication->cnis,
        ]);

        return redirect()
            ->route('institution.dashboard', ['institution' => $institution->id, 'section' => 'medications'])
            ->with('status', 'Medicamento institucional guardado.');
    }

    public function updateMedication(
        Request $request,
        MedicationCatalogItem $medication,
        PlatformAuditService $audit,
    ): RedirectResponse {
        $institution = $this->resolveInstitution($request);
        abort_unless((int) $medication->institution_id === (int) $institution->id, 403, 'No puedes editar medicamentos de otra institucion.');

        $data = $request->validate([
            'cnis' => ['required', 'string', 'max:120', Rule::unique('medication_catalog_items', 'cnis')
                ->where('institution_id', $institution->id)->ignore($medication->id)],
            'therapeutic_group' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:3000'],
            'mobile_units' => ['required', Rule::in(['undefined', 'yes', 'no'])],
            'basic_units' => ['required', Rule::in(['undefined', 'yes', 'no'])],
            'cessa' => ['required', Rule::in(['undefined', 'yes', 'no'])],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $availability = fn (string $value) => match ($value) {
            'yes' => 'Si',
            'no' => 'No',
            default => 'Sin definir',
        };
        $metadata = $medication->metadata ?? [];
        $metadata['group'] = $data['therapeutic_group'];
        $metadata['mobile_units'] = $availability($data['mobile_units']);
        $metadata['basic_units'] = $availability($data['basic_units']);
        $metadata['cessa'] = $availability($data['cessa']);
        $metadata['catalog'] = $institution->name;

        $medication->update([
            'cnis' => $data['cnis'],
            'name' => $data['name'],
            'generic_name' => $data['name'],
            'therapeutic_group' => $data['therapeutic_group'],
            'description' => $data['description'],
            'status' => $data['status'],
            'metadata' => $metadata,
        ]);

        $audit->record($request, 'institution.medication.updated', $medication, 'institution', [
            'institution_id' => $institution->id,
            'cnis' => $medication->cnis,
        ]);

        return redirect()
            ->route('institution.dashboard', ['institution' => $institution->id, 'section' => 'medications'])
            ->with('status', 'Medicamento institucional actualizado.');
    }

    public function storeSpecialty(Request $request, PlatformAuditService $audit): RedirectResponse
    {
        $institution = $this->resolveInstitution($request);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('services', 'name')],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $specialty = Service::query()->create([
            'name' => $data['name'],
            'specialty' => $data['name'],
            'category' => 'Especialidades',
            'status' => $data['status'],
            'metadata' => [
                'catalog' => 'institucional',
                'institution_ids' => [$institution->id],
            ],
        ]);

        $audit->record($request, 'institution.specialty.created', $specialty, 'institution', [
            'institution_id' => $institution->id,
            'name' => $specialty->name,
        ]);

        return redirect()
            ->route('institution.dashboard', ['institution' => $institution->id, 'section' => 'specialties'])
            ->with('status', 'Especialidad institucional guardada.');
    }

    public function updateSpecialty(Request $request, Service $service, PlatformAuditService $audit): RedirectResponse
    {
        $institution = $this->resolveInstitution($request);
        $institutionIds = $this->authorizeSpecialtyAccess($institution, $service);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('services', 'name')->ignore($service->id)],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $metadata = $service->metadata ?? [];
        $metadata['catalog'] = 'institucional';
        $metadata['institution_ids'] = $institutionIds->push($institution->id)->unique()->values()->all();

        $service->update([
            'name' => $data['name'],
            'specialty' => $data['name'],
            'status' => $data['status'],
            'metadata' => $metadata,
        ]);

        $audit->record($request, 'institution.specialty.updated', $service, 'institution', [
            'institution_id' => $institution->id,
            'name' => $service->name,
        ]);

        return redirect()
            ->route('institution.dashboard', ['institution' => $institution->id, 'section' => 'specialties'])
            ->with('status', 'Especialidad institucional actualizada.');
    }

    public function updateSpecialtyStatus(Request $request, Service $service, PlatformAuditService $audit): RedirectResponse
    {
        $institution = $this->resolveInstitution($request);
        $this->authorizeSpecialtyAccess($institution, $service);
        $data = $request->validate(['status' => ['required', Rule::in(['active', 'inactive'])]]);
        $service->update(['status' => $data['status']]);

        $audit->record($request, 'institution.specialty.status_updated', $service, 'institution', [
            'institution_id' => $institution->id,
            'status' => $service->status,
        ]);

        return redirect()
            ->route('institution.dashboard', ['institution' => $institution->id, 'section' => 'specialties'])
            ->with('status', $service->status === 'active' ? 'Especialidad activada.' : 'Especialidad inactivada.');
    }

    public function destroySpecialty(Request $request, Service $service, PlatformAuditService $audit): RedirectResponse
    {
        $institution = $this->resolveInstitution($request);
        $this->authorizeSpecialtyAccess($institution, $service);

        $audit->record($request, 'institution.specialty.deleted', $service, 'institution', [
            'institution_id' => $institution->id,
            'name' => $service->name,
        ]);
        $service->delete();

        return redirect()
            ->route('institution.dashboard', ['institution' => $institution->id, 'section' => 'specialties'])
            ->with('status', 'Especialidad eliminada.');
    }

    public function storeUnit(Request $request, PlatformAuditService $audit): RedirectResponse
    {
        $institution = $this->resolveInstitution($request);
        $data = $request->validate([
            'clues' => ['required', 'string', 'max:120', Rule::unique('medical_units', 'clues')],
            'name' => ['required', 'string', 'max:255'],
            'entity' => ['required', 'string', 'max:160'],
            'municipality' => ['required', 'string', 'max:160'],
            'care_level' => ['required', 'string', 'max:160'],
            'typology' => ['required', 'string', 'max:160'],
            'address' => ['required', 'string', 'max:1000'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'partida' => ['nullable', 'string', 'max:255'],
            'subpartida' => ['nullable', 'string', 'max:255'],
            'beds' => ['required', 'integer', 'min:0', 'max:100000'],
            'unit_username' => ['required', 'string', 'max:80', Rule::unique('users', 'username'), Rule::unique('medical_units', 'unit_username')],
            'unit_password' => ['required', 'string', 'min:6', 'max:255'],
        ]);

        [$unit, $unitUser] = DB::transaction(function () use ($data, $institution): array {
            $unitUser = User::query()->create([
                'name' => $data['name'],
                'username' => $data['unit_username'],
                'password' => Hash::make($data['unit_password']),
                'role' => 'unit',
                'module' => 'unit',
                'status' => 'active',
                'is_demo' => true,
                'metadata' => ['institution_id' => $institution->id],
            ]);

            $unit = MedicalUnit::query()->create([
                'institution_id' => $institution->id,
                'external_id' => 'unit-'.str($data['clues'])->slug(),
                'code' => $data['clues'],
                'clues' => $data['clues'],
                'name' => $data['name'],
                'city' => $data['municipality'],
                'municipality' => $data['municipality'],
                'state' => $data['entity'],
                'entity' => $data['entity'],
                'type' => $data['typology'],
                'typology' => $data['typology'],
                'care_level' => $data['care_level'],
                'address' => $data['address'],
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'partidas' => filled($data['partida'] ?? null) ? [$data['partida']] : [],
                'subpartidas' => filled($data['subpartida'] ?? null) ? [$data['subpartida']] : [],
                'beds' => $data['beds'],
                'unit_username' => $data['unit_username'],
                'status' => 'active',
                'metadata' => [
                    'demo_password' => $data['unit_password'],
                    'user_id' => $unitUser->id,
                ],
            ]);

            return [$unit, $unitUser];
        });

        $audit->record($request, 'institution.unit.created', $unit, 'institution', [
            'institution_id' => $institution->id,
            'user_id' => $unitUser->id,
            'clues' => $unit->clues,
        ]);

        return redirect()
            ->route('institution.dashboard', ['institution' => $institution->id])
            ->with('status', 'Unidad y credenciales creadas correctamente.');
    }

    private function authorizeUnitOwnership(Request $request, MedicalUnit $unit): void
    {
        $user = $request->user();

        if (in_array($user?->role, ['superadmin', 'admin'], true)) {
            return;
        }

        abort_unless(
            $user?->institution && (int) $unit->institution_id === (int) $user->institution->id,
            403,
            'No puedes modificar unidades de otra institucion.',
        );
    }

    private function resolveUnitUser(MedicalUnit $unit): ?User
    {
        $userId = data_get($unit->metadata, 'user_id');
        $unitUser = $userId
            ? User::query()->whereKey($userId)->where('role', 'unit')->first()
            : null;

        if ($unitUser || blank($unit->unit_username)) {
            return $unitUser;
        }

        return User::query()
            ->where('username', $unit->unit_username)
            ->where('role', 'unit')
            ->first();
    }

    private function authorizeSpecialtyAccess(Institution $institution, Service $service): \Illuminate\Support\Collection
    {
        $institutionIds = collect(data_get($service->metadata, 'institution_ids', []))->map(fn ($id) => (int) $id);
        abort_if($institutionIds->isNotEmpty() && ! $institutionIds->contains($institution->id), 403, 'No puedes modificar especialidades de otra institucion.');

        return $institutionIds;
    }

    private function resolveInstitution(Request $request): Institution
    {
        $user = $request->user();

        if ($user?->institution) {
            return $user->institution;
        }

        abort_unless(in_array($user?->role, ['superadmin', 'admin'], true), 403);

        if ($request->filled('institution')) {
            return Institution::query()
                ->with('owner')
                ->findOrFail($request->integer('institution'));
        }

        return Institution::query()
            ->with('owner')
            ->orderBy('name')
            ->firstOrFail();
    }
}
