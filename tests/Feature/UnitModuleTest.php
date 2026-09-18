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

        $importService = Service::query()->create([
            'external_id' => 'medicamentos-importacion',
            'name' => 'Importacion de medicamentos',
            'category' => 'Farmaceuticos',
            'specialty' => 'Medicamentos de importacion',
            'status' => 'active',
        ]);

        $digitalPharmacyService = Service::query()->create([
            'external_id' => 'farmacia-digital',
            'name' => 'Pedido y entrega de medicamentos',
            'category' => 'Farmacia',
            'specialty' => 'Farmacia Digital',
            'status' => 'active',
        ]);

        $futureService = Service::query()->create([
            'external_id' => 'telemedicina-avanzada',
            'name' => 'Telemedicina avanzada',
            'category' => 'Atencion medica',
            'specialty' => 'Telemedicina',
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

        foreach ([$importService, $digitalPharmacyService, $futureService] as $genericService) {
            ContractedService::query()->create([
                'institution_id' => $institution->id,
                'medical_unit_id' => $unit->id,
                'service_id' => $genericService->id,
                'contract_number' => 'UNIT-'.$genericService->id,
                'status' => 'active',
            ]);
        }

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

        ProviderRequest::query()->create([
            'provider_id' => $provider->id,
            'patient_id' => $patient->id,
            'medical_unit_id' => $unit->id,
            'external_id' => 'IMP-UNIT-001',
            'request_type' => 'import',
            'status' => 'requested',
            'requested_at' => now(),
        ]);

        ProviderRequest::query()->create([
            'provider_id' => $provider->id,
            'patient_id' => $patient->id,
            'medical_unit_id' => $unit->id,
            'external_id' => 'PED-UNIT-001',
            'request_type' => 'order',
            'status' => 'delivered',
            'requested_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->get(route('unit.dashboard'));

        $response
            ->assertOk()
            ->assertSee('unit-native-screen')
            ->assertSee('unit-native-sidebar')
            ->assertSeeInOrder(['Servicios integrales', 'Usuarios', 'Pacientes', 'Medicos', 'Especialidades', 'Farmacia Externa', 'Areas de Procedimiento', 'Medicamentos'])
            ->assertSee('unit-native-table')
            ->assertSee('data-unit-service-catalog-link', false)
            ->assertSee('Catalogo')
            ->assertSee('Hospital Unidad Test')
            ->assertSee('Quimioterapia')
            ->assertSee('Ver Operacion')
            ->assertSee(route('operational.dashboard', ['area' => 'oncology', 'section' => 'history', 'unit' => $unit->id, 'service' => $service->id]))
            ->assertSee('Operacion en tiempo real')
            ->assertSee('data-unit-consultation-submenu', false)
            ->assertSee('data-unit-consultation-toolbar', false)
            ->assertSee('unit-consultation-submenu-action', false)
            ->assertDontSee('data-unit-consultation-prev', false)
            ->assertDontSee('data-unit-consultation-next', false)
            ->assertSee('data-unit-consultation-tab="home"', false)
            ->assertSee('data-unit-consultation-tab="agenda"', false)
            ->assertSee('data-unit-consultation-tab="rooms"', false)
            ->assertSee('data-unit-consultation-tab="doctors"', false)
            ->assertSee('data-unit-consultation-tab="specialties"', false)
            ->assertSee('data-unit-consultation-tab="patients"', false)
            ->assertSee('data-unit-consultation-tab="prescriptions"', false)
            ->assertSee('data-unit-consultation-calendar', false)
            ->assertSee('room_number', false)
            ->assertSee('"room_number":"C-09"', false)
            ->assertSee('unit-consultation-calendar-room-number', false)
            ->assertSee('data-unit-calendar-mode="day"', false)
            ->assertSee('data-unit-calendar-mode="week"', false)
            ->assertSee('data-unit-calendar-mode="month"', false)
            ->assertSee('data-unit-calendar-mode="list"', false)
            ->assertSee('Agendar nueva cita')
            ->assertSee('data-unit-calendar-new-dialog', false)
            ->assertSee('data-unit-calendar-edit-dialog', false)
            ->assertSee('data-unit-calendar-cancel-dialog', false)
            ->assertSee('Gestionar cita existente')
            ->assertSee('data-unit-calendar-dialog-tab="information"', false)
            ->assertSee('data-unit-calendar-dialog-tab="history"', false)
            ->assertSee('data-unit-calendar-dialog-history', false)
            ->assertSee('data-unit-calendar-edit', false)
            ->assertSee('data-unit-calendar-reschedule', false)
            ->assertSee('data-unit-calendar-reminder', false)
            ->assertSee('data-unit-calendar-cancel', false)
            ->assertSee('N&uacute;mero de ID de la plataforma', false)
            ->assertSee('data-unit-new-platform-number', false)
            ->assertSee('data-unit-patient-autocomplete="search"', false)
            ->assertSee('aria-controls="unit-new-patient-results"', false)
            ->assertSee('data-unit-patient-autocomplete="platform"', false)
            ->assertSee('data-unit-new-platform-results', false)
            ->assertSee('aria-controls="unit-new-platform-results"', false)
            ->assertSee('data-unit-new-reference-layout', false)
            ->assertSee('Datos de la consulta')
            ->assertSee('Fecha y horario')
            ->assertSee('data-unit-new-week', false)
            ->assertSee('data-unit-new-slot-count', false)
            ->assertSee('data-unit-new-time', false)
            ->assertSee('Selecciona un horario disponible')
            ->assertSee('data-unit-new-slots', false)
            ->assertSee('Confirmaci&oacute;n al paciente', false)
            ->assertSee('data-unit-new-contact-email', false)
            ->assertSee('data-unit-new-summary="primary"', false)
            ->assertSee('data-unit-new-reset', false)
            ->assertSee('Limpiar')
            ->assertSee('Agendar cita')
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
            ->assertSee('data-unit-service-layout="medicamentos-importacion"', false)
            ->assertSee('data-unit-service-layout="farmacia-digital"', false)
            ->assertSee('data-unit-service-layout="telemedicina-avanzada"', false)
            ->assertSee('data-unit-request-board', false)
            ->assertSee('data-unit-request-filter="preparing,route"', false)
            ->assertSee('Gestionar importaciones')
            ->assertSee('Gestionar pedidos')
            ->assertSee('Abrir modulo operativo')
            ->assertSee('IMP-UNIT-001')
            ->assertSee('PED-UNIT-001')
            ->assertSee('Catalogo de productos/Servicios')
            ->assertSee('Elementos habilitados')
            ->assertSee('data-open-service-catalog', false)
            ->assertDontSee('<iframe');

        $this->assertSame(6, substr_count($response->getContent(), 'data-unit-service-info'));
        $this->assertSame(6, substr_count($response->getContent(), 'data-unit-service-controls'));
        $this->assertSame(6, substr_count($response->getContent(), 'data-unit-service-content'));

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
            ->get(route('unit.dashboard', ['section' => 'catalog']))
            ->assertOk()
            ->assertSee('data-unit-catalog-navigation', false)
            ->assertSee('data-unit-carousel-menu="catalog"', false)
            ->assertSee('data-unit-catalog-info', false)
            ->assertSee('data-unit-catalog-controls', false)
            ->assertSee('data-unit-catalog-content', false)
            ->assertSee('Catalogo de la unidad')
            ->assertSee('>Usuarios</strong>', false)
            ->assertSee('Areas de Procedimiento');

        $this->actingAs($user)
            ->get(route('unit.dashboard', ['section' => 'users']))
            ->assertOk()
            ->assertSee('data-unit-catalog-navigation', false)
            ->assertSee('data-unit-carousel-menu="users"', false)
            ->assertSee('data-unit-catalog-info', false)
            ->assertSee('data-unit-catalog-controls', false)
            ->assertSee('data-unit-catalog-content', false)
            ->assertSee('<h2>Usuarios</h2>', false)
            ->assertSee('Alta de usuario operativo')
            ->assertSee('Editar usuario operativo')
            ->assertSee('Usuarios de la unidad')
            ->assertSee('Guardar cambios')
            ->assertSee('data-edit-operational-user', false)
            ->assertSee('Descargar catalogo')
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
            ->assertSee('data-unit-catalog-navigation', false)
            ->assertSee('data-unit-carousel-menu="patients"', false)
            ->assertSee('data-unit-catalog-info', false)
            ->assertSee('data-unit-catalog-controls', false)
            ->assertSee('data-unit-catalog-content', false)
            ->assertSee('<h2>Pacientes</h2>', false)
            ->assertSee('Paciente Unidad');

        $this->actingAs($user)
            ->get(route('unit.dashboard', ['section' => 'doctors']))
            ->assertOk()
            ->assertSee('data-unit-catalog-navigation', false)
            ->assertSee('data-unit-carousel-menu="doctors"', false)
            ->assertSee('data-unit-catalog-info', false)
            ->assertSee('data-unit-catalog-controls', false)
            ->assertSee('data-unit-catalog-content', false)
            ->assertSee('<h2>Medicos</h2>', false)
            ->assertSee('Dra Unidad')
            ->assertSee('Nuevo medico adscrito')
            ->assertSee('data-open-doctor-dialog', false)
            ->assertSee('data-doctor-dialog', false)
            ->assertSee('data-close-doctor-dialog', false)
            ->assertSee('data-doctor-success-dialog', false)
            ->assertSee('data-close-doctor-success', false)
            ->assertDontSee('id="unit-doctor-form"', false)
            ->assertDontSee('Alta de medico adscrito')
            ->assertSee('Editar autorizaciones')
            ->assertSee('Usuario de la plataforma')
            ->assertSee('>Guardar</button>', false)
            ->assertSee('>Cancelar</button>', false)
            ->assertSee('Descargar catalogo');

        $this->actingAs($user)
            ->post(route('unit.doctors.store'), [
                '_doctor_form' => '1',
                'first_name' => 'Mario', 'last_name' => 'Adscripto',
                'specialty' => 'Oncologia', 'services' => ['Quimioterapia'],
            ])
            ->assertSessionHasErrors('professional_license');
        $this->actingAs($user)
            ->get(route('unit.dashboard', ['section' => 'doctors']))
            ->assertOk()
            ->assertSee('No se pudo guardar el medico.')
            ->assertSee('value="Mario"', false)
            ->assertSee('doctorDialog?.showModal();', false)
            ->assertDontSee('No se pudo actualizar.');

        $this->actingAs($user)
            ->post(route('unit.doctors.store'), [
                'first_name' => 'Mario', 'last_name' => 'Adscripto', 'platform_user' => 'med-900',
                'professional_license' => 'CED-900', 'specialty' => 'Oncologia',
                'subspecialty' => 'Atencion clinica', 'services' => ['Quimioterapia'],
            ])
            ->assertRedirect(route('unit.dashboard', ['section' => 'doctors']))
            ->assertSessionHas('doctor_created', true)
            ->assertSessionMissing('status');
        $this->assertDatabaseHas('doctors', ['medical_unit_id' => $unit->id, 'full_name' => 'Mario Adscripto', 'professional_license' => 'CED-900', 'status' => 'pending']);
        $this->actingAs($user)
            ->get(route('unit.dashboard', ['section' => 'doctors']))
            ->assertOk()
            ->assertSee('Guardado con éxito')
            ->assertSee('doctorSuccessDialog?.showModal();', false);
        $createdDoctor = Doctor::query()->where('professional_license', 'CED-900')->firstOrFail();
        $this->actingAs($user)
            ->put(route('unit.doctors.authorizations.update', $createdDoctor), ['status' => 'active', 'services' => ['Quimioterapia']])
            ->assertRedirect(route('unit.dashboard', ['section' => 'doctors']));
        $this->assertSame('active', $createdDoctor->fresh()->status);
        $this->assertSame(['Quimioterapia'], $createdDoctor->fresh()->metadata['services']);

        $this->actingAs($user)
            ->get(route('unit.dashboard', ['section' => 'specialties']))
            ->assertOk()
            ->assertSee('data-unit-catalog-navigation', false)
            ->assertSee('data-unit-carousel-menu="specialties"', false)
            ->assertSee('data-unit-catalog-info', false)
            ->assertSee('data-unit-catalog-controls', false)
            ->assertSee('data-unit-catalog-content', false)
            ->assertSee('<h2>Especialidades</h2>', false)
            ->assertSee('Oncologia');

        $this->actingAs($user)
            ->get(route('unit.dashboard', ['section' => 'external-pharmacy']))
            ->assertOk()
            ->assertSee('data-unit-catalog-navigation', false)
            ->assertSee('data-unit-carousel-menu="external-pharmacy"', false)
            ->assertSee('data-unit-catalog-info', false)
            ->assertSee('data-unit-catalog-controls', false)
            ->assertSee('data-unit-catalog-content', false)
            ->assertSee('<h2>Farmacia Externa</h2>', false)
            ->assertSee('Sin medicamentos institucionales para esta unidad.');

        $this->actingAs($user)
            ->get(route('unit.dashboard', ['section' => 'procedure-areas']))
            ->assertOk()
            ->assertSee('data-unit-catalog-navigation', false)
            ->assertSee('data-unit-carousel-menu="procedure-areas"', false)
            ->assertSee('data-unit-catalog-info', false)
            ->assertSee('data-unit-catalog-controls', false)
            ->assertSee('data-unit-catalog-content', false)
            ->assertSee('<h2>Areas de Procedimiento</h2>', false)
            ->assertSee('data-procedure-category="all"', false)
            ->assertSee('data-procedure-catalog="all"', false)
            ->assertSee('Catalogo de subunidades')
            ->assertSee('Todos')
            ->assertSee('Catalogo de consultorios')
            ->assertSee('Catalogo de salas de infusion')
            ->assertSee('Catalogo de quirofanos')
            ->assertSee('Catalogo de salas de recuperacion')
            ->assertSee('Nuevo consultorio')
            ->assertSee('data-procedure-create', false)
            ->assertSee('data-procedure-create-dialog', false)
            ->assertSee('data-procedure-edit-dialog', false);
        $this->actingAs($user)
            ->get(route('unit.dashboard', ['section' => 'procedure-areas', 'create' => 'consulting']))
            ->assertOk()
            ->assertSee('Nueva subunidad')
            ->assertSee('Guardar subunidad')
            ->assertSee('Horario de atencion de la unidad')
            ->assertSee('data-open-on-load', false);
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
            ->assertOk()
            ->assertSee('Editar consultorio')
            ->assertSee('Guardar cambios')
            ->assertSee('Cancelar')
            ->assertSee('data-procedure-edit', false)
            ->assertSee('data-procedure-edit-form', false)
            ->assertSee('data-procedure-edit-dialog-close', false)
            ->assertSee('data-open-on-load', false)
            ->assertSee('C-01');
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
            ->assertSee('data-unit-catalog-navigation', false)
            ->assertSee('data-unit-carousel-menu="medications"', false)
            ->assertSee('data-unit-catalog-info', false)
            ->assertSee('data-unit-catalog-controls', false)
            ->assertSee('data-unit-catalog-content', false)
            ->assertSee('<h2>Medicamentos</h2>', false)
            ->assertSee('Paracetamol')
            ->assertSee('010.000.0104.00')
            ->assertSee('data-medication-table', false);

        foreach ([
            'catalog' => ['all', 'operation', 'clinical', 'pharmacy'],
            'users' => ['all', 'active', 'inactive'],
            'patients' => ['all', 'active', 'inactive'],
            'doctors' => ['all', 'active', 'pending', 'inactive'],
            'specialties' => ['all', 'active', 'inactive'],
            'external-pharmacy' => ['all', 'active', 'inactive'],
            'procedure-areas' => ['all', 'consulting', 'infusion', 'operating', 'uci', 'uti', 'recovery'],
            'medications' => ['all', 'active', 'inactive'],
        ] as $section => $filters) {
            $html = $this->actingAs($user)
                ->get(route('unit.dashboard', ['section' => $section]))
                ->assertOk()
                ->getContent();

            $this->assertSame(1, preg_match('/<section[^>]*data-unit-carousel-menu="'.preg_quote($section, '/').'"[^>]*>.*?<\/section>/s', $html, $carousel));
            preg_match_all('/data-unit-carousel-filter="([^"]+)"/', $carousel[0], $matches);
            $this->assertSame($filters, $matches[1], $section);
            $this->assertStringContainsString('<strong>Catálogo</strong>', $carousel[0], $section);
            $this->assertStringNotContainsString('href=', $carousel[0], $section);
        }

        $serviceHtml = $this->actingAs($user)
            ->get(route('unit.dashboard', ['section' => 'services', 'service' => 'catalog']))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('data-unit-service-tab="catalog"', $serviceHtml);
        $this->assertStringContainsString('data-unit-service-panel="catalog"', $serviceHtml);
        $this->assertStringContainsString('data-unit-open-service="'.$contract->id.'"', $serviceHtml);
        $this->assertStringNotContainsString('data-unit-service-catalog-link href=', $serviceHtml);
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

        $appointmentPayload = [
            'unit' => $unit->id,
            'patient_id' => $patient->id,
            'platform_number' => 'PAC-AGENDA-01',
            'doctor_id' => $doctor->id,
            'procedure_area_id' => $room->id,
            'specialty' => 'Medicina interna',
            'modality' => 'Presencial',
            'appointment_date' => '2030-01-15',
            'appointment_time' => '09:00',
            'duration' => 30,
            'priority' => 'routine',
            'reason' => 'Consulta de seguimiento',
            'notes' => 'Primera nota de agenda',
            'notify_email' => true,
            'notify_sms' => false,
        ];

        $this->actingAs($user)
            ->post(route('unit.appointments.store'), array_diff_key($appointmentPayload, ['platform_number' => true]))
            ->assertSessionHasErrors('platform_number');
        $this->assertDatabaseCount('appointments', 0);

        $this->actingAs($user)
            ->post(route('unit.appointments.store'), $appointmentPayload)
            ->assertRedirect(route('unit.dashboard', [
                'unit' => $unit->id,
                'calendar_date' => '2030-01-15',
            ]));

        $appointment = Appointment::query()->where('patient_id', $patient->id)->firstOrFail();
        $this->assertSame('CE-2030-'.str_pad((string) $appointment->id, 6, '0', STR_PAD_LEFT), data_get($appointment->metadata, 'folio'));
        $this->assertSame('PAC-AGENDA-01', data_get($appointment->metadata, 'patient_platform_number'));
        $this->assertSame('routine', data_get($appointment->metadata, 'priority'));
        $this->assertTrue(data_get($appointment->metadata, 'notifications.email'));
        $this->assertFalse(data_get($appointment->metadata, 'notifications.sms'));

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
