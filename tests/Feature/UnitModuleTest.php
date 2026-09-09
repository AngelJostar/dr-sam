<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\ContractedService;
use App\Models\Doctor;
use App\Models\Institution;
use App\Models\InventoryItem;
use App\Models\MedicationCatalogItem;
use App\Models\MedicalUnit;
use App\Models\OperationalArea;
use App\Models\OperationalProfile;
use App\Models\Patient;
use App\Models\PharmacyProduct;
use App\Models\Prescription;
use App\Models\Provider;
use App\Models\ProviderRequest;
use App\Models\ProcedureArea;
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

        $consultingRoom = ProcedureArea::query()->create([
            'medical_unit_id' => $unit->id,
            'type' => 'consulting',
            'location' => 'Primer piso',
            'floor' => '1',
            'unit_number' => 'C-09',
            'simultaneous_capacity' => 1,
            'status' => 'active',
            'metadata' => ['name' => 'Consultorio 9', 'specialty' => 'Medicina interna'],
        ]);

        Appointment::query()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'medical_unit_id' => $unit->id,
            'procedure_area_id' => $consultingRoom->id,
            'specialty' => 'Medicina interna',
            'modality' => 'Presencial',
            'status' => 'scheduled',
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addMinutes(30),
            'reason' => 'Consulta de control',
        ]);

        $service = Service::query()->create([
            'name' => 'Quimioterapia',
            'specialty' => 'Oncologia',
            'status' => 'active',
        ]);

        $consultationService = Service::query()->create([
            'external_id' => 'consulta-externa',
            'name' => 'Consulta externa',
            'category' => 'Atencion medica',
            'specialty' => 'Consulta Externa',
            'status' => 'active',
        ]);

        $nutritionService = Service::query()->create([
            'external_id' => 'nutricion-parenteral',
            'name' => 'Nutricion Parenteral',
            'category' => 'Farmaceuticos',
            'specialty' => 'Central de Mezclas de Nutricion Parenteral',
            'status' => 'active',
        ]);

        $contract = ContractedService::query()->create([
            'institution_id' => $institution->id,
            'medical_unit_id' => $unit->id,
            'service_id' => $service->id,
            'contract_number' => 'UNIT-001',
            'status' => 'active',
        ]);

        ContractedService::query()->create([
            'institution_id' => $institution->id,
            'medical_unit_id' => $unit->id,
            'service_id' => $consultationService->id,
            'contract_number' => 'UNIT-CE-001',
            'status' => 'active',
        ]);

        $nutritionContract = ContractedService::query()->create([
            'institution_id' => $institution->id,
            'medical_unit_id' => $unit->id,
            'service_id' => $nutritionService->id,
            'contract_number' => 'UNIT-NPT-001',
            'status' => 'active',
        ]);

        $prescription = Prescription::query()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'code' => 'REC-UNIT-001',
            'status' => 'active',
            'issued_at' => now(),
        ]);
        $prescription->items()->create([
            'medication_name' => 'Paracetamol',
            'dose' => '500 mg',
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

        $nutritionRequest = ProviderRequest::query()->create([
            'provider_id' => $provider->id,
            'patient_id' => $patient->id,
            'medical_unit_id' => $unit->id,
            'external_id' => 'NPT-UNIT-001',
            'request_type' => 'npt',
            'status' => 'preparing',
            'requested_at' => now(),
            'required_at' => now()->addDay(),
            'payload' => [
                'mix_id' => 'MIX-UNIT-001',
                'lot' => 'LOT-UNIT-001',
                'authorizations' => ['operational' => 'approved', 'pharmacy' => 'approved'],
            ],
        ]);

        $this->actingAs($user)
            ->get(route('unit.dashboard'))
            ->assertOk()
            ->assertSee('unit-native-screen')
            ->assertSee('unit-native-sidebar')
            ->assertSee('unit-native-table')
            ->assertSee('Hospital Unidad Test')
            ->assertSee('Quimioterapia')
            ->assertSee('Ver Operacion')
            ->assertSee(route('operational.dashboard', ['area' => 'oncology', 'section' => 'history', 'unit' => $unit->id, 'service' => $service->id]))
            ->assertSee('Operacion en tiempo real')
            ->assertSee('data-unit-consultation-submenu', false)
            ->assertSee('data-unit-consultation-tab="home"', false)
            ->assertSee('data-unit-consultation-tab="agenda"', false)
            ->assertSee('data-unit-consultation-tab="rooms"', false)
            ->assertSee('data-unit-consultation-tab="doctors"', false)
            ->assertSee('data-unit-consultation-tab="specialties"', false)
            ->assertSee('data-unit-consultation-tab="patients"', false)
            ->assertSee('data-unit-consultation-tab="prescriptions"', false)
            ->assertSee('data-unit-consultation-calendar', false)
            ->assertSee('data-unit-calendar-mode="day"', false)
            ->assertSee('data-unit-calendar-mode="week"', false)
            ->assertSee('data-unit-calendar-mode="month"', false)
            ->assertSee('data-unit-calendar-mode="list"', false)
            ->assertSee('Agendar nueva cita')
            ->assertSee('data-unit-calendar-new-dialog', false)
            ->assertSee('data-unit-calendar-edit-dialog', false)
            ->assertSee('data-unit-calendar-cancel-dialog', false)
            ->assertSee('data-unit-calendar-edit', false)
            ->assertSee('data-unit-calendar-reschedule', false)
            ->assertSee('data-unit-calendar-cancel', false)
            ->assertSee('Confirmar nueva cita')
            ->assertSee('data-unit-consultation-rooms', false)
            ->assertSee('Consultorios activos')
            ->assertSee('Nuevo consultorio')
            ->assertSee(route('unit.dashboard', ['unit' => $unit->id, 'section' => 'procedure-areas', 'create' => 'consulting']))
            ->assertSee('data-unit-room-search', false)
            ->assertSee('data-unit-room-detail', false)
            ->assertSee('Editar consultorio')
            ->assertSee('data-unit-room-more', false)
            ->assertSee('Agenda de hoy')
            ->assertSee('data-unit-room-agenda-body', false)
            ->assertSee('data-drsam-table-filter-skip', false)
            ->assertSee('data-unit-room-appointment-view', false)
            ->assertSee('Consultorio 9')
            ->assertSee('Consulta de control')
            ->assertSee('data-unit-nutrition-requests', false)
            ->assertSee('data-unit-nutrition-open', false)
            ->assertSee('data-unit-nutrition-dialog', false)
            ->assertSee('Solicitud de mezcla')
            ->assertSee(route('unit.nutrition-requests.store'), false)
            ->assertSee('data-unit-nutrition-filter="preparing"', false)
            ->assertSee('data-unit-nutrition-row', false)
            ->assertSee('data-request-service="chemotherapy"', false)
            ->assertSee('Filtrar solicitudes de quimioterapia')
            ->assertSee('data-unit-chemotherapy-request', false)
            ->assertSee(route('operational.dashboard', [
                'area' => 'oncology',
                'section' => 'calendar',
                'oncology_track' => 'infusions',
                'unit' => $unit->id,
                'new_infusion' => 1,
            ]))
            ->assertSee('Fecha y hora programada de entrega')
            ->assertSee('Estado operativo')
            ->assertSee('Aprobaci&oacute;n', false)
            ->assertSee('MIX-UNIT-001')
            ->assertSee('NPT-UNIT-001')
            ->assertSee('LOT-UNIT-001')
            ->assertSee(route('operational.dashboard', ['area' => 'nursing', 'section' => 'support', 'unit' => $unit->id, 'request' => $nutritionRequest->id]))
            ->assertSee('Resumen del dia')
            ->assertSee('REC-UNIT-001')
            ->assertSee('Solicitudes hoy')
            ->assertSee('Tiempo promedio')
            ->assertSee('REQ-UNIT-001')
            ->assertSee('data-unit-service-overview', false)
            ->assertSee('data-unit-service-operation', false)
            ->assertSee('data-unit-operation-filter="status"', false)
            ->assertSee('data-unit-operation-row', false)
            ->assertSee('data-unit-status-filter="Pendiente"', false)
            ->assertSee('Catalogo de productos/Servicios')
            ->assertSee('Catalogo de medicamentos')
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
            ->post(route('unit.nutrition-requests.store'), [
                'unit' => $unit->id,
                'service_contract_id' => $nutritionContract->id,
                'patient_id' => $patient->id,
                'doctor_id' => $doctor->id,
                'clinical_service' => 'Nutricion clinica',
                'priority' => 'urgent',
                'delivery_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
                'route' => 'Central',
                'npt_type' => 'Individualizada',
                'total_volume' => 1250,
                'infusion_hours' => 24,
                'diagnosis' => 'Soporte nutricional de prueba',
                'components' => 'Aminoacidos y lipidos',
                'notes' => 'Preparacion prioritaria',
            ])
            ->assertRedirect(route('unit.dashboard', [
                'unit' => $unit->id,
                'section' => 'services',
                'service' => $nutritionContract->id,
            ]));

        $createdNutritionRequest = ProviderRequest::query()
            ->where('request_type', 'npt')
            ->where('payload->source', 'unit_nutrition_board')
            ->firstOrFail();
        $this->assertSame('requested', $createdNutritionRequest->status);
        $this->assertSame($unit->id, $createdNutritionRequest->medical_unit_id);
        $this->assertSame($patient->id, $createdNutritionRequest->patient_id);
        $this->assertSame(1250, (int) data_get($createdNutritionRequest->payload, 'clinical_format.total_volume'));
        $this->assertDatabaseHas('provider_request_status_events', [
            'provider_request_id' => $createdNutritionRequest->id,
            'status' => 'requested',
        ]);

        $this->actingAs($user)
            ->get(route('operational.dashboard', ['area' => 'oncology', 'section' => 'history', 'unit' => $unit->id, 'service' => $service->id]))
            ->assertOk()
            ->assertSee('MODULO OPERATIVO')
            ->assertSee('Centro Oncologico')
            ->assertSee('Hospital Unidad Test');

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
            ])->assertRedirect(route('unit.dashboard', ['section' => 'procedure-areas', 'catalog' => 'consulting']));
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
            ])->assertRedirect(route('unit.dashboard', ['section' => 'procedure-areas', 'catalog' => 'consulting']));
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

        MedicationCatalogItem::query()->create([
            'institution_id' => $institution->id,
            'cnis' => '010.000.0104.00',
            'name' => 'Paracetamol',
            'generic_name' => 'Paracetamol',
            'therapeutic_group' => 'Analgesia',
            'description' => 'Tableta de 500 mg',
            'presentation' => 'Envase con 10 tabletas',
            'requires_prescription' => false,
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->get(route('unit.dashboard', ['section' => 'medications']))
            ->assertOk()
            ->assertSee('Catalogo de medicamentos')
            ->assertSee('Paracetamol')
            ->assertSee('010.000.0104.00')
            ->assertSee('data-medication-table', false);
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

    public function test_unit_user_can_create_edit_and_cancel_a_consultation_appointment(): void
    {
        $user = User::query()->create([
            'name' => 'Unidad Agenda',
            'username' => 'unidad.agenda',
            'email' => 'unidad.agenda@test.local',
            'role' => 'unit',
            'module' => 'unit',
            'status' => 'active',
        ]);

        $unit = MedicalUnit::query()->create([
            'name' => 'Unidad Agenda',
            'unit_username' => $user->username,
            'status' => 'active',
        ]);

        $doctor = Doctor::query()->create([
            'medical_unit_id' => $unit->id,
            'full_name' => 'Dra Agenda',
            'professional_license' => 'CED-AGENDA-01',
            'specialty' => 'Medicina interna',
            'status' => 'active',
        ]);

        $patient = Patient::query()->create([
            'full_name' => 'Paciente Agenda',
            'platform_number' => 'PAC-AGENDA-01',
            'status' => 'active',
        ]);

        $room = ProcedureArea::query()->create([
            'medical_unit_id' => $unit->id,
            'type' => 'consulting',
            'location' => 'Consulta externa',
            'unit_number' => 'C-12',
            'simultaneous_capacity' => 1,
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->post(route('unit.appointments.store'), [
                'unit' => $unit->id,
                'patient_id' => $patient->id,
                'doctor_id' => $doctor->id,
                'procedure_area_id' => $room->id,
                'specialty' => 'Medicina interna',
                'modality' => 'Presencial',
                'appointment_date' => '2030-01-15',
                'appointment_time' => '09:00',
                'duration' => 30,
                'reason' => 'Consulta de seguimiento',
                'notes' => 'Primera nota de agenda',
            ])
            ->assertRedirect(route('unit.dashboard', [
                'unit' => $unit->id,
                'calendar_date' => '2030-01-15',
            ]));

        $appointment = Appointment::query()->where('patient_id', $patient->id)->firstOrFail();
        $this->assertSame('CE-2030-'.str_pad((string) $appointment->id, 6, '0', STR_PAD_LEFT), data_get($appointment->metadata, 'folio'));

        $this->actingAs($user)
            ->patch(route('unit.appointments.update', $appointment), [
                'unit' => $unit->id,
                'patient_id' => $patient->id,
                'doctor_id' => $doctor->id,
                'procedure_area_id' => $room->id,
                'specialty' => 'Medicina interna',
                'modality' => 'Presencial',
                'appointment_date' => '2030-01-16',
                'appointment_time' => '10:30',
                'duration' => 30,
                'reason' => 'Consulta de seguimiento actualizada',
                'notes' => 'Paciente solicita horario matutino',
                'status' => 'confirmed',
            ])
            ->assertRedirect(route('unit.dashboard', [
                'unit' => $unit->id,
                'calendar_date' => '2030-01-16',
            ]));

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'procedure_area_id' => $room->id,
            'status' => 'confirmed',
            'reason' => 'Consulta de seguimiento actualizada',
        ]);
        $this->assertSame('Paciente solicita horario matutino', data_get($appointment->fresh()->metadata, 'notes'));

        $this->actingAs($user)
            ->from(route('unit.dashboard', ['unit' => $unit->id]))
            ->patch(route('unit.appointments.status', $appointment), [
                'unit' => $unit->id,
                'status' => 'cancelled',
            ])
            ->assertRedirect(route('unit.dashboard', ['unit' => $unit->id]))
            ->assertSessionHasErrors('notes');
        $this->assertSame('confirmed', $appointment->fresh()->status);

        $this->actingAs($user)
            ->patch(route('unit.appointments.status', $appointment), [
                'unit' => $unit->id,
                'status' => 'cancelled',
                'notes' => 'Paciente solicito la cancelacion',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'cancelled',
        ]);
        $this->assertDatabaseHas('appointment_status_events', [
            'appointment_id' => $appointment->id,
            'from_status' => 'confirmed',
            'to_status' => 'cancelled',
            'notes' => 'Paciente solicito la cancelacion',
        ]);
    }
}
