<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\ContractedService;
use App\Models\Doctor;
use App\Models\Institution;
use App\Models\InventoryItem;
use App\Models\MedicalUnit;
use App\Models\OperationalArea;
use App\Models\OperationalProfile;
use App\Models\Patient;
use App\Models\PharmacyProduct;
use App\Models\Provider;
use App\Models\ProviderRequest;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnitModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_unit_user_can_open_native_dashboard(): void
    {
        $user = User::query()->create([
            'name' => 'Unidad Demo',
            'username' => 'unidad.test',
            'email' => 'unidad@test.local',
            'role' => 'unit',
            'module' => 'unit',
            'status' => 'active',
        ]);

        $institution = Institution::query()->create([
            'name' => 'Institucion Unidad',
            'status' => 'active',
        ]);

        $unit = MedicalUnit::query()->create([
            'institution_id' => $institution->id,
            'name' => 'Hospital Unidad Test',
            'code' => 'HUT',
            'unit_username' => $user->username,
            'type' => 'Hospital General',
            'care_level' => 'Segundo Nivel',
            'beds' => 50,
            'status' => 'active',
        ]);

        $doctor = Doctor::query()->create([
            'medical_unit_id' => $unit->id,
            'full_name' => 'Dra Unidad',
            'specialty' => 'Medicina interna',
            'status' => 'active',
        ]);

        $patient = Patient::query()->create([
            'full_name' => 'Paciente Unidad',
            'status' => 'active',
        ]);

        Appointment::query()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'medical_unit_id' => $unit->id,
            'specialty' => 'Medicina interna',
            'status' => 'scheduled',
            'starts_at' => now()->addDay(),
        ]);

        $service = Service::query()->create([
            'name' => 'Quimioterapia',
            'specialty' => 'Oncologia',
            'status' => 'active',
        ]);

        $contract = ContractedService::query()->create([
            'institution_id' => $institution->id,
            'medical_unit_id' => $unit->id,
            'service_id' => $service->id,
            'contract_number' => 'UNIT-001',
            'status' => 'active',
        ]);

        $area = OperationalArea::query()->create([
            'key' => 'farmacia-unit',
            'label' => 'Farmacia',
        ]);

        OperationalProfile::query()->create([
            'user_id' => $user->id,
            'medical_unit_id' => $unit->id,
            'operational_area_id' => $area->id,
            'status' => 'active',
        ]);

        $product = PharmacyProduct::query()->create([
            'name' => 'Medicamento Unidad',
            'price' => 100,
            'status' => 'active',
        ]);

        InventoryItem::query()->create([
            'pharmacy_product_id' => $product->id,
            'medical_unit_id' => $unit->id,
            'quantity' => 10,
            'status' => 'available',
        ]);

        $provider = Provider::query()->create([
            'name' => 'Proveedor Unidad',
            'provider_type' => 'npt',
            'status' => 'active',
        ]);

        ProviderRequest::query()->create([
            'provider_id' => $provider->id,
            'patient_id' => $patient->id,
            'medical_unit_id' => $unit->id,
            'external_id' => 'REQ-UNIT-001',
            'request_type' => 'chemo',
            'status' => 'requested',
            'requested_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('unit.dashboard'))
            ->assertOk()
            ->assertSee('unit-native-screen')
            ->assertSee('unit-native-sidebar')
            ->assertSee('unit-native-table')
            ->assertSee('Hospital Unidad Test')
            ->assertSee('Servicios Habilitados por la Institucion')
            ->assertSee('Quimioterapia')
            ->assertSee('Ver Operacion')
            ->assertSee(route('operational.dashboard', ['area' => 'oncology', 'section' => 'history', 'unit' => $unit->id, 'service' => $service->id]))
            ->assertSee('Reporte de unidad del servicio de nutricion parenteral')
            ->assertSee('Ver catalogo de productos / servicios')
            ->assertSee('Descargar')
            ->assertSee('Excel')
            ->assertSee('Catalogo de productos/Servicios')
            ->assertSee('Elementos habilitados')
            ->assertSee('data-open-service-catalog', false)
            ->assertDontSee('<iframe');

        $reportResponse = $this->actingAs($user)
            ->get(route('unit.services.report', $contract))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('Folio', $reportResponse->streamedContent());
        $this->assertStringContainsString('REQ-UNIT-001', $reportResponse->streamedContent());

        $this->actingAs($user)
            ->get(route('operational.dashboard', ['area' => 'oncology', 'section' => 'history', 'unit' => $unit->id, 'service' => $service->id]))
            ->assertOk()
            ->assertSee('Modulo Area Operativa - Centro Oncologico')
            ->assertSee('Hospital Unidad Test - HUT');

        $this->actingAs($user)
            ->get(route('unit.dashboard', ['section' => 'profile']))
            ->assertOk()
            ->assertSee('Perfil de la unidad')
            ->assertSee('Vista previa')
            ->assertSee('Nombre pÃºblico de la unidad')
            ->assertSee('Texto para redes sociales')
            ->assertSee('Nota para papelerÃ­a')
            ->assertSee('Guardar perfil');

        $this->actingAs($user)
            ->patch(route('unit.profile.update'), [
                'public_name' => 'ClÃ­nica Unidad Test',
                'general_info' => 'InformaciÃ³n pÃºblica de la unidad.',
                'services' => 'Quimioterapia',
                'news' => 'Nueva Ã¡rea habilitada.',
                'social_text' => 'Texto para redes.',
                'stationery_note' => 'PapelerÃ­a oficial.',
            ])
            ->assertRedirect(route('unit.dashboard', ['section' => 'profile']));

        $this->assertSame('ClÃ­nica Unidad Test', $unit->fresh()->metadata['profile']['public_name']);

        $this->actingAs($user)
            ->get(route('unit.dashboard', ['section' => 'users']))
            ->assertOk()
            ->assertSee('Alta de usuario operativo')
            ->assertSee('Editar usuario operativo')
            ->assertSee('Usuarios de la unidad')
            ->assertSee('Guardar cambios')
            ->assertSee('data-edit-operational-user', false)
            ->assertSee('Excel')
            ->assertSee('Farmacia');

        $this->actingAs($user)
            ->post(route('unit.operational-users.store'), [
                'name' => 'Operador Unidad', 'username' => 'operador.unidad', 'password' => 'Demo2026',
                'role_label' => 'Responsable de Area', 'authority' => 'Direccion Administrativa',
                'service' => 'Quimioterapia', 'operational_area_id' => $area->id,
                'permissions' => ['history', 'detail', 'reports'],
            ])
            ->assertRedirect(route('unit.dashboard', ['section' => 'users']));
        $this->assertDatabaseHas('users', ['username' => 'operador.unidad', 'role' => 'operational']);
        $this->assertDatabaseHas('operational_profiles', ['medical_unit_id' => $unit->id, 'operational_area_id' => $area->id, 'role_label' => 'Responsable de Area']);

        $this->actingAs($user)
            ->get(route('unit.dashboard', ['section' => 'patients']))
            ->assertOk()
            ->assertSee('Catalogo de pacientes')
            ->assertSee('Paciente Unidad');

        $this->actingAs($user)
            ->get(route('unit.dashboard', ['section' => 'doctors']))
            ->assertOk()
            ->assertSee('Catalogo de medicos adscritos')
            ->assertSee('Dra Unidad')
            ->assertSee('Alta de medico adscrito')
            ->assertSee('Editar autorizaciones')
            ->assertSee('Usuario de la plataforma')
            ->assertSee('Guardar medico')
            ->assertSee('Excel');

        $this->actingAs($user)
            ->post(route('unit.doctors.store'), [
                'first_name' => 'Mario', 'last_name' => 'Adscripto', 'platform_user' => 'med-900',
                'professional_license' => 'CED-900', 'specialty' => 'Oncologia',
                'subspecialty' => 'Atencion clinica', 'services' => ['Quimioterapia'],
            ])
            ->assertRedirect(route('unit.dashboard', ['section' => 'doctors']));
        $this->assertDatabaseHas('doctors', ['medical_unit_id' => $unit->id, 'full_name' => 'Mario Adscripto', 'professional_license' => 'CED-900', 'status' => 'pending']);
        $createdDoctor = Doctor::query()->where('professional_license', 'CED-900')->firstOrFail();
        $this->actingAs($user)
            ->put(route('unit.doctors.authorizations.update', $createdDoctor), ['status' => 'active', 'services' => ['Quimioterapia']])
            ->assertRedirect(route('unit.dashboard', ['section' => 'doctors']));
        $this->assertSame('active', $createdDoctor->fresh()->status);
        $this->assertSame(['Quimioterapia'], $createdDoctor->fresh()->metadata['services']);

        $this->actingAs($user)
            ->get(route('unit.dashboard', ['section' => 'specialties']))
            ->assertOk()
            ->assertSee('Catalogo de especialidades')
            ->assertSee('Oncologia');

        $this->actingAs($user)
            ->get(route('unit.dashboard', ['section' => 'external-pharmacy']))
            ->assertOk()
            ->assertSee('Catalogo de farmacia externa')
            ->assertSee('Sin medicamentos institucionales para esta unidad.');

        $this->actingAs($user)
            ->get(route('unit.dashboard', ['section' => 'procedure-areas']))
            ->assertOk()
            ->assertSee('Catalogo de areas de procedimiento')
            ->assertSee('Catalogo de consultorios')
            ->assertSee('Catalogo de salas de infusion')
            ->assertSee('Catalogo de quirofanos')
            ->assertSee('Catalogo de salas de recuperacion')
            ->assertSee('Nuevo consultorio');
        $this->actingAs($user)
            ->get(route('unit.dashboard', ['section' => 'procedure-areas', 'create' => 'consulting']))
            ->assertOk()->assertSee('Nueva subunidad')->assertSee('Guardar subunidad')->assertSee('Horario de atencion de la unidad');
        $this->actingAs($user)
            ->post(route('unit.procedure-areas.store'), [
                'type' => 'consulting', 'location' => 'Consulta externa', 'floor' => 'PB',
                'unit_number' => 'C-01', 'capacity' => 1, 'responsible' => '',
                'schedule' => ['monday' => ['enabled' => 1, 'start' => '08:00', 'end' => '16:00']],
            ])->assertRedirect(route('unit.dashboard', ['section' => 'procedure-areas']));
        $this->assertSame('C-01', data_get($unit->fresh()->metadata, 'procedure_areas.0.unit_number'));
        $procedureAreaId = data_get($unit->fresh()->metadata, 'procedure_areas.0.id');
        $this->actingAs($user)
            ->get(route('unit.dashboard', ['section' => 'procedure-areas', 'edit' => $procedureAreaId]))
            ->assertOk()->assertSee('Editar consultorio')->assertSee('Guardar cambios')->assertSee('C-01');
        $this->actingAs($user)
            ->put(route('unit.procedure-areas.update', $procedureAreaId), [
                'type' => 'consulting', 'location' => 'Centro oncologico', 'floor' => '1',
                'unit_number' => 'C-01', 'capacity' => 6, 'responsible' => '',
                'schedule' => ['monday' => ['enabled' => 1, 'start' => '07:00', 'end' => '15:00']],
            ])->assertRedirect(route('unit.dashboard', ['section' => 'procedure-areas']));
        $this->assertSame(6, data_get($unit->fresh()->metadata, 'procedure_areas.0.capacity'));
        $this->assertDatabaseHas('procedure_areas', [
            'medical_unit_id' => $unit->id,
            'external_id' => $procedureAreaId,
            'unit_number' => 'C-01',
            'simultaneous_capacity' => 6,
        ]);
        $this->assertDatabaseHas('procedure_area_schedules', [
            'day_of_week' => 1,
            'starts_at' => '07:00',
            'ends_at' => '15:00',
        ]);
    }

    public function test_unit_user_can_update_appointment_status(): void
    {
        $user = User::query()->create([
            'name' => 'Unidad Status',
            'username' => 'unidad.status',
            'email' => 'unidad.status@test.local',
            'role' => 'unit',
            'module' => 'unit',
            'status' => 'active',
        ]);

        $unit = MedicalUnit::query()->create([
            'name' => 'Unidad Status',
            'unit_username' => $user->username,
            'status' => 'active',
        ]);

        $appointment = Appointment::query()->create([
            'medical_unit_id' => $unit->id,
            'status' => 'scheduled',
            'starts_at' => now()->addDay(),
        ]);

        $this->actingAs($user)
            ->patch(route('unit.appointments.status', $appointment), [
                'status' => 'confirmed',
                'notes' => 'Confirmada por unidad',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'confirmed',
        ]);
        $this->assertDatabaseHas('appointment_status_events', [
            'appointment_id' => $appointment->id,
            'from_status' => 'scheduled',
            'to_status' => 'confirmed',
            'notes' => 'Confirmada por unidad',
        ]);

        $this->assertSame(
            'Confirmada por unidad',
            $appointment->fresh()->metadata['last_status_note'] ?? null,
        );
    }
}
