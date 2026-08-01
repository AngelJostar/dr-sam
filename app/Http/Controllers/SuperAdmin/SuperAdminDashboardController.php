<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Doctor;
use App\Models\Hospital;
use App\Models\InsuranceCarrier;
use App\Models\Institution;
use App\Models\MedicalUnit;
use App\Models\MedicalDevice;
use App\Models\Medication;
use App\Models\MedicationCatalogItem;
use App\Models\Patient;
use App\Models\PharmacyProduct;
use App\Models\PlatformModule;
use App\Models\Provider;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SuperAdminDashboardController extends Controller
{
    public function index(): View
    {
        $this->syncConfiguredModules();

        $activeModules = PlatformModule::query()->where('enabled', true)->count();
        $totalModules = PlatformModule::query()->count();

        return view('superadmin.dashboard', [
            'modules' => PlatformModule::query()->orderBy('label')->get(),
            'users' => User::query()->latest()->limit(20)->get(),
            'auditLogs' => AuditLog::query()->with('user')->latest()->limit(12)->get(),
            'roleLabels' => collect(UserRole::cases())->mapWithKeys(fn (UserRole $role) => [$role->value => $role->label()]),
            'activeModules' => $activeModules,
            'totalModules' => $totalModules,
            'metrics' => [
                'Modulos activos' => $activeModules,
                'Unidades' => MedicalUnit::query()->count(),
                'Medicos' => Doctor::query()->count(),
                'Usuarios plataforma' => User::query()->whereIn('role', ['doctor', 'patient'])->count(),
                'Vendedores' => Provider::query()->count(),
                'Hosp. privados' => Hospital::query()->count(),
                'Pacientes' => Patient::query()->count(),
                'Usuarios de acceso' => count(config('drsam.demo_users', [])),
                'Servicios' => Service::query()->count(),
                'Medicamentos' => Medication::query()->count() + MedicationCatalogItem::query()->count() + PharmacyProduct::query()->count(),
            ],
        ]);
    }

    public function catalog(Request $request, string $section): View
    {
        $catalog = $this->catalogDefinition($section);
        abort_if(! $catalog, 404);

        $query = $catalog['query']();
        $this->applyCatalogFilters($query, $catalog, $request);

        $records = method_exists($query, 'paginate')
            ? $query->paginate(20)
                ->withQueryString()
            : new LengthAwarePaginator($query, $query->count(), 20);

        $selectedModule = null;

        if ($section === 'modules') {
            $selectedKey = $request->string('selected_module')->toString();
            $selectedModule = $selectedKey !== ''
                ? PlatformModule::query()->where('key', $selectedKey)->first()
                : $records->first();
        }

        $selectedInstitution = null;

        if ($section === 'institutions') {
            $selectedId = $request->integer('selected_institution');
            $selectedInstitution = $selectedId
                ? Institution::query()->with(['owner'])->withCount(['medicalUnits', 'services'])->find($selectedId)
                : $records->first();
        }

        return view('superadmin.catalog', [
            'section' => $section,
            'catalog' => $catalog,
            'records' => $records,
            'selectedModule' => $selectedModule,
            'selectedInstitution' => $selectedInstitution,
            'subscriptionSettings' => $section === 'subscriptions' ? $this->subscriptionConfiguration() : null,
            'subscriptionUsers' => $section === 'subscriptions' ? $this->subscriptionUsers() : collect(),
            'filters' => [
                'q' => $request->string('q')->toString(),
                'status' => $request->string('status')->toString(),
                'module' => $request->string('module')->toString(),
            ],
        ]);
    }

    public function exportCatalog(Request $request, string $section): StreamedResponse
    {
        $catalog = $this->catalogDefinition($section);
        abort_if(! $catalog, 404);

        $query = $catalog['query']();
        $this->applyCatalogFilters($query, $catalog, $request);
        $records = method_exists($query, 'get') ? $query->limit(1000)->get() : $query;
        $filename = 'superadmin-'.$section.'-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($catalog, $records): void {
            $output = fopen('php://output', 'w');
            fputcsv($output, $catalog['columns']);

            foreach ($records as $record) {
                fputcsv($output, $catalog['map']($record));
            }

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function exportSubscriptionUsers(string $plan): StreamedResponse
    {
        $configuration = $this->subscriptionConfiguration();
        $selectedPlan = collect($configuration['plans'])->firstWhere('id', $plan);
        abort_if(! $selectedPlan, 404);
        $users = $this->subscriptionUsers()->where('plan_id', $plan)->values();

        return response()->streamDownload(function () use ($users): void {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Nombre', 'Apellidos', 'Pais', 'Ciudad', 'No. usuario plataforma', 'Edad', 'CURP', 'Correo', 'Telefono', 'Tipo de usuario', 'Plan', 'Estatus']);
            foreach ($users as $user) {
                fputcsv($output, [$user['first_name'], $user['last_name'], $user['country'], $user['city'], $user['platform_number'], $user['age'], $user['curp'], $user['email'], $user['phone'], $user['user_type'], $user['plan_name'], $user['status']]);
            }
            fclose($output);
        }, 'usuarios-plan-'.$plan.'-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function updateModule(Request $request, PlatformModule $module): RedirectResponse
    {
        $data = $request->validate([
            'enabled' => ['required', 'boolean'],
        ]);

        $module->update([
            'enabled' => (bool) $data['enabled'],
            'settings' => array_merge($module->settings ?? [], [
                'updated_from' => 'superadmin_dashboard',
                'updated_by' => $request->user()?->id,
            ]),
        ]);

        AuditLog::query()->create([
            'user_id' => $request->user()?->id,
            'event' => 'platform.module.updated',
            'auditable_type' => PlatformModule::class,
            'auditable_id' => $module->id,
            'ip_address' => $request->ip(),
            'payload' => [
                'key' => $module->key,
                'enabled' => $module->enabled,
            ],
        ]);

        return back()->with('status', "Modulo {$module->label} actualizado.");
    }

    public function updateModuleDetails(Request $request, PlatformModule $module): RedirectResponse
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:160'],
            'target' => ['required', 'string', 'max:160'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'owner' => ['nullable', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:500'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'max:80'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $module->update([
            'label' => $data['label'],
            'target' => $data['target'],
            'enabled' => ($data['status'] ?? ($module->enabled ? 'active' : 'inactive')) === 'active',
            'settings' => array_merge($module->settings ?? [], [
                'owner' => $data['owner'] ?? null,
                'description' => $data['description'] ?? null,
                'permissions' => array_values($data['permissions'] ?? []),
                'note' => $data['note'] ?? null,
                'details_updated_by' => $request->user()?->id,
            ]),
        ]);

        $this->writeAudit($request, 'platform.module.details_updated', PlatformModule::class, $module->id, [
            'key' => $module->key,
            'label' => $module->label,
        ]);

        return back()->with('status', "Detalles de {$module->label} actualizados.");
    }

    public function storeInstitution(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'legal_name' => ['nullable', 'string', 'max:180'],
            'external_id' => ['nullable', 'string', 'max:80', 'unique:institutions,external_id'],
            'type' => ['required', Rule::in(['publica', 'privada', 'institution'])],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'username' => ['nullable', 'string', 'max:80', 'unique:users,username'],
            'password' => ['nullable', 'string', 'min:6', 'max:80'],
        ]);

        $institutionUsername = $data['username'] ?? $this->uniqueUsername($data['name']);

        $owner = User::query()->create([
            'name' => $data['name'],
            'username' => $institutionUsername,
            'email' => $institutionUsername.'@drsam.local',
            'password' => Hash::make($data['password'] ?? 'Temporal2026!'),
            'role' => 'institution',
            'module' => 'institution',
            'status' => $data['status'],
            'email_verified_at' => now(),
        ]);

        $institution = Institution::query()->create([
            'owner_user_id' => $owner->id,
            'name' => $data['name'],
            'legal_name' => $data['legal_name'] ?? $data['name'],
            'external_id' => $data['external_id'] ?? 'inst-'.str($data['name'])->slug('-'),
            'type' => $data['type'],
            'status' => $data['status'],
            'metadata' => [
                'created_from' => 'superadmin_phase_2',
                'created_by' => $request->user()?->id,
            ],
        ]);

        $this->writeAudit($request, 'superadmin.institution.created', Institution::class, $institution->id, [
            'name' => $institution->name,
        ]);

        return back()->with('status', "Institucion {$institution->name} creada.");
    }

    public function updateInstitution(Request $request, Institution $institution): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'legal_name' => ['nullable', 'string', 'max:180'],
            'type' => ['required', Rule::in(['publica', 'privada', 'institution'])],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'username' => ['nullable', 'string', 'max:80', Rule::unique('users', 'username')->ignore($institution->owner_user_id)],
            'password' => ['nullable', 'string', 'min:6', 'max:80'],
        ]);

        $institution->update(collect($data)->only(['name', 'legal_name', 'type', 'status'])->all());

        if ($institution->owner) {
            $ownerData = [
                'name' => $data['name'],
                'username' => $data['username'] ?? $institution->owner->username,
                'status' => $data['status'],
            ];

            if (! empty($data['password'])) {
                $ownerData['password'] = Hash::make($data['password']);
            }

            $institution->owner->update($ownerData);
        }

        $this->writeAudit($request, 'superadmin.institution.updated', Institution::class, $institution->id, [
            'name' => $institution->name,
            'status' => $institution->status,
        ]);

        return back()->with('status', "Institucion {$institution->name} actualizada.");
    }

    public function storeHospital(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'network_type' => ['nullable', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:500'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'rfc' => ['nullable', 'string', 'max:13'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'state' => ['nullable', 'string', 'max:120'],
            'unit_type' => ['nullable', 'string', 'max:160'],
            'scope' => ['nullable', 'string', 'max:160'],
            'source' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'contact_name' => ['nullable', 'string', 'max:160'],
            'contact_email' => ['nullable', 'email', 'max:180'],
            'related_services' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $hospital = Hospital::query()->create([
            'name' => $data['name'],
            'network_type' => $data['network_type'] ?? 'Cadena privada',
            'address' => $data['address'] ?? null,
            'contact_phone' => $data['contact_phone'] ?? null,
            'rfc' => $data['rfc'] ?? null,
            'status' => $data['status'],
            'created_by' => $request->user()?->id,
            'metadata' => [
                'state' => $data['state'] ?? null,
                'unit_type' => $data['unit_type'] ?? 'Centro de medicina ambulatoria',
                'scope' => $data['scope'] ?? 'Cadena privada',
                'source' => $data['source'] ?? 'Alta manual superadmin',
                'city' => $data['city'] ?? null,
                'contact_name' => $data['contact_name'] ?? null,
                'contact_email' => $data['contact_email'] ?? null,
                'related_services' => $data['related_services'] ?? null,
                'notes' => $data['notes'] ?? null,
            ],
        ]);

        $this->writeAudit($request, 'superadmin.hospital.created', Hospital::class, $hospital->id, [
            'name' => $hospital->name,
        ]);

        return back()->with('status', "Hospital {$hospital->name} creado.");
    }

    public function updateHospital(Request $request, Hospital $hospital): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'network_type' => ['nullable', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:500'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'rfc' => ['nullable', 'string', 'max:13'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'state' => ['nullable', 'string', 'max:120'],
            'unit_type' => ['nullable', 'string', 'max:160'],
            'scope' => ['nullable', 'string', 'max:160'],
            'source' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'contact_name' => ['nullable', 'string', 'max:160'],
            'contact_email' => ['nullable', 'email', 'max:180'],
            'related_services' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $metadata = $hospital->metadata ?? [];
        $metadataValue = fn (string $key) => $request->has($key) ? ($data[$key] ?? null) : data_get($metadata, $key);

        $hospital->update([
            'name' => $data['name'],
            'network_type' => $request->has('network_type') ? ($data['network_type'] ?? null) : $hospital->network_type,
            'address' => $request->has('address') ? ($data['address'] ?? null) : $hospital->address,
            'contact_phone' => $request->has('contact_phone') ? ($data['contact_phone'] ?? null) : $hospital->contact_phone,
            'rfc' => $request->has('rfc') ? ($data['rfc'] ?? null) : $hospital->rfc,
            'status' => $data['status'],
            'updated_by' => $request->user()?->id,
            'metadata' => array_merge($metadata, [
                'state' => $metadataValue('state'),
                'unit_type' => $metadataValue('unit_type'),
                'scope' => $metadataValue('scope'),
                'source' => $metadataValue('source'),
                'city' => $metadataValue('city'),
                'contact_name' => $metadataValue('contact_name'),
                'contact_email' => $metadataValue('contact_email'),
                'related_services' => $metadataValue('related_services'),
                'notes' => $metadataValue('notes'),
            ]),
        ]);

        $this->writeAudit($request, 'superadmin.hospital.updated', Hospital::class, $hospital->id, [
            'name' => $hospital->name,
            'status' => $hospital->status,
        ]);

        return back()->with('status', "Hospital {$hospital->name} actualizado.");
    }

    public function storeDoctor(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:180'],
            'username' => ['nullable', 'string', 'max:80', 'unique:users,username'],
            'email' => ['nullable', 'email', 'max:180', 'unique:users,email'],
            'password' => ['nullable', 'string', 'min:6', 'max:80'],
            'professional_license' => ['nullable', 'string', 'max:80'],
            'specialty' => ['nullable', 'string', 'max:120'],
            'subspecialty' => ['nullable', 'string', 'max:120'],
            'service_name' => ['nullable', 'string', 'max:120'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $username = $data['username'] ?? $this->uniqueUsername($data['full_name']);
        $user = User::query()->create([
            'name' => $data['full_name'],
            'username' => $username,
            'email' => $data['email'] ?? $username.'@drsam.local',
            'password' => Hash::make($data['password'] ?? 'Temporal2026!'),
            'role' => 'doctor',
            'module' => 'doctor',
            'status' => $data['status'],
            'email_verified_at' => now(),
            'metadata' => ['created_from' => 'superadmin_doctor_catalog'],
        ]);

        $doctor = Doctor::query()->create([
            'user_id' => $user->id,
            'external_id' => 'MED-'.str_pad((string) $user->id, 4, '0', STR_PAD_LEFT),
            'full_name' => $data['full_name'],
            'professional_license' => $data['professional_license'] ?? null,
            'specialty' => $data['specialty'] ?? null,
            'subspecialty' => $data['subspecialty'] ?? null,
            'service_name' => $data['service_name'] ?? null,
            'status' => $data['status'],
            'verified_at' => now(),
            'metadata' => ['created_by' => $request->user()?->id],
        ]);

        $this->writeAudit($request, 'superadmin.doctor.created', Doctor::class, $doctor->id, ['full_name' => $doctor->full_name]);

        return back()->with('status', "Medico {$doctor->full_name} creado.");
    }

    public function updateDoctor(Request $request, Doctor $doctor): RedirectResponse
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:180'],
            'professional_license' => ['nullable', 'string', 'max:80'],
            'specialty' => ['nullable', 'string', 'max:120'],
            'subspecialty' => ['nullable', 'string', 'max:120'],
            'service_name' => ['nullable', 'string', 'max:120'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $doctor->update($data);
        $doctor->user?->update([
            'name' => $data['full_name'],
            'status' => $data['status'],
        ]);

        $this->writeAudit($request, 'superadmin.doctor.updated', Doctor::class, $doctor->id, ['status' => $doctor->status]);

        return back()->with('status', "Medico {$doctor->full_name} actualizado.");
    }

    public function storePatient(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'first_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:140'],
            'full_name' => ['required', 'string', 'max:180'],
            'username' => ['nullable', 'string', 'max:80', 'unique:users,username'],
            'email' => ['nullable', 'email', 'max:180', 'unique:users,email'],
            'password' => ['nullable', 'string', 'min:6', 'max:80'],
            'platform_number' => ['nullable', 'string', 'max:80', 'unique:patients,platform_number'],
            'curp' => ['nullable', 'string', 'max:18', 'unique:patients,curp'],
            'phone' => ['nullable', 'string', 'max:50'],
            'sex' => ['nullable', 'string', 'max:40'],
            'birth_date' => ['nullable', 'date'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $username = $data['username'] ?? $this->uniqueUsername($data['full_name']);
        $user = User::query()->create([
            'name' => $data['full_name'],
            'username' => $username,
            'email' => $data['email'] ?? $username.'@drsam.local',
            'password' => Hash::make($data['password'] ?? 'Temporal2026!'),
            'role' => 'patient',
            'module' => 'patient',
            'status' => $data['status'],
            'email_verified_at' => now(),
            'metadata' => ['phone' => $data['phone'] ?? null, 'created_from' => 'superadmin_patient_catalog'],
        ]);

        $patient = Patient::query()->create([
            'user_id' => $user->id,
            'platform_number' => $data['platform_number'] ?? str_pad((string) $user->id, 8, '0', STR_PAD_LEFT),
            'first_name' => $data['first_name'] ?? null,
            'last_name' => $data['last_name'] ?? null,
            'full_name' => $data['full_name'],
            'birth_date' => $data['birth_date'] ?? null,
            'sex' => $data['sex'] ?? null,
            'curp' => $data['curp'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? $user->email,
            'status' => $data['status'],
            'email_verified_at' => now(),
            'profile_completed_at' => now(),
            'metadata' => ['created_by' => $request->user()?->id],
        ]);

        $this->writeAudit($request, 'superadmin.patient.created', Patient::class, $patient->id, ['full_name' => $patient->full_name]);

        return back()->with('status', "Paciente {$patient->full_name} creado.");
    }

    public function updatePatient(Request $request, Patient $patient): RedirectResponse
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:180'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:180'],
            'curp' => ['nullable', 'string', 'max:18'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $patient->update($data);
        $patient->user?->update([
            'name' => $data['full_name'],
            'email' => $data['email'] ?? $patient->user?->email,
            'status' => $data['status'],
        ]);

        $this->writeAudit($request, 'superadmin.patient.updated', Patient::class, $patient->id, ['status' => $patient->status]);

        return back()->with('status', "Paciente {$patient->full_name} actualizado.");
    }

    public function storeProvider(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'username' => ['nullable', 'string', 'max:80', 'unique:users,username'],
            'email' => ['nullable', 'email', 'max:180', 'unique:users,email'],
            'password' => ['nullable', 'string', 'min:6', 'max:80'],
            'provider_type' => ['required', Rule::in(['npt', 'chemotherapy', 'import', 'vendor'])],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'last_name' => ['nullable', 'string', 'max:180'],
            'phone' => ['nullable', 'string', 'max:50'],
            'doctor_ids' => ['nullable', 'array'],
            'doctor_ids.*' => ['integer', 'exists:doctors,id'],
        ]);

        $displayName = $data['provider_type'] === 'vendor'
            ? trim($data['name'].' '.($data['last_name'] ?? ''))
            : $data['name'];

        $username = $data['username'] ?? $this->uniqueUsername($displayName);
        $module = match ($data['provider_type']) {
            'chemotherapy' => 'provider_chemo',
            'import' => 'provider_import',
            default => 'provider_npt',
        };

        $user = User::query()->create([
            'name' => $displayName,
            'username' => $username,
            'email' => $data['email'] ?? $username.'@drsam.local',
            'password' => Hash::make($data['password'] ?? 'Temporal2026!'),
            'role' => 'provider',
            'module' => $module,
            'status' => $data['status'],
            'email_verified_at' => now(),
            'metadata' => ['created_from' => 'superadmin_provider_catalog', 'phone' => $data['phone'] ?? null],
        ]);

        $provider = Provider::query()->create([
            'user_id' => $user->id,
            'name' => $displayName,
            'provider_type' => $data['provider_type'],
            'status' => $data['status'],
            'metadata' => [
                'created_by' => $request->user()?->id,
                'first_name' => $data['name'],
                'last_name' => $data['last_name'] ?? null,
                'phone' => $data['phone'] ?? null,
                'doctor_ids' => array_values($data['doctor_ids'] ?? []),
                'related_doctors' => count($data['doctor_ids'] ?? []),
            ],
        ]);

        $this->writeAudit($request, 'superadmin.provider.created', Provider::class, $provider->id, ['name' => $provider->name]);

        return back()->with('status', "Proveedor {$provider->name} creado.");
    }

    public function updateProvider(Request $request, Provider $provider): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'provider_type' => ['required', Rule::in(['npt', 'chemotherapy', 'import', 'vendor'])],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'last_name' => ['nullable', 'string', 'max:180'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:180', Rule::unique('users', 'email')->ignore($provider->user_id)],
            'doctor_ids' => ['nullable', 'array'],
            'doctor_ids.*' => ['integer', 'exists:doctors,id'],
        ]);

        $displayName = $data['provider_type'] === 'vendor'
            ? trim($data['name'].' '.($data['last_name'] ?? ''))
            : $data['name'];
        $metadata = $provider->metadata ?? [];
        $doctorIds = array_values($data['doctor_ids'] ?? data_get($metadata, 'doctor_ids', []));

        $provider->update([
            'name' => $displayName,
            'provider_type' => $data['provider_type'],
            'status' => $data['status'],
            'metadata' => array_merge($metadata, [
                'first_name' => $data['name'],
                'last_name' => $data['last_name'] ?? null,
                'phone' => $data['phone'] ?? null,
                'doctor_ids' => $doctorIds,
                'related_doctors' => count($doctorIds),
            ]),
        ]);

        $provider->user?->update([
            'name' => $displayName,
            'email' => $data['email'] ?? $provider->user?->email,
            'module' => match ($data['provider_type']) {
                'chemotherapy' => 'provider_chemo',
                'import' => 'provider_import',
                default => 'provider_npt',
            },
            'status' => $data['status'],
            'metadata' => array_merge($provider->user?->metadata ?? [], ['phone' => $data['phone'] ?? null]),
        ]);

        $this->writeAudit($request, 'superadmin.provider.updated', Provider::class, $provider->id, ['status' => $provider->status]);

        return back()->with('status', "Proveedor {$provider->name} actualizado.");
    }
    public function storeInsuranceCarrier(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'slug' => ['nullable', 'string', 'max:180', 'unique:insurance_carriers,slug'],
            'type' => ['nullable', 'string', 'max:120'],
            'contact' => ['nullable', 'string', 'max:160'],
            'phone' => ['nullable', 'string', 'max:60'],
            'email' => ['nullable', 'email', 'max:180'],
            'scope' => ['nullable', 'string', 'max:500'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $carrier = InsuranceCarrier::query()->create([
            'name' => $data['name'],
            'slug' => $data['slug'] ?: Str::slug($data['name']),
            'type' => $data['type'] ?? 'GMM',
            'contact' => $data['contact'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'scope' => $data['scope'] ?? null,
            'status' => $data['status'],
            'metadata' => ['created_by' => $request->user()?->id],
        ]);

        $this->writeAudit($request, 'superadmin.insurance_carrier.created', InsuranceCarrier::class, $carrier->id, ['name' => $carrier->name]);

        return back()->with('status', "Aseguradora {$carrier->name} creada.");
    }

    public function updateInsuranceCarrier(Request $request, InsuranceCarrier $carrier): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'slug' => ['nullable', 'string', 'max:180', Rule::unique('insurance_carriers', 'slug')->ignore($carrier->id)],
            'type' => ['nullable', 'string', 'max:120'],
            'contact' => ['nullable', 'string', 'max:160'],
            'phone' => ['nullable', 'string', 'max:60'],
            'email' => ['nullable', 'email', 'max:180'],
            'scope' => ['nullable', 'string', 'max:500'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $carrier->update([
            'name' => $data['name'],
            'slug' => $data['slug'] ?: $carrier->slug ?: Str::slug($data['name']),
            'type' => $data['type'] ?? null,
            'contact' => $data['contact'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'scope' => $data['scope'] ?? null,
            'status' => $data['status'],
            'metadata' => array_merge($carrier->metadata ?? [], ['updated_by' => $request->user()?->id]),
        ]);

        $this->writeAudit($request, 'superadmin.insurance_carrier.updated', InsuranceCarrier::class, $carrier->id, ['status' => $carrier->status]);

        return back()->with('status', "Aseguradora {$carrier->name} actualizada.");
    }

    public function storeInsuranceAdvisor(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'username' => ['nullable', 'string', 'max:80', 'unique:users,username'],
            'email' => ['nullable', 'email', 'max:180', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'max:120'],
            'insurance_carrier' => ['nullable', 'string', 'max:180'],
            'agent_number' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:60'],
            'scope' => ['nullable', 'string', 'max:500'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $user = User::query()->create([
            'name' => $data['name'],
            'username' => $data['username'] ?: Str::slug($data['name']).'-'.Str::lower(Str::random(5)),
            'email' => $data['email'] ?? null,
            'password' => Hash::make($data['password']),
            'role' => UserRole::InsuranceAdvisor->value,
            'module' => 'insurance_advisor',
            'status' => $data['status'],
            'metadata' => [
                'insurance_carrier' => $data['insurance_carrier'] ?? null,
                'agent_number' => $data['agent_number'] ?? null,
                'phone' => $data['phone'] ?? null,
                'scope' => $data['scope'] ?? 'Seguimiento de polizas y pacientes',
            ],
        ]);

        $this->writeAudit($request, 'superadmin.insurance_advisor.created', User::class, $user->id, ['status' => $user->status]);

        return back()->with('status', "Asesor {$user->name} creado.");
    }

    public function updateInsuranceAdvisor(Request $request, User $advisor): RedirectResponse
    {
        abort_unless($advisor->role === UserRole::InsuranceAdvisor->value, 404);

        $data = $request->validate([
            'status' => ['required', Rule::in(['active', 'inactive', 'suspended'])],
            'name' => ['required', 'string', 'max:180'],
            'username' => ['nullable', 'string', 'max:80', Rule::unique('users', 'username')->ignore($advisor->id)],
            'email' => ['nullable', 'email', 'max:180', Rule::unique('users', 'email')->ignore($advisor->id)],
            'password' => ['nullable', 'string', 'min:6', 'max:120'],
            'insurance_carrier' => ['nullable', 'string', 'max:180'],
            'agent_number' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:60'],
            'scope' => ['nullable', 'string', 'max:500'],
        ]);

        $payload = [
            'name' => $data['name'],
            'username' => $data['username'] ?: $advisor->username,
            'email' => $data['email'] ?: null,
            'role' => UserRole::InsuranceAdvisor->value,
            'module' => 'insurance_advisor',
            'status' => $data['status'],
            'metadata' => array_merge($advisor->metadata ?? [], [
                'insurance_carrier' => $data['insurance_carrier'] ?? null,
                'agent_number' => $data['agent_number'] ?? null,
                'phone' => $data['phone'] ?? null,
                'scope' => $data['scope'] ?? 'Seguimiento de polizas y pacientes',
            ]),
        ];

        if (! empty($data['password'])) {
            $payload['password'] = Hash::make($data['password']);
        }

        $advisor->update($payload);

        $this->writeAudit($request, 'superadmin.insurance_advisor.updated', User::class, $advisor->id, ['status' => $advisor->status]);

        return back()->with('status', "Asesor {$advisor->name} actualizado.");
    }
    public function updateSectionSettings(Request $request, string $section): RedirectResponse
    {
        abort_unless(in_array($section, ['advertising', 'prescription-format', 'subscriptions'], true), 404);

        if ($section === 'subscriptions') {
            return $this->updateSubscriptionSettings($request);
        }

        $data = $section === 'advertising'
            ? $request->validate([
                'module' => ['required', 'string', 'max:80'],
                'primary_title' => ['required', 'string', 'max:160'],
                'primary_copy' => ['nullable', 'string', 'max:500'],
                'primary_cta' => ['nullable', 'string', 'max:80'],
                'secondary_title' => ['nullable', 'string', 'max:160'],
                'secondary_cta' => ['nullable', 'string', 'max:80'],
            ])
            : $request->validate([
                'folio_prefix' => ['required', 'string', 'max:20'],
                'default_service' => ['nullable', 'string', 'max:120'],
                'diagnosis_required' => ['nullable', 'boolean'],
                'medication_required' => ['nullable', 'boolean'],
                'footer_note' => ['nullable', 'string', 'max:500'],
            ]);

        $moduleKey = $section === 'advertising' ? 'advertising' : 'doctor';
        $module = PlatformModule::query()->firstOrCreate(
            ['key' => $moduleKey],
            [
                'label' => $section === 'advertising' ? 'Modulo Publicidad' : 'Modulo Medico',
                'target' => $section === 'advertising' ? 'superadmin.catalog' : 'doctor.dashboard',
                'enabled' => true,
                'roles' => ['superadmin'],
                'settings' => [],
            ],
        );

        $settingsKey = $section === 'advertising' ? 'advertising_config' : 'prescription_format';
        $module->update([
            'settings' => array_merge($module->settings ?? [], [
                $settingsKey => $data,
                $settingsKey.'_updated_by' => $request->user()?->id,
                $settingsKey.'_updated_at' => now()->toISOString(),
            ]),
        ]);

        $this->writeAudit($request, 'superadmin.'.$section.'.settings_updated', PlatformModule::class, $module->id, [
            'section' => $section,
        ]);

        return back()->with('status', 'Configuracion actualizada.');
    }

    public function updateUser(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['active', 'inactive', 'suspended'])],
            'name' => ['nullable', 'string', 'max:180'],
            'email' => ['nullable', 'email', 'max:180', Rule::unique('users', 'email')->ignore($user->id)],
            'username' => ['nullable', 'string', 'max:80', Rule::unique('users', 'username')->ignore($user->id)],
            'email_validation' => ['nullable', Rule::in(['validated', 'pending'])],
            'phone' => ['nullable', 'string', 'max:50'],
            'curp' => ['nullable', 'string', 'max:18'],
            'professional_license' => ['nullable', 'string', 'max:80'],
            'specialty' => ['nullable', 'string', 'max:120'],
        ]);

        $user->update([
            'status' => $data['status'],
            'name' => $data['name'] ?? $user->name,
            'email' => array_key_exists('email', $data) ? ($data['email'] ?: null) : $user->email,
            'username' => $data['username'] ?? $user->username,
            'email_verified_at' => ($data['email_validation'] ?? null) === 'validated'
                ? ($user->email_verified_at ?? now())
                : (($data['email_validation'] ?? null) === 'pending' ? null : $user->email_verified_at),
            'metadata' => array_merge($user->metadata ?? [], ['phone' => $data['phone'] ?? data_get($user->metadata, 'phone')]),
        ]);

        if ($user->patient) {
            $user->patient->update([
                'full_name' => $user->name,
                'email' => $user->email,
                'phone' => $data['phone'] ?? $user->patient->phone,
                'curp' => $data['curp'] ?? $user->patient->curp,
                'status' => $user->status,
                'email_verified_at' => $user->email_verified_at,
            ]);
        }

        if ($user->doctor) {
            $user->doctor->update([
                'full_name' => $user->name,
                'professional_license' => $data['professional_license'] ?? $user->doctor->professional_license,
                'specialty' => $data['specialty'] ?? $user->doctor->specialty,
                'status' => $user->status,
                'verified_at' => $user->email_verified_at,
            ]);
        }

        AuditLog::query()->create([
            'user_id' => $request->user()?->id,
            'event' => 'platform.user.updated',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'ip_address' => $request->ip(),
            'payload' => [
                'username' => $user->username,
                'status' => $user->status,
            ],
        ]);

        return back()->with('status', "Usuario {$user->username} actualizado.");
    }

    private function writeAudit(Request $request, string $event, string $type, int $id, array $payload = []): void
    {
        AuditLog::query()->create([
            'user_id' => $request->user()?->id,
            'event' => $event,
            'auditable_type' => $type,
            'auditable_id' => $id,
            'ip_address' => $request->ip(),
            'payload' => $payload,
        ]);
    }

    private function uniqueUsername(string $name): string
    {
        $base = str($name)->ascii()->lower()->replaceMatches('/[^a-z0-9]+/', '.')->trim('.')->limit(45, '')->toString() ?: 'usuario';
        $candidate = $base;
        $counter = 1;

        while (User::query()->where('username', $candidate)->exists()) {
            $candidate = $base.'.'.$counter;
            $counter++;
        }

        return $candidate;
    }

    private function syncConfiguredModules(): void
    {
        foreach (config('drsam.modules', []) as $key => $module) {
            PlatformModule::query()->firstOrCreate(
                ['key' => $key],
                [
                    'label' => $module['label'],
                    'target' => $module['target'],
                    'enabled' => true,
                    'roles' => $module['roles'] ?? [],
                    'settings' => ['runtime' => 'native'],
                ],
            );
        }
    }

    private function catalogDefinition(string $section): ?array
    {
        $activeModuleCount = PlatformModule::query()->where('enabled', true)->count();
        $totalModuleCount = PlatformModule::query()->count();
        $moduleActivityDescription = $activeModuleCount.' de '.$totalModuleCount.' modulos activos';

        return [
            'users' => [
                'title' => 'Catalogo de Usuarios de la plataforma',
                'eyebrow' => 'Modulo superadministrador',
                'description' => User::query()->count().' usuarios visibles en la plataforma',
                'search_placeholder' => 'Buscar usuario, correo, tipo, cedula o CURP',
                'columns' => ['No.', 'No. usuario de la plataforma', 'Tipo de usuario', 'Nombre', 'Correo electronico', 'Usuario', 'Validacion correo', 'Fecha validacion', 'Telefono', 'CURP', 'Cedula profesional', 'Especialidad', 'Estatus', 'Fuente / perfil', 'Acciones'],
                'query' => fn () => User::query()->with(['patient', 'doctor'])->latest(),
                'search' => ['name', 'username', 'email', 'role', 'module'],
                'status_filter' => true,
                'map' => fn (User $record) => [
                    $record->id,
                    $record->patient?->platform_number ?? $record->doctor?->external_id ?? (string) (100000000 + $record->id),
                    UserRole::tryFrom($record->role)?->label() ?? ucfirst($record->role),
                    $record->name,
                    $record->email ?? 'Sin correo',
                    $record->username,
                    $record->email_verified_at ? 'Validado' : 'Pendiente',
                    $record->email_verified_at?->format('d/m/Y') ?? 'Sin validar',
                    $record->patient?->phone ?? data_get($record->metadata, 'phone', 'Sin telefono'),
                    $record->patient?->curp ?? 'Sin CURP',
                    $record->doctor?->professional_license ?? 'No aplica',
                    $record->doctor?->specialty ?? 'No aplica',
                    $record->status,
                    $record->doctor ? 'Catalogo medico, Acceso medico' : ($record->patient ? 'Registro plataforma, Registro paciente completo, Acceso paciente' : 'Registro plataforma, Acceso '.strtolower(UserRole::tryFrom($record->role)?->label() ?? $record->role)),
                    'Cambiar estatus',
                ],
            ],
            'institutions' => [
                'title' => 'Catalogo de instituciones',
                'eyebrow' => 'Modulo superadministrador',
                'description' => 'Las instituciones pueden ser publicas o privadas. Cada institucion tiene a su cargo unidades que llevan la gestion administrativa.',
                'search_placeholder' => 'Buscar institucion, usuario o alcance',
                'columns' => ['Institucion', 'Tipo', 'Estatus', 'Unidades', 'Servicios', 'Usuarios', 'Modulos', 'Acciones'],
                'query' => fn () => Institution::query()->with('owner')->withCount(['medicalUnits', 'services'])->latest(),
                'search' => ['name', 'legal_name', 'type', 'external_id'],
                'status_filter' => true,
                'primary_action' => ['label' => 'Alta de institucion', 'route' => 'institution.dashboard'],
                'open_route' => 'institution.dashboard',
                'map' => fn (Institution $record) => [$record->name, ucfirst($record->type ?? 'Publica'), $record->status, $record->medical_units_count ?? 0, $record->services_count ?? 0, $record->owner ? 1 : 0, PlatformModule::query()->where('enabled', true)->count().' activos', 'Ver / Editar'],
            ],
            'doctors' => [
                'title' => 'Catalogo de medicos',
                'eyebrow' => 'Modulo superadministrador',
                'description' => 'El catalogo de medicos contiene usuarios medicos con registro y validacion.',
                'search_placeholder' => 'Buscar medico, cedula, especialidad o unidad',
                'columns' => ['Medico', 'Cedula', 'No. usuario plataforma', 'Usuario', 'Contrasena', 'Especialidad', 'Subespecialidad', 'Servicio', 'Unidad', 'Estatus', 'Acciones'],
                'query' => fn () => Doctor::query()->with(['user', 'medicalUnit'])->latest(),
                'search' => ['full_name', 'professional_license', 'specialty', 'service_name', 'external_id'],
                'status_filter' => true,
                'open_route' => 'doctor.dashboard',
                'map' => fn (Doctor $record) => [$record->full_name, $record->professional_license ?? 'Sin cedula', $record->external_id ?? 'MED-0001', $record->user?->username ?? 'Sin usuario', 'Protegida', $record->specialty ?? 'Sin especialidad', $record->subspecialty ?? 'Atencion clinica', $record->service_name ?? 'General', $record->medicalUnit?->name ?? data_get($record->metadata, 'unit', 'Sin unidad'), $record->status === 'active' ? 'Autorizado' : $record->status, 'Abrir'],
            ],
            'providers' => [
                'title' => 'Catalogo de Vendedores',
                'eyebrow' => 'Modulo superadministrador',
                'description' => 'Proveedores y vendedores disponibles para servicios y medicamentos.',
                'search_placeholder' => 'Buscar vendedor, medico, cedula o especialidad',
                'columns' => ['Vendedor', 'Medicos relacionados', 'Nombre', 'Apellidos', 'Cedula profesional', 'Especialidad', 'Hospital, clinica o particular', 'No. usuario plataforma', 'Estatus', 'Acciones'],
                'query' => fn () => Provider::query()->with('user')->where('provider_type', 'vendor')->latest(),
                'search' => ['name', 'status'],
                'status_filter' => true,
                'primary_action' => ['label' => 'Alta de vendedor', 'route' => 'superadmin.catalog'],
                'map' => fn (Provider $record) => [$record->name, data_get($record->metadata, 'related_doctors', 0), data_get($record->metadata, 'first_name', $record->name), data_get($record->metadata, 'last_name', ''), data_get($record->metadata, 'professional_license', 'Sin cedula'), data_get($record->metadata, 'specialty', 'Sin especialidad'), data_get($record->metadata, 'institution', 'Particular'), $record->user_id ? 100000000 + $record->user_id : 'Sin usuario', $record->status, 'Editar'],
            ],
            'insurance-carriers' => [
                'title' => 'Catalogo de Aseguradoras',
                'eyebrow' => 'Modulo superadministrador',
                'description' => 'Catalogo base de aseguradoras para futuras relaciones con pacientes, polizas, hospitales y servicios de la plataforma.',
                'search_placeholder' => 'Buscar aseguradora, tipo, contacto o alcance',
                'columns' => ['Aseguradora', 'Tipo', 'Contacto', 'Telefono', 'Correo', 'Alcance', 'Estatus', 'Acciones'],
                'query' => fn () => InsuranceCarrier::query()->orderBy('name'),
                'search' => ['name', 'slug', 'type', 'contact', 'phone', 'email', 'scope'],
                'status_filter' => true,
                'map' => fn (InsuranceCarrier $record) => [
                    $record->name,
                    $record->type,
                    $record->contact ?? 'Sin contacto',
                    $record->phone ?? 'Sin telefono',
                    $record->email ?? 'Sin correo',
                    $record->scope ?? 'Sin alcance',
                    $record->status,
                    'Editar',
                ],
            ],
            'insurance-advisors' => [
                'title' => 'Catalogo de Asesores de Seguros',
                'eyebrow' => 'Modulo superadministrador',
                'description' => 'Catalogo base de asesores de seguros para relacionar aseguradoras, pacientes, polizas y seguimiento operativo.',
                'search_placeholder' => 'Buscar asesor, aseguradora, telefono o correo',
                'columns' => ['Asesor', 'Aseguradora', 'No. agente', 'Telefono', 'Correo', 'Alcance', 'Estatus', 'Acciones'],
                'query' => fn () => User::query()->where('role', 'insurance_advisor')->orderBy('name'),
                'search' => ['name', 'username', 'email'],
                'metadata_search' => ['insurance_carrier', 'agent_number', 'phone', 'scope'],
                'status_filter' => true,
                'primary_action' => ['label' => 'Alta de asesor', 'route' => 'superadmin.catalog'],
                'map' => fn (User $record) => [
                    $record->name,
                    data_get($record->metadata, 'insurance_carrier', 'Sin aseguradora'),
                    data_get($record->metadata, 'agent_number', 'Sin numero'),
                    data_get($record->metadata, 'phone', 'Sin telefono'),
                    $record->email ?? 'Sin correo',
                    data_get($record->metadata, 'scope', 'Seguimiento de polizas y pacientes'),
                    $record->status,
                    'Editar',
                ],
            ],
            'hospitals' => [
                'title' => 'Listado de Hospitales Privados',
                'eyebrow' => 'Modulo superadministrador',
                'description' => 'Este listado alimenta el formato de alta de nuevo consultorio para consulta privada.',
                'search_placeholder' => 'Buscar hospital, grupo, ciudad o servicio',
                'columns' => ['Hospital', 'Grupo / Institucion', 'Estado', 'Tipo de unidad', 'Alcance', 'Fuente / Nota', 'Estatus', 'Acciones'],
                'query' => fn () => Hospital::query()->latest(),
                'search' => ['name', 'network_type', 'address', 'rfc'],
                'status_filter' => true,
                'primary_action' => ['label' => 'Agregar hospital', 'route' => 'superadmin.catalog'],
                'map' => fn (Hospital $record) => [$record->name, $record->network_type ?? 'Cadena privada', data_get($record->metadata, 'state', 'Sin estado'), data_get($record->metadata, 'unit_type', 'Centro de medicina ambulatoria'), data_get($record->metadata, 'scope', 'Cadena privada'), data_get($record->metadata, 'source', 'Sin nota'), $record->status, 'Editar / Inactivar'],
            ],
            'specialties' => [
                'title' => 'Catalogo de especialidades',
                'eyebrow' => 'Modulo superadministrador',
                'description' => $moduleActivityDescription,
                'search_placeholder' => 'Buscar especialidad de la plataforma',
                'columns' => ['No.', 'Especialidad', 'Estatus'],
                'query' => fn () => Service::query()->latest(),
                'search' => ['name', 'category', 'specialty', 'code'],
                'status_filter' => true,
                'map' => fn (Service $record) => [$record->id, $record->specialty ?? $record->name, $record->status],
            ],
            'medications' => [
                'title' => 'Catalogo Universal de Medicamentos',
                'eyebrow' => 'Modulo superadministrador',
                'description' => $moduleActivityDescription,
                'search_placeholder' => 'Buscar clave, medicamento, grupo o institucion',
                'columns' => ['Clave CNIS', 'Grupo', 'Medicamento', 'Descripcion', 'Unidades moviles', 'Nucleos basicos', 'CESSA', 'Catalogo institucional de', 'Estatus'],
                'query' => fn () => MedicationCatalogItem::query()->orderBy('cnis'),
                'search' => ['name', 'generic_name', 'presentation', 'cnis'],
                'status_filter' => true,
                'map' => fn (MedicationCatalogItem $record) => [$record->cnis ?? 'Sin CNIS', data_get($record->metadata, 'group', $record->therapeutic_group ?? 'Grupo no clasificado'), $record->name.' '.($record->external_id ?? ''), $record->description ?? $record->presentation ?? $record->generic_name ?? 'Sin descripcion', data_get($record->metadata, 'mobile_units', 'No'), data_get($record->metadata, 'basic_units', 'Si'), data_get($record->metadata, 'cessa', 'Si'), data_get($record->metadata, 'catalog', 'Imss Bienestar'), $record->status],
            ],
            'medical-devices' => [
                'title' => 'Catalogo de dispositivos medicos',
                'eyebrow' => 'Modulo superadministrador',
                'description' => 'Catalogo base de dispositivos medicos que se pueden vincular con la aplicacion para registrar mediciones, seguimiento y datos clinicos.',
                'search_placeholder' => 'Buscar dispositivo, fabricante, conectividad o modulo',
                'columns' => ['Dispositivo', 'Categoria', 'Fabricante / Proveedor', 'Modelo', 'Conectividad', 'Modulo vinculado', 'Dato que registra', 'Compatibilidad', 'Estatus'],
                'query' => fn () => MedicalDevice::query()->orderBy('name'),
                'search' => ['name', 'code', 'category', 'manufacturer', 'model', 'connectivity', 'linked_module', 'recorded_data', 'compatibility'],
                'map' => fn (MedicalDevice $record) => [
                    $record->name,
                    $record->category,
                    $record->manufacturer,
                    $record->model ?? 'Sin modelo',
                    $record->connectivity ?? 'Sin conectividad',
                    $record->linked_module ?? 'Sin modulo',
                    $record->recorded_data ?? 'Sin dato configurado',
                    $record->compatibility ?? 'Sin compatibilidad',
                    $record->status === 'evaluation' ? 'En evaluacion' : $record->status,
                ],
            ],
            'patients' => [
                'title' => 'Catalogo de pacientes',
                'eyebrow' => 'Modulo superadministrador',
                'description' => '11 de 11 modulos activos',
                'search_placeholder' => 'Buscar paciente, expediente, diagnostico o unidad',
                'columns' => ['Paciente usuario', 'No. usuario plataforma', 'Usuario', 'Contrasena', 'Estatus', 'Acciones'],
                'query' => fn () => Patient::query()->with('user')->latest(),
                'search' => ['full_name', 'record_number', 'curp', 'phone', 'email'],
                'status_filter' => true,
                'open_route' => 'patient.dashboard',
                'map' => fn (Patient $record) => [$record->full_name, $record->platform_number ?? str_pad((string) $record->id, 8, '0', STR_PAD_LEFT), $record->user?->username ?? 'Sin usuario', 'Asignada', $record->status, 'Abrir'],
            ],
            'subscriptions' => [
                'title' => 'Suscripciones',
                'eyebrow' => 'Modulo superadministrador',
                'description' => 'Administra los planes de suscripcion de la plataforma, sus precios, alcance y beneficios para usuarios pacientes.',
                'columns' => ['Plan'],
                'query' => fn () => collect($this->subscriptionConfiguration()['plans'])->map(fn (array $plan) => (object) $plan),
                'map' => fn (object $record) => [$record->name],
            ],
            'prescription-format' => [
                'title' => 'Formato de receta medica',
                'eyebrow' => 'Modulo superadministrador',
                'description' => 'Plantillas y configuracion documental para recetas medicas.',
                'columns' => ['Formato', 'Modulo', 'Version', 'Estatus', 'Actualizado'],
                'query' => fn () => collect([(object) ['name' => 'Receta medica estandar', 'module' => 'doctor', 'version' => '1.0', 'status' => 'active', 'updated_at' => now()]]),
                'map' => fn (object $record) => [$record->name, $record->module, $record->version, $record->status, $record->updated_at?->format('d/m/Y')],
            ],
            'mix-request-format' => [
                'title' => 'Formato de solicitud de mezcla',
                'eyebrow' => 'Modulo superadministrador',
                'description' => 'Formatos base de solicitud de mezcla para Modulo medico y Modulo de area operativa, replicados del proveedor integral.',
                'columns' => ['Formato'],
                'query' => fn () => collect([(object) ['name' => 'Mezcla oncologica'], (object) ['name' => 'Nutricion parenteral']]),
                'map' => fn (object $record) => [$record->name],
            ],
            'advertising' => [
                'title' => 'Publicidad',
                'eyebrow' => 'Modulo superadministrador',
                'description' => 'Espacios de publicidad, avisos y banners operativos.',
                'columns' => ['Elemento', 'Ubicacion', 'Inicio', 'Estatus', 'Actualizado'],
                'query' => fn () => collect([(object) ['name' => 'Aviso principal', 'placement' => 'dashboard', 'starts_at' => now(), 'status' => 'inactive', 'updated_at' => now()]]),
                'map' => fn (object $record) => [$record->name, $record->placement, $record->starts_at?->format('d/m/Y'), $record->status, $record->updated_at?->format('d/m/Y')],
            ],
            'modules' => [
                'title' => 'Modulos',
                'eyebrow' => 'Modulo superadministrador',
                'description' => 'Modulos de plataforma, destino, roles y disponibilidad.',
                'search_placeholder' => 'Buscar modulo, responsable o alcance',
                'columns' => ['Modulo', 'Responsable', 'Estatus', 'Usuarios / Perfiles', 'Alcance', 'Acciones'],
                'query' => fn () => PlatformModule::query()->orderBy('label'),
                'search' => ['label', 'key', 'target'],
                'status_filter' => true,
                'map' => fn (PlatformModule $record) => [$record->label, data_get($record->settings, 'owner', 'Direccion Administrativa'), $record->enabled ? 'Activo' : 'Inactivo', count($record->roles ?? []), $record->target, 'Editar / Abrir'],
            ],
            'access' => [
                'title' => 'Modulo de acceso',
                'eyebrow' => 'Modulo superadministrador',
                'description' => $moduleActivityDescription,
                'search_placeholder' => 'Buscar usuario, modulo o unidad',
                'columns' => ['Usuario', 'Modulo', 'Perfil', 'Alcance', 'Destino'],
                'query' => fn () => User::query()
                    ->orderByDesc('is_demo')
                    ->orderByRaw("case role when 'superadmin' then 0 when 'admin' then 1 when 'institution' then 2 when 'unit' then 3 when 'operational' then 4 when 'doctor' then 5 when 'patient' then 6 else 7 end")
                    ->orderBy('name'),
                'search' => ['name', 'username', 'role', 'module'],
                'module_filter' => true,
                'map' => fn (User $record) => [
                    $record->name,
                    $this->accessModuleLabel($record),
                    $this->accessProfileLabel($record),
                    $this->accessScope($record),
                    $record->status === 'active' ? 'Abrir' : 'Inactivo',
                ],
            ],
        ][$section] ?? null;
    }

    private function updateSubscriptionSettings(Request $request): RedirectResponse
    {
        $module = PlatformModule::query()->firstOrCreate(
            ['key' => 'superadmin'],
            [
                'label' => 'Modulo superadministrador',
                'target' => 'superadmin.dashboard',
                'enabled' => true,
                'roles' => ['superadmin'],
                'settings' => [],
            ],
        );
        $configuration = $this->subscriptionConfiguration();
        $mode = $request->string('mode')->toString();

        if ($mode === 'plan') {
            $data = $request->validate([
                'plan_id' => ['required', Rule::in(array_column($configuration['plans'], 'id'))],
                'type' => ['required', 'string', 'max:80'],
                'name' => ['required', 'string', 'max:160'],
                'price' => ['required', 'string', 'max:80'],
                'users_included' => ['required', 'string', 'max:80'],
                'short_description' => ['required', 'string', 'max:500'],
                'long_description' => ['required', 'string', 'max:3000'],
                'status' => ['required', Rule::in(['active', 'inactive'])],
            ]);

            $configuration['plans'] = collect($configuration['plans'])->map(function (array $plan) use ($data): array {
                return $plan['id'] === $data['plan_id']
                    ? array_merge($plan, collect($data)->except('plan_id')->all())
                    : $plan;
            })->values()->all();
        } elseif ($mode === 'legal') {
            $data = $request->validate([
                'document' => ['required', Rule::in(['usage_policies', 'terms_conditions'])],
                'content' => ['required', 'string', 'max:12000'],
            ]);
            $configuration[$data['document']] = $data['content'];
        } else {
            abort(422, 'Edicion de suscripcion no reconocida.');
        }

        $module->update([
            'settings' => array_merge($module->settings ?? [], [
                'subscriptions_config' => $configuration,
                'subscriptions_config_updated_by' => $request->user()?->id,
                'subscriptions_config_updated_at' => now()->toISOString(),
            ]),
        ]);
        $this->writeAudit($request, 'superadmin.subscriptions.settings_updated', PlatformModule::class, $module->id, ['mode' => $mode]);

        return back()->with('status', 'Suscripciones actualizadas.');
    }

    private function subscriptionConfiguration(): array
    {
        $defaults = [
            'plans' => [
                ['id' => 'basic', 'type' => 'Gratis', 'name' => 'Plan Historial Basico', 'price' => 'Gratis', 'users_included' => '1 usuario', 'short_description' => 'Para empezar a guardar y consultar informacion medica reciente.', 'long_description' => 'Permite al usuario consultar su historial medico de hasta 1 ano atras. Tambien puede subir analisis clinicos a la plataforma para que queden registrados dentro de su historial clinico digital. Es ideal para pacientes que quieren empezar a organizar su informacion medica sin costo.', 'status' => 'active'],
                ['id' => 'complete', 'type' => 'Suscripcion 1', 'name' => 'Plan Historial Completo', 'price' => '$20 MXN mensuales', 'users_included' => '1 usuario', 'short_description' => 'Para tener acceso permanente a toda la historia clinica digital.', 'long_description' => 'Incluye acceso al historial clinico de toda la vida dentro de la cuenta. El paciente puede consultar consultas medicas, diagnosticos, tratamientos, estudios y analisis clinicos cargados desde el inicio del uso de la plataforma. Es util para usuarios que desean tener toda su informacion medica centralizada.', 'status' => 'active'],
                ['id' => 'smart', 'type' => 'Suscripcion 2', 'name' => 'Plan Salud Inteligente', 'price' => '$190 MXN mensuales', 'users_included' => '1 usuario', 'short_description' => 'Para recibir apoyo en la organizacion y seguimiento de la salud.', 'long_description' => 'Incluye todo lo del Plan Historial Completo, mas integracion con inteligencia artificial para ayudar al paciente a administrar mejor su salud. La IA puede apoyar con recordatorios, organizacion de estudios, seguimiento de sintomas, preparacion de preguntas para el medico y explicacion general de resultados medicos en lenguaje sencillo.', 'status' => 'active'],
                ['id' => 'family', 'type' => 'Suscripcion 3', 'name' => 'Plan Cuidado Familiar', 'price' => '$799 MXN', 'users_included' => 'Hasta 5 usuarios', 'short_description' => 'Para administrar la salud de familiares desde una misma plataforma. Hasta 5 usuarios.', 'long_description' => 'Pensado para usuarios que desean administrar la salud de varios miembros de su familia desde una sola cuenta. Permite crear perfiles familiares, organizar historiales medicos por persona, subir analisis clinicos, guardar vacunas, medicamentos, alergias y antecedentes importantes. Ideal para padres, cuidadores o familiares responsables de adultos mayores.', 'status' => 'active'],
                ['id' => 'premium', 'type' => 'Suscripcion 4', 'name' => 'Plan Salud Premium 360', 'price' => '$950 MXN', 'users_included' => '1 usuario', 'short_description' => 'Para usuarios que buscan una experiencia medica digital mas completa y personalizada.', 'long_description' => 'Es el plan mas completo. Incluye historial clinico completo, inteligencia artificial, administracion familiar y funciones avanzadas como reportes personalizados de salud, alertas preventivas, seguimiento de medicamentos, integracion con citas medicas, exportacion de expedientes y soporte prioritario. Esta dirigido a pacientes que quieren una gestion integral y continua de su salud.', 'status' => 'active'],
            ],
            'usage_policies' => "Propuesta de Politicas de uso\n\n1. Uso responsable de la plataforma: los usuarios deberan utilizar la plataforma para fines relacionados con la administracion de su informacion de salud, consulta de historial clinico, seguimiento de servicios medicos y uso de herramientas digitales disponibles.\n2. Informacion del usuario: el usuario es responsable de proporcionar informacion verdadera, actualizada y completa.\n3. Informacion medica: la informacion presentada tiene fines de organizacion, consulta y apoyo digital. No sustituye la valoracion profesional.\n4. Seguridad de acceso: cada usuario debera resguardar sus credenciales.\n5. Uso de herramientas con IA: sus respuestas no deberan interpretarse como diagnostico medico definitivo.\n6. Suscripciones y beneficios: el acceso dependera del plan contratado.\n7. Conductas no permitidas: queda prohibido el uso fraudulento o acceso no autorizado.\n8. Actualizacion de politicas: la plataforma podra actualizar estas politicas.",
            'terms_conditions' => "Propuesta de Terminos y condiciones\n\n1. Aceptacion: al registrarse y usar la plataforma, el usuario acepta estos terminos.\n2. Objeto de la plataforma: administrar informacion medica digital y servicios relacionados.\n3. Registro y cuenta: el usuario debera proporcionar informacion veraz.\n4. Planes de suscripcion: precios, beneficios y condiciones podran ser modificados.\n5. Pagos y cancelaciones: se sujetaran a las reglas comerciales vigentes.\n6. Alcance medico: la plataforma no sustituye consulta medica ni urgencias.\n7. Informacion y documentos: se procesaran conforme a los avisos de privacidad.\n8. Limitacion de responsabilidad: las herramientas digitales requieren validacion profesional.\n9. Modificaciones: los terminos podran actualizarse.\n10. Jurisdiccion: se aplicara la legislacion correspondiente.",
        ];

        $stored = PlatformModule::query()->where('key', 'superadmin')->first()?->settings['subscriptions_config'] ?? [];

        return array_replace($defaults, is_array($stored) ? $stored : []);
    }

    private function subscriptionUsers(): Collection
    {
        $plans = collect($this->subscriptionConfiguration()['plans'])->keyBy('id');

        return User::query()->with('patient')->where('role', 'patient')->orderBy('name')->get()->map(function (User $user) use ($plans): array {
            $patient = $user->patient;
            $fullName = trim($patient?->full_name ?? $user->name);
            $parts = preg_split('/\s+/', $fullName) ?: [];
            $firstName = array_shift($parts) ?? $fullName;
            $lastName = implode(' ', $parts);
            $planId = (string) (data_get($patient?->metadata, 'subscription_plan_id') ?? data_get($user->metadata, 'subscription_plan_id') ?? 'basic');
            $plan = $plans->get($planId) ?? $plans->get('basic');
            $birthDate = $patient?->birth_date;

            return [
                'plan_id' => $plan['id'] ?? 'basic',
                'plan_name' => $plan['name'] ?? 'Plan Historial Basico',
                'plan_price' => $plan['price'] ?? 'Gratis',
                'first_name' => data_get($patient?->metadata, 'first_name', $firstName),
                'last_name' => data_get($patient?->metadata, 'last_name', $lastName ?: 'No capturados'),
                'country' => data_get($patient?->metadata, 'country', 'Mexico'),
                'city' => data_get($patient?->metadata, 'city', 'No capturada'),
                'platform_number' => $patient?->platform_number ?? (string) (100000000 + $user->id),
                'age' => $birthDate ? $birthDate->age : 'No capturada',
                'curp' => $patient?->curp ?: 'Sin CURP',
                'email' => $patient?->email ?: ($user->email ?: 'Sin correo'),
                'phone' => $patient?->phone ?: 'Sin telefono',
                'user_type' => 'Paciente',
                'status' => $user->status,
            ];
        });
    }

    private function accessModuleLabel(User $user): string
    {
        return data_get(config('drsam.modules'), $user->module.'.label', ucfirst(str_replace('_', ' ', (string) $user->module)));
    }

    private function accessProfileLabel(User $user): string
    {
        return [
            'superadmin' => 'Superadministrador',
            'admin' => 'Administrador',
            'institution' => 'Institucion',
            'unit' => 'Unidad',
            'operational' => 'Operativo',
            'doctor' => 'Medico',
            'patient' => 'Paciente',
            'provider' => 'Proveedor',
            'messenger' => 'Mensajero',
            'insurance_advisor' => 'Asesor',
            'insurance_admin' => 'Aseguradora',
        ][$user->role] ?? ucfirst(str_replace('_', ' ', (string) $user->role));
    }

    private function accessScope(User $user): string
    {
        if ($user->role === 'superadmin') {
            return 'Gobierno de modulos';
        }

        if ($user->role === 'admin') {
            return 'Alta de unidades y servicios';
        }

        if ($user->role === 'institution') {
            return 'Activo';
        }

        $sourceId = data_get($user->metadata, 'external_id');
        if ($sourceId) {
            return (string) $sourceId.($user->status !== 'active' ? ' - Inactivo' : '');
        }

        return $user->status === 'active' ? 'Activo' : 'Inactivo';
    }

    private function applyCatalogFilters(mixed $query, array $catalog, Request $request): void
    {
        if (! method_exists($query, 'where')) {
            return;
        }

        $search = trim($request->string('q')->toString());
        if ($search !== '' && ! empty($catalog['search'])) {
            $query->where(function ($subQuery) use ($catalog, $search): void {
                foreach ($catalog['search'] as $column) {
                    $subQuery->orWhere($column, 'like', '%'.$search.'%');
                }

                foreach ($catalog['metadata_search'] ?? [] as $key) {
                    $subQuery->orWhere('metadata->'.$key, 'like', '%'.$search.'%');
                }
            });
        }

        $status = $request->string('status')->toString();
        if ($status !== '' && $status !== 'all' && ! empty($catalog['status_filter'])) {
            if (isset($catalog['title']) && $catalog['title'] === 'Modulos') {
                $query->where('enabled', $status === 'active');
            } else {
                $query->where('status', $status);
            }
        }

        $module = $request->string('module')->toString();
        if ($module !== '' && $module !== 'all' && ! empty($catalog['module_filter'])) {
            $query->where('module', $module);
        }
    }
}

