<?php

namespace App\Http\Controllers\Unit;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AppointmentStatusEvent;
use App\Models\ContractedService;
use App\Models\Doctor;
use App\Models\InventoryItem;
use App\Models\MedicationCatalogItem;
use App\Models\MedicalUnit;
use App\Models\OperationalArea;
use App\Models\OperationalProfile;
use App\Models\Patient;
use App\Models\ProviderRequest;
use App\Models\ProcedureArea;
use App\Models\Service;
use App\Models\User;
use App\Services\Platform\PlatformAuditService;
use App\Services\Platform\DomainStateTransitionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UnitDashboardController extends Controller
{
    public function downloadServiceReport(Request $request, ContractedService $contract): StreamedResponse
    {
        $unit = $this->resolveUnit($request);
        abort_unless((int) $contract->medical_unit_id === (int) $unit->id, 403);

        $contract->loadMissing('service');
        $serviceText = str(($contract->service?->name ?? '').' '.($contract->service?->category ?? '').' '.($contract->service?->specialty ?? ''))->lower();
        $requestTypes = $serviceText->contains('oncolo') || $serviceText->contains('quimio') ? ['chemo', 'chemotherapy'] : ['npt'];
        $requests = ProviderRequest::query()
            ->with(['patient', 'provider'])
            ->where('medical_unit_id', $unit->id)
            ->whereIn('request_type', $requestTypes)
            ->orderByDesc('requested_at')
            ->get();

        $filename = 'reporte-'.str($contract->service?->name ?? 'servicio')->slug().'-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($requests): void {
            $output = fopen('php://output', 'wb');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['Folio', 'Fecha', 'Paciente', 'Proveedor', 'Tipo', 'Estatus', 'Fecha requerida']);
            foreach ($requests as $providerRequest) {
                fputcsv($output, [
                    $providerRequest->external_id,
                    optional($providerRequest->requested_at)->format('d/m/Y H:i'),
                    $providerRequest->patient?->full_name,
                    $providerRequest->provider?->name,
                    $providerRequest->request_type,
                    $providerRequest->status,
                    optional($providerRequest->required_at)->format('d/m/Y H:i'),
                ]);
            }
            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function index(Request $request): View
    {
        $unit = $this->resolveUnit($request);
        $section = $request->string('section')->toString() ?: 'services';
        if (! in_array($section, ['profile', 'services', 'users', 'patients', 'doctors', 'specialties', 'external-pharmacy', 'procedure-areas'], true)) {
            $section = 'services';
        }

        $unit->load([
            'institution',
            'contractedServices.service',
            'operationalProfiles.user',
            'operationalProfiles.area',
            'doctors.user',
        ]);

        $appointments = Appointment::query()
            ->with(['patient', 'doctor'])
            ->where('medical_unit_id', $unit->id)
            ->latest('starts_at')
            ->limit(10)
            ->get();

        $providerRequests = ProviderRequest::query()
            ->with(['provider', 'patient'])
            ->where('medical_unit_id', $unit->id)
            ->latest('requested_at')
            ->limit(10)
            ->get();

        $inventory = InventoryItem::query()
            ->with('product')
            ->where('medical_unit_id', $unit->id)
            ->latest()
            ->limit(10)
            ->get();

        $patients = Patient::query()
            ->whereHas('appointments', fn ($query) => $query->where('medical_unit_id', $unit->id))
            ->with(['appointments' => fn ($query) => $query->where('medical_unit_id', $unit->id)->latest('starts_at')])
            ->orderBy('full_name')
            ->get();

        $specialties = Service::query()
            ->orderBy('specialty')
            ->orderBy('name')
            ->get();

        $externalPharmacyCatalog = MedicationCatalogItem::query()
            ->when($unit->institution_id, fn ($query) => $query->where(function ($subQuery) use ($unit): void {
                $subQuery->where('institution_id', $unit->institution_id)
                    ->orWhereNull('institution_id');
            }))
            ->orderBy('name')
            ->get();

        return view('unit.dashboard', [
            'unit' => $unit,
            'section' => $section,
            'appointments' => $appointments,
            'providerRequests' => $providerRequests,
            'inventory' => $inventory,
            'patients' => $patients,
            'specialties' => $specialties,
            'externalPharmacyCatalog' => $externalPharmacyCatalog,
            'operationalAreas' => OperationalArea::query()->orderBy('label')->get(),
            'metrics' => [
                'Camas' => $unit->beds,
                'Medicos' => $unit->doctors->count(),
                'Areas operativas' => $unit->operationalProfiles->count(),
                'Servicios activos' => $unit->contractedServices->where('status', 'active')->count(),
                'Citas programadas' => Appointment::query()
                    ->where('medical_unit_id', $unit->id)
                    ->where('status', 'scheduled')
                    ->count(),
                'Solicitudes abiertas' => ProviderRequest::query()
                    ->where('medical_unit_id', $unit->id)
                    ->whereNotIn('status', ['delivered', 'cancelled', 'rejected'])
                    ->count(),
            ],
        ]);
    }

    public function updateAppointmentStatus(Request $request, Appointment $appointment, PlatformAuditService $audit, DomainStateTransitionService $transitions): RedirectResponse
    {
        $this->authorizeAppointmentOwnership($request, $appointment);

        $data = $request->validate([
            'status' => ['required', Rule::in(['scheduled', 'confirmed', 'in_progress', 'completed', 'cancelled', 'no_show'])],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $previousStatus = $appointment->status;
        $transitions->assertAllowed('appointment', $previousStatus, $data['status']);

        $metadata = $appointment->metadata ?? [];
        $metadata['last_status_note'] = $data['notes'] ?? null;
        $metadata['last_status_changed_by'] = $request->user()?->id;
        $metadata['last_status_changed_at'] = now()->toISOString();

        DB::transaction(function () use ($appointment, $data, $metadata, $previousStatus, $request): void {
            $appointment->update([
                'status' => $data['status'],
                'metadata' => $metadata,
            ]);
            AppointmentStatusEvent::query()->create([
                'appointment_id' => $appointment->id,
                'changed_by' => $request->user()?->id,
                'from_status' => $previousStatus,
                'to_status' => $data['status'],
                'notes' => $data['notes'] ?? null,
                'metadata' => ['source' => 'unit'],
            ]);
        });

        $audit->record($request, 'unit.appointment.status_updated', $appointment, 'unit', [
            'status' => $appointment->status,
        ]);

        return back()->with('status', 'Cita actualizada desde unidad.');
    }

    public function updateProfile(Request $request, PlatformAuditService $audit): RedirectResponse
    {
        $unit = $this->resolveUnit($request);
        $data = $request->validate([
            'public_name' => ['required', 'string', 'max:255'],
            'general_info' => ['nullable', 'string', 'max:3000'],
            'services' => ['nullable', 'string', 'max:3000'],
            'news' => ['nullable', 'string', 'max:3000'],
            'social_text' => ['nullable', 'string', 'max:3000'],
            'stationery_note' => ['nullable', 'string', 'max:3000'],
            'profile_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'remove_image' => ['nullable', 'boolean'],
        ]);

        $metadata = $unit->metadata ?? [];
        $profile = $metadata['profile'] ?? [];

        if ($request->boolean('remove_image') && filled($profile['image_path'] ?? null)) {
            Storage::disk('public')->delete($profile['image_path']);
            unset($profile['image_path']);
        }

        if ($request->hasFile('profile_image')) {
            if (filled($profile['image_path'] ?? null)) {
                Storage::disk('public')->delete($profile['image_path']);
            }
            $profile['image_path'] = $request->file('profile_image')->store('unit-profiles', 'public');
        }

        foreach (['public_name', 'general_info', 'services', 'news', 'social_text', 'stationery_note'] as $field) {
            $profile[$field] = $data[$field] ?? '';
        }
        $metadata['profile'] = $profile;
        $unit->update(['metadata' => $metadata]);

        $audit->record($request, 'unit.profile.updated', $unit, 'unit', ['public_name' => $profile['public_name']]);

        return redirect()->route('unit.dashboard', ['section' => 'profile'])->with('status', 'Perfil de la unidad actualizado.');
    }

    public function storeOperationalUser(Request $request, PlatformAuditService $audit): RedirectResponse
    {
        $unit = $this->resolveUnit($request);
        $data = $this->validateOperationalUser($request);
        $user = User::query()->create(['name' => $data['name'], 'username' => $data['username'], 'email' => filter_var($data['username'], FILTER_VALIDATE_EMAIL) ? $data['username'] : null, 'password' => Hash::make($data['password']), 'role' => 'operational', 'module' => 'operational', 'status' => 'active']);
        $profile = OperationalProfile::query()->create(['user_id' => $user->id, 'medical_unit_id' => $unit->id, 'operational_area_id' => $data['operational_area_id'], 'role_label' => $data['role_label'], 'permissions' => $data['permissions'] ?? [], 'status' => 'active', 'metadata' => ['authority' => $data['authority'], 'service' => $data['service'], 'demo_password' => $data['password']]]);
        $audit->record($request, 'unit.operational_user.created', $profile, 'unit', ['unit_id' => $unit->id]);
        return redirect()->route('unit.dashboard', ['section' => 'users'])->with('status', 'Usuario operativo creado.');
    }

    public function updateOperationalUser(Request $request, OperationalProfile $profile, PlatformAuditService $audit): RedirectResponse
    {
        $unit = $this->resolveUnit($request);
        abort_unless((int) $profile->medical_unit_id === (int) $unit->id, 403);
        $data = $this->validateOperationalUser($request, $profile);
        $profile->user->update(array_filter(['name' => $data['name'], 'username' => $data['username'], 'email' => filter_var($data['username'], FILTER_VALIDATE_EMAIL) ? $data['username'] : null, 'password' => filled($data['password'] ?? null) ? Hash::make($data['password']) : null], fn ($value) => $value !== null));
        $metadata = $profile->metadata ?? [];
        $metadata['authority'] = $data['authority']; $metadata['service'] = $data['service'];
        if (filled($data['password'] ?? null)) $metadata['demo_password'] = $data['password'];
        $profile->update(['operational_area_id' => $data['operational_area_id'], 'role_label' => $data['role_label'], 'permissions' => $data['permissions'] ?? [], 'metadata' => $metadata]);
        $audit->record($request, 'unit.operational_user.updated', $profile, 'unit');
        return redirect()->route('unit.dashboard', ['section' => 'users'])->with('status', 'Usuario operativo actualizado.');
    }

    public function destroyOperationalUser(Request $request, OperationalProfile $profile, PlatformAuditService $audit): RedirectResponse
    {
        $unit = $this->resolveUnit($request);
        abort_unless((int) $profile->medical_unit_id === (int) $unit->id, 403);
        $audit->record($request, 'unit.operational_user.deleted', $profile, 'unit');
        $profile->delete();
        return redirect()->route('unit.dashboard', ['section' => 'users'])->with('status', 'Perfil operativo eliminado.');
    }

    public function storeDoctor(Request $request, PlatformAuditService $audit): RedirectResponse
    {
        $unit = $this->resolveUnit($request);
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:180'],
            'platform_user' => ['nullable', 'string', 'max:180'],
            'professional_license' => ['required', 'string', 'max:80', Rule::unique('doctors', 'professional_license')],
            'specialty' => ['required', 'string', 'max:180'],
            'subspecialty' => ['nullable', 'string', 'max:180'],
            'services' => ['required', 'array', 'min:1'],
            'services.*' => ['string', 'max:180'],
        ]);
        $doctor = Doctor::query()->create([
            'medical_unit_id' => $unit->id,
            'full_name' => trim($data['first_name'].' '.$data['last_name']),
            'professional_license' => $data['professional_license'],
            'specialty' => $data['specialty'],
            'subspecialty' => $data['subspecialty'] ?? null,
            'service_name' => $data['services'][0],
            'status' => 'pending',
            'metadata' => ['platform_user' => $data['platform_user'] ?? null, 'services' => $data['services']],
        ]);
        $audit->record($request, 'unit.doctor.created', $doctor, 'unit', ['unit_id' => $unit->id]);
        return redirect()->route('unit.dashboard', ['section' => 'doctors'])->with('status', 'Medico adscrito registrado.');
    }

    public function updateDoctorAuthorizations(Request $request, Doctor $doctor, PlatformAuditService $audit): RedirectResponse
    {
        $unit = $this->resolveUnit($request);
        abort_unless((int) $doctor->medical_unit_id === (int) $unit->id, 403);
        $data = $request->validate([
            'status' => ['required', Rule::in(['active', 'pending', 'inactive'])],
            'services' => ['required', 'array', 'min:1'],
            'services.*' => ['string', 'max:180'],
        ]);
        $metadata = $doctor->metadata ?? [];
        $metadata['services'] = array_values(array_unique($data['services']));
        $doctor->update(['status' => $data['status'], 'service_name' => $metadata['services'][0], 'metadata' => $metadata]);
        $audit->record($request, 'unit.doctor.authorizations.updated', $doctor, 'unit', ['services' => $metadata['services']]);
        return redirect()->route('unit.dashboard', ['section' => 'doctors'])->with('status', 'Autorizaciones del medico actualizadas.');
    }

    public function destroyDoctor(Request $request, Doctor $doctor, PlatformAuditService $audit): RedirectResponse
    {
        $unit = $this->resolveUnit($request);
        abort_unless((int) $doctor->medical_unit_id === (int) $unit->id, 403);
        $audit->record($request, 'unit.doctor.deleted', $doctor, 'unit');
        $doctor->delete();
        return redirect()->route('unit.dashboard', ['section' => 'doctors'])->with('status', 'Medico eliminado del catalogo local.');
    }

    public function updateExternalPharmacyStatus(Request $request, MedicationCatalogItem $medication, PlatformAuditService $audit): RedirectResponse
    {
        $unit = $this->resolveUnit($request);
        abort_unless($medication->institution_id === null || (int) $medication->institution_id === (int) $unit->institution_id, 403);
        $data = $request->validate(['status' => ['required', Rule::in(['active', 'inactive'])]]);
        $medication->update(['status' => $data['status']]);
        $audit->record($request, 'unit.external_pharmacy.status.updated', $medication, 'unit', ['unit_id' => $unit->id]);
        return redirect()->route('unit.dashboard', ['section' => 'external-pharmacy'])->with('status', 'Estatus del medicamento actualizado.');
    }

    public function storeProcedureArea(Request $request, PlatformAuditService $audit): RedirectResponse
    {
        $unit = $this->resolveUnit($request);
        $data = $request->validate([
            'type' => ['required', Rule::in(['consulting', 'infusion', 'operating', 'recovery'])],
            'location' => ['required', 'string', 'max:180'],
            'floor' => ['required', 'string', 'max:40'],
            'unit_number' => ['required', 'string', 'max:80'],
            'capacity' => ['required', 'integer', 'min:1', 'max:999'],
            'responsible' => ['nullable', 'string', 'max:180'],
            'schedule' => ['nullable', 'array'],
            'schedule.*.enabled' => ['nullable', 'boolean'],
            'schedule.*.start' => ['nullable', 'date_format:H:i'],
            'schedule.*.end' => ['nullable', 'date_format:H:i'],
        ]);
        $metadata = $unit->metadata ?? [];
        $areas = $metadata['procedure_areas'] ?? [];
        $areaId = (string) str()->uuid();
        $areas[] = [
            'id' => $areaId, 'type' => $data['type'], 'location' => $data['location'],
            'floor' => $data['floor'], 'unit_number' => $data['unit_number'], 'capacity' => $data['capacity'],
            'responsible' => $data['responsible'] ?: 'Sin responsable', 'schedule' => $data['schedule'] ?? [],
        ];
        $metadata['procedure_areas'] = $areas;
        DB::transaction(function () use ($unit, $metadata, $data, $areaId): void {
            $unit->update(['metadata' => $metadata]);
            $this->persistProcedureArea($unit, $areaId, $data);
        });
        $audit->record($request, 'unit.procedure_area.created', $unit, 'unit', ['type' => $data['type'], 'unit_number' => $data['unit_number']]);
        return redirect()->route('unit.dashboard', ['section' => 'procedure-areas'])->with('status', 'Subunidad registrada.');
    }

    public function updateProcedureArea(Request $request, string $areaId, PlatformAuditService $audit): RedirectResponse
    {
        $unit = $this->resolveUnit($request);
        $data = $request->validate([
            'type' => ['required', Rule::in(['consulting', 'infusion', 'operating', 'recovery'])],
            'location' => ['required', 'string', 'max:180'], 'floor' => ['required', 'string', 'max:40'],
            'unit_number' => ['required', 'string', 'max:80'], 'capacity' => ['required', 'integer', 'min:1', 'max:999'],
            'responsible' => ['nullable', 'string', 'max:180'], 'schedule' => ['nullable', 'array'],
            'schedule.*.enabled' => ['nullable', 'boolean'], 'schedule.*.start' => ['nullable', 'date_format:H:i'],
            'schedule.*.end' => ['nullable', 'date_format:H:i'],
        ]);
        $metadata = $unit->metadata ?? [];
        $areas = $metadata['procedure_areas'] ?? [];
        $index = collect($areas)->search(fn (array $area): bool => ($area['id'] ?? null) === $areaId);
        abort_if($index === false, 404);
        $areas[$index] = array_merge($areas[$index], [
            'type' => $data['type'], 'location' => $data['location'], 'floor' => $data['floor'],
            'unit_number' => $data['unit_number'], 'capacity' => $data['capacity'],
            'responsible' => $data['responsible'] ?: 'Sin responsable', 'schedule' => $data['schedule'] ?? [],
        ]);
        $metadata['procedure_areas'] = array_values($areas);
        DB::transaction(function () use ($unit, $metadata, $data, $areaId): void {
            $unit->update(['metadata' => $metadata]);
            $this->persistProcedureArea($unit, $areaId, $data);
        });
        $audit->record($request, 'unit.procedure_area.updated', $unit, 'unit', ['area_id' => $areaId]);
        return redirect()->route('unit.dashboard', ['section' => 'procedure-areas'])->with('status', 'Subunidad actualizada.');
    }

    private function persistProcedureArea(MedicalUnit $unit, string $sourceId, array $data): void
    {
        $area = ProcedureArea::query()->updateOrCreate(
            ['medical_unit_id' => $unit->id, 'external_id' => $sourceId],
            [
                'type' => $data['type'],
                'location' => $data['location'],
                'floor' => $data['floor'],
                'unit_number' => $data['unit_number'],
                'simultaneous_capacity' => $data['capacity'],
                'responsible_name' => $data['responsible'] ?: 'Sin responsable',
                'status' => 'active',
            ],
        );

        $days = ['sunday' => 0, 'monday' => 1, 'tuesday' => 2, 'wednesday' => 3, 'thursday' => 4, 'friday' => 5, 'saturday' => 6];
        $area->schedules()->delete();

        foreach ($data['schedule'] ?? [] as $day => $schedule) {
            if (! ($schedule['enabled'] ?? false) || ! isset($days[$day], $schedule['start'], $schedule['end'])) {
                continue;
            }

            $area->schedules()->create([
                'day_of_week' => $days[$day],
                'starts_at' => $schedule['start'],
                'ends_at' => $schedule['end'],
                'active' => true,
            ]);
        }
    }

    private function validateOperationalUser(Request $request, ?OperationalProfile $profile = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', Rule::unique('users', 'username')->ignore($profile?->user_id)],
            'password' => [$profile ? 'nullable' : 'required', 'string', 'min:6', 'max:255'],
            'role_label' => ['required', Rule::in(['Responsable de Area', 'Operador'])],
            'authority' => ['required', 'string', 'max:150'],
            'service' => ['required', 'string', 'max:180'],
            'operational_area_id' => ['required', 'integer', Rule::exists('operational_areas', 'id')],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in(['history', 'detail', 'manage', 'reports'])],
        ]);
    }

    private function authorizeAppointmentOwnership(Request $request, Appointment $appointment): void
    {
        $user = $request->user();

        if (in_array($user?->role, ['superadmin', 'admin'], true)) {
            return;
        }

        $unit = $this->resolveUnit($request);

        abort_unless(
            (int) $appointment->medical_unit_id === (int) $unit->id,
            403,
            'No puedes modificar citas de otra unidad.',
        );
    }

    private function resolveUnit(Request $request): MedicalUnit
    {
        $user = $request->user();

        if ($user?->role === 'unit') {
            return MedicalUnit::query()
                ->where('unit_username', $user->username)
                ->firstOrFail();
        }

        if ($user?->role === 'institution' && $user->institution) {
            return $user->institution
                ->medicalUnits()
                ->orderBy('name')
                ->firstOrFail();
        }

        abort_unless(in_array($user?->role, ['superadmin', 'admin'], true), 403);

        return MedicalUnit::query()
            ->with('institution')
            ->orderBy('name')
            ->firstOrFail();
    }
}
