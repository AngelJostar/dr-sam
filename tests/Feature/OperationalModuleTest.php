<?php

namespace Tests\Feature;

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
use App\Models\Prescription;
use App\Models\ProcedureArea;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class OperationalModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_operational_user_can_open_native_dashboard(): void
    {
        $user = User::query()->create([
            'name' => 'Operador Enfermeria',
            'username' => 'op.enfermeria.test',
            'email' => 'op.enfermeria@test.local',
            'role' => 'operational',
            'module' => 'operational',
            'status' => 'active',
        ]);

        $institution = Institution::query()->create([
            'name' => 'Institucion Test',
            'status' => 'active',
        ]);

        $unit = MedicalUnit::query()->create([
            'institution_id' => $institution->id,
            'name' => 'Unidad Operativa Test',
            'code' => 'UOT',
            'status' => 'active',
        ]);

        $area = OperationalArea::query()->create([
            'key' => 'enfermeria-test',
            'label' => 'Enfermeria',
            'role_label' => 'Operador',
            'default_permissions' => ['view-history'],
        ]);

        OperationalProfile::query()->create([
            'user_id' => $user->id,
            'medical_unit_id' => $unit->id,
            'operational_area_id' => $area->id,
            'role_label' => 'Operador',
            'status' => 'active',
            'permissions' => ['view-history'],
        ]);

        $service = Service::query()->create([
            'name' => 'Nutricion parenteral',
            'category' => 'Terapia',
            'specialty' => 'Nutricion',
            'status' => 'active',
        ]);

        ContractedService::query()->create([
            'institution_id' => $institution->id,
            'medical_unit_id' => $unit->id,
            'service_id' => $service->id,
            'contract_number' => 'TEST-001',
            'status' => 'active',
            'starts_at' => now()->subMonth(),
            'ends_at' => now()->addMonth(),
        ]);

        $product = PharmacyProduct::query()->create([
            'name' => 'Solucion Test',
            'price' => 120,
            'status' => 'active',
        ]);

        InventoryItem::query()->create([
            'pharmacy_product_id' => $product->id,
            'medical_unit_id' => $unit->id,
            'warehouse' => 'Central',
            'quantity' => 15,
            'status' => 'available',
        ]);

        $patient = Patient::query()->create([
            'first_name' => 'Paciente',
            'last_name' => 'Operativo',
            'full_name' => 'Paciente Operativo',
            'curp' => 'PAOP900101HMCXXX01',
            'platform_number' => 'USR-HGCH-001',
            'status' => 'active',
            'metadata' => [
                'nss_federal' => '04967231458',
                'nss_estatal' => 'MEX-248391',
                'state' => 'Mexico',
            ],
        ]);

        $provider = Provider::query()->create([
            'name' => 'Proveedor NPT Test',
            'provider_type' => 'npt',
            'status' => 'active',
        ]);

        ProviderRequest::query()->create([
            'provider_id' => $provider->id,
            'patient_id' => $patient->id,
            'medical_unit_id' => $unit->id,
            'external_id' => 'REQ-OP-001',
            'request_type' => 'npt',
            'status' => 'requested',
            'requested_at' => now(),
            'required_at' => now()->addDay(),
        ]);

        $oncologyPatient = Patient::query()->create([
            'first_name' => 'Ana Sofia',
            'last_name' => 'Morales Reyes',
            'full_name' => 'Ana Sofia Morales Reyes',
            'curp' => 'MOSA870101MMCXXX02',
            'platform_number' => 'USR-ONC-001',
            'status' => 'active',
            'metadata' => [
                'age' => 54,
                'nss_federal' => '04967231458',
                'nss_estatal' => 'MEX-248391',
                'state' => 'Mexico',
            ],
        ]);

        $oncologyRequest = ProviderRequest::query()->create([
            'provider_id' => $provider->id,
            'patient_id' => $oncologyPatient->id,
            'medical_unit_id' => $unit->id,
            'external_id' => 'QT-001',
            'request_type' => 'chemo',
            'status' => 'requested',
            'requested_at' => now()->subMinutes(30),
            'required_at' => now()->addDay(),
            'payload' => [
                'volume' => '500',
                'doctor' => 'Dra. Laura Benitez',
                'mix_status' => 'Protocolo por validar',
            ],
        ]);

        $this->actingAs($user)
            ->get(route('operational.dashboard'))
            ->assertOk()
            ->assertSee('MODULO OPERATIVO')
            ->assertSee('operational-native-screen')
            ->assertSee('operational-native-sidebar')
            ->assertSee('Farmacia Externa')
            ->assertSee(route('external-pharmacy.dashboard'), false)
            ->assertSee('operational-native-table')
            ->assertSee('Unidad Operativa Test')
            ->assertSee('REQ-OP-001')
            ->assertSee('Historial de solicitudes')
            ->assertDontSee('Solicitudes enviadas por hospitales')
            ->assertDontSee('<iframe');

        $this->actingAs($user)
            ->get(route('operational.dashboard', ['section' => 'history']))
            ->assertOk()
            ->assertSee('Historial de solicitudes')
            ->assertDontSee('Historial de solicitudes enviadas por hospitales')
            ->assertSee('REQ-OP-001');

        $this->actingAs($user)
            ->get(route('operational.patients.index', ['unit' => $unit->id]))
            ->assertOk()
            ->assertSee('data-operational-patient-catalog', false)
            ->assertDontSee('<nav class="operational-section-tabs"', false)
            ->assertDontSee('<section class="operational-oncology-carousel"', false)
            ->assertSee('Catalogo de pacientes')
            ->assertSee('Pacientes vinculados a todas las areas operativas')
            ->assertSee('Nuevo paciente')
            ->assertSee('Alta de paciente')
            ->assertSee('Paciente')
            ->assertSee('Operativo')
            ->assertSee('Ana Sofia')
            ->assertSee('USR-HGCH-001');

        $this->actingAs($user)
            ->get(route('operational.patients.index', ['unit' => $unit->id, 'modal' => 'patient']))
            ->assertOk()
            ->assertSee('operational-modal is-open')
            ->assertSee('Guardar paciente');

        $oncologyResponse = $this->actingAs($user)
            ->get(route('operational.dashboard', ['area' => 'oncology']));

        $oncologyResponse
            ->assertOk()
            ->assertSee('MODULO OPERATIVO')
            ->assertSee('Centro Oncologico')
            ->assertSee('operational-compact-carousel')
            ->assertSee('Central de mezclas')
            ->assertSee('Salas de infusion')
            ->assertDontSee('Formato de solicitud')
            ->assertSeeInOrder([
                'Calendario de Infusiones',
                'Salas de infusion',
                'Central de mezclas',
                'Soporte',
            ])
            ->assertSee(route('operational.dashboard', [
                'area' => 'oncology',
                'section' => 'infusion-rooms',
                'oncology_track' => 'infusions',
                'unit' => $unit->id,
            ]))
            ->assertSee('Soporte')
            ->assertDontSee('Mezclas Oncologicas')
            ->assertSee('QT-001')
            ->assertSee('Oncologia medica')
            ->assertSee('Protocolo por validar')
            ->assertDontSee('REQ-OP-001');

        $this->assertSame(1, substr_count($oncologyResponse->getContent(), 'Salas de infusion'));

        $this->actingAs($user)
            ->get(route('operational.dashboard', ['area' => 'oncology', 'section' => 'history']))
            ->assertOk()
            ->assertSee('Historial de solicitudes enviadas por hospitales')
            ->assertSee('QT-001');

        $this->actingAs($user)
            ->get(route('operational.dashboard', ['area' => 'oncology', 'section' => 'patients']))
            ->assertOk()
            ->assertSee('data-operational-patient-catalog', false)
            ->assertDontSee('<nav class="operational-section-tabs"', false)
            ->assertDontSee('<section class="operational-oncology-carousel"', false)
            ->assertSee('Catalogo de pacientes')
            ->assertSee('USR-HGCH-001')
            ->assertSee('Ana Sofia')
            ->assertSee('USR-ONC-001');

        foreach ([
            'services-pending' => 'Pendientes',
            'services-history' => 'Historial',
            'service-create' => 'Nuevo servicio',
            'service-format' => 'SOLICITUD DE ONCOLÃ“GICOS',
            'mixes' => 'Mezclas programadas',
            'mix-history' => 'Historial de mezclas',
            'calendar' => 'Inicio',
            'infusion-rooms' => 'Inicio de salas de infusi',
            'infusion-room-catalog' => 'CATALOGO',
            'support' => 'SOLICITUD DE ONCOLÃ“GICOS',
            'support-ai' => 'data-oncology-support-ai',
            'support-analytics' => 'data-oncology-support-analytics',
        ] as $operationalSection => $expectedText) {
            $this->actingAs($user)
                ->get(route('operational.dashboard', ['area' => 'oncology', 'section' => $operationalSection]))
                ->assertOk()
                ->assertSee($expectedText);
        }

        $this->actingAs($user)
            ->get(route('operational.dashboard', ['area' => 'oncology', 'section' => 'support', 'unit' => $unit->id]))
            ->assertOk()
            ->assertSee('operational-oncology-filter is-active', false)
            ->assertSeeInOrder(['Formato de solicitud', 'Asistencia con IA', 'Analiticas'])
            ->assertDontSee('En preparaci&oacute;n', false)
            ->assertSee('Guardar formato')
            ->assertSee(route('operational.service-requests.store'), false)
            ->assertDontSee('<h2>Soporte</h2>', false);

        $this->actingAs($user)
            ->get(route('operational.dashboard', ['area' => 'oncology', 'section' => 'support-ai', 'unit' => $unit->id]))
            ->assertOk()
            ->assertSee('data-oncology-support-ai', false)
            ->assertSee('Asistencia con IA')
            ->assertSee('Abrir solicitud')
            ->assertSeeInOrder(['Formato de solicitud', 'Asistencia con IA', 'Analiticas'])
            ->assertSee('operational-oncology-filter is-active', false);

        $this->actingAs($user)
            ->get(route('operational.dashboard', ['area' => 'oncology', 'section' => 'support-analytics', 'unit' => $unit->id]))
            ->assertOk()
            ->assertSee('data-oncology-support-analytics', false)
            ->assertSee('Anal&iacute;ticas', false)
            ->assertSee('Finalizadas')
            ->assertSeeInOrder(['Formato de solicitud', 'Asistencia con IA', 'Analiticas'])
            ->assertSee('operational-oncology-filter is-active', false);

        $this->actingAs($user)
            ->get(route('operational.dashboard', [
                'area' => 'oncology',
                'section' => 'support',
                'unit' => $unit->id,
                'request' => $oncologyRequest->id,
            ]))
            ->assertOk()
            ->assertSee('Detalle de solicitud')
            ->assertSee('Ana Sofia Morales Reyes')
            ->assertSee('Dra. Laura Benitez')
            ->assertSee('operational-oncology-filter is-active', false);

        $this->actingAs($user)
            ->get(route('operational.dashboard', ['area' => 'oncology', 'section' => 'calendar']))
            ->assertOk()
            ->assertSee('+ Nueva infusión')
            ->assertDontSee('+ Nuevo servicio');

        $this->actingAs($user)
            ->post(route('operational.patients.store'), [
                'first_name' => 'Paciente Nueva',
                'last_name' => 'Operativa',
                'curp' => 'NUOP900101MMCXXX03',
                'state' => 'MÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â©xico',
            ])
            ->assertRedirect(route('operational.patients.index', ['unit' => $unit->id]));

        $this->assertDatabaseHas('patients', ['full_name' => 'Paciente Nueva Operativa']);
        $this->assertSame($unit->id, data_get(Patient::query()->where('full_name', 'Paciente Nueva Operativa')->firstOrFail()->metadata, 'medical_unit_id'));

        $this->actingAs($user)
            ->post(route('operational.service-requests.store'), [
                'patient_id' => $oncologyPatient->id,
                'service' => 'Quimioterapia ambulatoria',
                'doctor' => 'Dra. Laura Benitez',
                'volume' => 650,
                'diagnosis' => 'DiagnÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â³stico de prueba',
            ])
            ->assertRedirect(route('operational.dashboard', ['area' => 'oncology', 'section' => 'services-pending']));

        $this->assertDatabaseHas('provider_requests', [
            'patient_id' => $oncologyPatient->id,
            'request_type' => 'chemo',
            'status' => 'requested',
        ]);

        $this->actingAs($user)
            ->get(route('operational.dashboard', ['area' => 'inpatient-pharmacy']))
            ->assertOk()
            ->assertSee('MODULO OPERATIVO')
            ->assertSee('Farmacia intrahospitalaria')
            ->assertSee('operational-inpatient-pharmacy-carousel')
            ->assertSeeInOrder([
                'class="operational-oncology-carousel operational-compact-carousel operational-inpatient-pharmacy-carousel"',
                'class="operational-section-tabs"',
            ], false)
            ->assertSee('Solicitudes pendientes')
            ->assertSee('Historial de solicitudes')
            ->assertSee('REQ-OP-001')
            ->assertSee('QT-001')
            ->assertSee('Remision de entrega');

        $this->actingAs($user)
            ->get(route('operational.dashboard', ['area' => 'inpatient-pharmacy', 'section' => 'history']))
            ->assertOk()
            ->assertSee('operational-inpatient-pharmacy-carousel')
            ->assertSeeInOrder([
                'class="operational-oncology-carousel operational-compact-carousel operational-inpatient-pharmacy-carousel"',
                'class="operational-section-tabs"',
            ], false)
            ->assertSee('Historial de solicitudes enviadas por hospitales');

        $this->actingAs($user)
            ->get(route('operational.dashboard', ['area' => 'inpatient-pharmacy', 'section' => 'patients']))
            ->assertOk()
            ->assertSee('data-operational-patient-catalog', false)
            ->assertDontSee('<nav class="operational-section-tabs"', false)
            ->assertSee('Catalogo de pacientes')
            ->assertSee('Nuevo paciente')
            ->assertSee('Paciente')
            ->assertSee('Ana Sofia');
    }

    public function test_operational_service_request_can_link_outpatient_prescription(): void
    {
        $user = User::query()->create([
            'name' => 'Operador Oncologia',
            'username' => 'op.oncologia.rx',
            'email' => 'op.oncologia.rx@test.local',
            'role' => 'operational',
            'module' => 'operational',
            'status' => 'active',
        ]);

        $unit = MedicalUnit::query()->create([
            'name' => 'Unidad Oncologia RX',
            'status' => 'active',
        ]);

        $area = OperationalArea::query()->create([
            'key' => 'oncology',
            'label' => 'Centro Oncologico',
            'role_label' => 'Operador oncologico',
            'default_permissions' => ['manage-services'],
        ]);

        OperationalProfile::query()->create([
            'user_id' => $user->id,
            'medical_unit_id' => $unit->id,
            'operational_area_id' => $area->id,
            'role_label' => 'Operador oncologico',
            'status' => 'active',
            'permissions' => ['manage-services'],
        ]);

        $patient = Patient::query()->create([
            'first_name' => 'Paciente',
            'last_name' => 'Consulta Externa',
            'full_name' => 'Paciente Consulta Externa',
            'platform_number' => 'USR-RX-OP-001',
            'status' => 'active',
        ]);

        $doctor = Doctor::query()->create([
            'medical_unit_id' => $unit->id,
            'full_name' => 'Dra. Consulta Externa',
            'specialty' => 'Oncologia medica',
            'status' => 'active',
        ]);

        Provider::query()->create([
            'name' => 'Proveedor Quimioterapia',
            'provider_type' => 'chemo',
            'status' => 'active',
        ]);

        $prescription = Prescription::query()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'code' => 'RX-CE-001',
            'status' => 'active',
            'issued_at' => now()->subHour(),
            'metadata' => [
                'source' => 'outpatient_module',
                'medical_unit_id' => $unit->id,
                'diagnosis' => 'Diagnostico oncologico',
            ],
        ]);

        $prescription->items()->create([
            'medication_name' => 'Medicamento oncologico',
            'dose' => '100 mg',
            'frequency' => 'Cada 21 dias',
            'duration' => '3 ciclos',
            'instructions' => 'Infusion IV',
            'metadata' => [
                'route' => 'IV',
                'quantity' => 1,
            ],
        ]);

        $this->actingAs($user)
            ->post(route('operational.service-requests.store'), [
                'patient_id' => $patient->id,
                'prescription_id' => $prescription->id,
                'service' => 'Quimioterapia desde consulta externa',
                'doctor' => $doctor->full_name,
                'volume' => 500,
                'notes' => 'Solicitud ligada a receta',
            ])
            ->assertRedirect(route('operational.dashboard', ['area' => 'oncology', 'section' => 'services-pending']));

        $providerRequest = ProviderRequest::query()->firstOrFail();

        $this->assertSame($prescription->id, data_get($providerRequest->payload, 'prescription_id'));
        $this->assertTrue((bool) data_get($providerRequest->payload, 'outpatient_source'));
        $this->assertSame('RX-CE-001', data_get($providerRequest->payload, 'prescription_code'));
        $this->assertSame('Medicamento oncologico', data_get($providerRequest->payload, 'prescription_items.0.medication_name'));

        $prescription->refresh();
        $this->assertSame('requested', data_get($prescription->metadata, 'operational_status'));
        $this->assertSame($providerRequest->id, data_get($prescription->metadata, 'operational_provider_request_id'));
    }
    public function test_operational_user_can_update_provider_request_status(): void
    {
        $user = User::query()->create([
            'name' => 'Operador Farmacia',
            'username' => 'op.farmacia.status',
            'email' => 'op.farmacia.status@test.local',
            'role' => 'operational',
            'module' => 'operational',
            'status' => 'active',
        ]);

        $unit = MedicalUnit::query()->create([
            'name' => 'Unidad Status',
            'status' => 'active',
        ]);

        $patient = Patient::query()->create([
            'full_name' => 'Paciente Status Operativo',
            'status' => 'active',
        ]);
        $doctor = Doctor::query()->create([
            'medical_unit_id' => $unit->id,
            'full_name' => 'Dra. Status Operativo',
            'status' => 'active',
        ]);
        $prescription = Prescription::query()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'code' => 'RX-OP-STATUS',
            'status' => 'active',
            'issued_at' => now(),
            'metadata' => ['medical_unit_id' => $unit->id],
        ]);

        $request = ProviderRequest::query()->create([
            'medical_unit_id' => $unit->id,
            'patient_id' => $patient->id,
            'request_type' => 'chemo',
            'status' => 'requested',
            'requested_at' => now(),
            'payload' => ['prescription_id' => $prescription->id, 'prescription_code' => 'RX-OP-STATUS'],
        ]);

        $this->actingAs($user)
            ->patch(route('operational.provider-requests.status', $request), [
                'status' => 'accepted',
                'notes' => 'Aceptada por operacion',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('provider_requests', [
            'id' => $request->id,
            'status' => 'accepted',
        ]);

        $this->assertDatabaseHas('provider_request_status_events', [
            'provider_request_id' => $request->id,
            'status' => 'accepted',
            'actor' => 'Operador Farmacia',
            'notes' => 'Aceptada por operacion',
        ]);

        $prescription->refresh();
        $this->assertSame('accepted', data_get($prescription->metadata, 'operational_status'));
        $this->assertSame('accepted', data_get($prescription->metadata, 'operational_provider_request_status'));
        $this->assertSame($request->id, data_get($prescription->metadata, 'operational_provider_request_id'));
        $this->assertNotEmpty(data_get($prescription->metadata, 'operational_provider_request_status_at'));
        $this->assertNotEmpty(data_get($prescription->metadata, 'operational_provider_request_status_event_id'));
    }

    public function test_authorizations_are_independent_and_restricted_to_the_operational_area(): void
    {
        $unit = MedicalUnit::query()->create(['name' => 'Unidad Autorizaciones', 'status' => 'active']);
        $nursingArea = OperationalArea::query()->create(['key' => 'nursing', 'label' => 'Enfermeria']);
        $pharmacyArea = OperationalArea::query()->create(['key' => 'inpatient-pharmacy', 'label' => 'Farmacia intrahospitalaria']);
        $nursingUser = User::query()->create(['name' => 'Operador Enfermeria', 'username' => 'op.auth.nursing', 'email' => 'nursing.auth@test.local', 'role' => 'operational', 'module' => 'operational', 'status' => 'active']);
        $pharmacyUser = User::query()->create(['name' => 'Operador Farmacia', 'username' => 'op.auth.pharmacy', 'email' => 'pharmacy.auth@test.local', 'role' => 'operational', 'module' => 'operational', 'status' => 'active']);
        OperationalProfile::query()->create(['user_id' => $nursingUser->id, 'medical_unit_id' => $unit->id, 'operational_area_id' => $nursingArea->id, 'status' => 'active']);
        OperationalProfile::query()->create(['user_id' => $pharmacyUser->id, 'medical_unit_id' => $unit->id, 'operational_area_id' => $pharmacyArea->id, 'status' => 'active']);
        $providerRequest = ProviderRequest::query()->create([
            'medical_unit_id' => $unit->id,
            'request_type' => 'npt',
            'status' => 'requested',
            'requested_at' => now(),
            'payload' => ['authorizations' => ['operational' => 'pending', 'pharmacy' => 'pending']],
        ]);

        $this->actingAs($nursingUser)
            ->patch(route('operational.provider-requests.authorizations.update', $providerRequest), [
                'authorization' => 'operational', 'operating_area' => 'nursing', 'status' => 'approved', 'notes' => 'Validado por enfermeria',
            ])
            ->assertRedirect();

        $providerRequest->refresh();
        $this->assertSame('approved', data_get($providerRequest->payload, 'authorizations.operational'));
        $this->assertSame('pending', data_get($providerRequest->payload, 'authorizations.pharmacy'));
        $this->assertSame('requested', $providerRequest->status);

        $this->actingAs($pharmacyUser)
            ->patch(route('operational.provider-requests.authorizations.update', $providerRequest), [
                'authorization' => 'operational', 'status' => 'rejected',
            ])
            ->assertForbidden();

        $this->actingAs($pharmacyUser)
            ->patch(route('operational.provider-requests.authorizations.update', $providerRequest), [
                'authorization' => 'pharmacy', 'operating_area' => 'inpatient-pharmacy', 'status' => 'approved', 'notes' => 'Validado por farmacia',
            ])
            ->assertRedirect();

        $providerRequest->refresh();
        $this->assertSame('approved', data_get($providerRequest->payload, 'authorizations.pharmacy'));
        $this->assertSame('requested', $providerRequest->status);
        $this->assertDatabaseHas('provider_request_status_events', [
            'provider_request_id' => $providerRequest->id,
            'status' => 'authorization_approved',
            'actor' => 'Operador Farmacia',
            'notes' => 'Validado por farmacia',
        ]);

        $this->actingAs($nursingUser)
            ->patch(route('operational.provider-requests.status', $providerRequest), [
                'status' => 'accepted',
                'provider_name' => 'Prodifem',
                'notes' => 'Enviado al proveedor',
            ])
            ->assertRedirect();

        $providerRequest->refresh();
        $this->assertSame('accepted', $providerRequest->status);
        $this->assertSame('Prodifem', data_get($providerRequest->payload, 'provider_assignment.name'));
        $this->assertSame('sent-to-provider', data_get($providerRequest->payload, 'provider_dispatch_status'));
    }

    public function test_rejected_authorization_cancels_and_locks_the_request_with_traceability(): void
    {
        $unit = MedicalUnit::query()->create(['name' => 'Unidad Rechazo', 'status' => 'active']);
        $area = OperationalArea::query()->create(['key' => 'nursing', 'label' => 'Enfermeria']);
        $user = User::query()->create([
            'name' => 'Juan Enfermero',
            'username' => 'op.rejection.nursing',
            'email' => 'rejection.nursing@test.local',
            'role' => 'operational',
            'module' => 'operational',
            'status' => 'active',
        ]);
        OperationalProfile::query()->create([
            'user_id' => $user->id,
            'medical_unit_id' => $unit->id,
            'operational_area_id' => $area->id,
            'status' => 'active',
        ]);
        $providerRequest = ProviderRequest::query()->create([
            'medical_unit_id' => $unit->id,
            'request_type' => 'npt',
            'status' => 'requested',
            'requested_at' => now(),
            'payload' => [
                'authorizations' => ['operational' => 'pending', 'pharmacy' => 'pending'],
                'authorization_requirements' => ['operational', 'pharmacy'],
            ],
        ]);

        $this->actingAs($user)
            ->patch(route('operational.provider-requests.authorizations.update', $providerRequest), [
                'authorization' => 'operational',
                'operating_area' => 'nursing',
                'status' => 'rejected',
                'notes' => 'La concentración no es viable.',
            ])
            ->assertRedirect();

        $providerRequest->refresh();
        $this->assertSame('cancelled', $providerRequest->status);
        $this->assertSame('rejected', data_get($providerRequest->payload, 'authorizations.operational'));
        $this->assertSame('Juan Enfermero', data_get($providerRequest->payload, 'cancellation.actor'));
        $this->assertSame('operational', data_get($providerRequest->payload, 'cancellation.area'));
        $this->assertSame('La concentración no es viable.', data_get($providerRequest->payload, 'cancellation.reason'));

        $this->actingAs($user)
            ->patch(route('operational.provider-requests.authorizations.update', $providerRequest), [
                'authorization' => 'operational',
                'operating_area' => 'nursing',
                'status' => 'approved',
            ])
            ->assertUnprocessable();
    }

    public function test_unit_user_can_operate_only_the_current_area_authorization(): void
    {
        $user = User::query()->create([
            'name' => 'Unidad Operativa',
            'username' => 'unidad.operativa',
            'email' => 'unidad.operativa@test.local',
            'role' => 'unit',
            'module' => 'unit',
            'status' => 'active',
        ]);
        $unit = MedicalUnit::query()->create([
            'name' => 'Unidad con autorización',
            'unit_username' => $user->username,
            'status' => 'active',
        ]);
        $providerRequest = ProviderRequest::query()->create([
            'medical_unit_id' => $unit->id,
            'request_type' => 'npt',
            'status' => 'requested',
            'requested_at' => now(),
            'payload' => [
                'authorizations' => ['operational' => 'pending', 'pharmacy' => 'pending'],
                'authorization_requirements' => ['operational', 'pharmacy'],
            ],
        ]);

        $this->actingAs($user)
            ->get(route('operational.dashboard', ['area' => 'nursing', 'section' => 'pending']))
            ->assertOk()
            ->assertSee('data-authorization-toggle="true"', false)
            ->assertSee('name="operating_area" value="nursing"', false);

        $this->actingAs($user)
            ->patch(route('operational.provider-requests.authorizations.update', $providerRequest), [
                'authorization' => 'operational',
                'operating_area' => 'nursing',
                'status' => 'approved',
            ])
            ->assertRedirect();

        $this->assertSame('approved', data_get($providerRequest->refresh()->payload, 'authorizations.operational'));

        $this->actingAs($user)
            ->patch(route('operational.provider-requests.status', $providerRequest), [
                'status' => 'cancelled',
            ])
            ->assertForbidden();

        $this->actingAs($user)
            ->patch(route('operational.provider-requests.authorizations.update', $providerRequest), [
                'authorization' => 'pharmacy',
                'operating_area' => 'nursing',
                'status' => 'approved',
            ])
            ->assertForbidden();
    }

    public function test_inpatient_pharmacy_can_cancel_only_after_both_authorizations_are_approved(): void
    {
        $unit = MedicalUnit::query()->create(['name' => 'Unidad Cancelación Farmacia', 'status' => 'active']);
        $area = OperationalArea::query()->create(['key' => 'farmacia', 'label' => 'Farmacia intrahospitalaria']);
        $user = User::query()->create([
            'name' => 'Responsable Farmacia',
            'username' => 'op.pharmacy.cancel',
            'email' => 'op.pharmacy.cancel@test.local',
            'role' => 'operational',
            'module' => 'operational',
            'status' => 'active',
        ]);
        OperationalProfile::query()->create([
            'user_id' => $user->id,
            'medical_unit_id' => $unit->id,
            'operational_area_id' => $area->id,
            'status' => 'active',
        ]);
        $providerRequest = ProviderRequest::query()->create([
            'medical_unit_id' => $unit->id,
            'request_type' => 'npt',
            'status' => 'requested',
            'requested_at' => now(),
            'payload' => [
                'authorizations' => ['operational' => 'approved', 'pharmacy' => 'pending'],
                'authorization_requirements' => ['operational', 'pharmacy'],
            ],
        ]);

        $this->actingAs($user)
            ->patch(route('operational.provider-requests.status', $providerRequest), [
                'status' => 'cancelled',
                'operating_area' => 'inpatient-pharmacy',
            ])
            ->assertForbidden();

        $payload = $providerRequest->payload;
        data_set($payload, 'authorizations.pharmacy', 'approved');
        $providerRequest->update(['payload' => $payload]);

        $this->actingAs($user)
            ->patch(route('operational.provider-requests.status', $providerRequest), [
                'status' => 'cancelled',
                'operating_area' => 'inpatient-pharmacy',
                'notes' => 'Cancelado por Farmacia intrahospitalaria',
            ])
            ->assertRedirect();

        $this->assertSame('cancelled', $providerRequest->refresh()->status);
    }

    public function test_superadmin_can_create_and_edit_an_infusion_room(): void
    {
        $user = User::query()->create([
            'name' => 'Superadministrador',
            'username' => 'superadmin.rooms',
            'email' => 'superadmin.rooms@test.local',
            'role' => 'superadmin',
            'module' => 'superadmin',
            'status' => 'active',
        ]);
        $unit = MedicalUnit::query()->create([
            'name' => 'Unidad Oncologica',
            'code' => 'UO-01',
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->post(route('operational.infusion-rooms.store'), [
                'unit' => $unit->id,
                'location' => 'Centro oncologico',
                'floor' => '1',
                'unit_number' => 'SI-01',
                'simultaneous_capacity' => 6,
                'responsible_name' => 'Responsable Oncologia',
                'status' => 'active',
                'schedule' => [
                    'monday' => ['enabled' => 1, 'start' => '07:00', 'end' => '15:00'],
                    'tuesday' => ['enabled' => 1, 'start' => '07:00', 'end' => '15:00'],
                ],
            ])
            ->assertRedirect(route('operational.dashboard', [
                'area' => 'oncology',
                'section' => 'infusion-room-catalog',
                'oncology_track' => 'infusions',
                'unit' => $unit->id,
            ]));

        $room = ProcedureArea::query()->where('unit_number', 'SI-01')->firstOrFail();
        $this->assertSame('infusion', $room->type);
        $this->assertSame(2, $room->schedules()->count());

        $this->actingAs($user)
            ->get(route('operational.dashboard', [
                'area' => 'oncology',
                'section' => 'infusion-rooms',
                'oncology_track' => 'infusions',
                'unit' => $unit->id,
            ]))
            ->assertOk()
            ->assertSeeInOrder(['Inicio', 'Catalogo'])
            ->assertDontSee('Programadas')
            ->assertDontSee('Pendientes')
            ->assertSee('N&uacute;mero de sala', false)
            ->assertSee('Citas hoy')
            ->assertSee('Citas esta semana')
            ->assertSee('Citas este mes')
            ->assertSee('Ocupaci&oacute;n mensual', false)
            ->assertSee('0 de 6 sillones')
            ->assertSee(route('operational.dashboard', [
                'area' => 'oncology',
                'section' => 'infusion-room-calendar',
                'oncology_track' => 'infusions',
                'unit' => $unit->id,
                'room' => $room->id,
                'calendar_month' => now()->format('Y-m'),
            ]))
            ->assertSee(route('operational.dashboard', [
                'area' => 'oncology',
                'section' => 'infusion-room-catalog',
                'oncology_track' => 'infusions',
                'unit' => $unit->id,
                'room' => $room->id,
            ]));

        $this->actingAs($user)
            ->get(route('operational.dashboard', [
                'area' => 'oncology',
                'section' => 'infusion-room-calendar',
                'oncology_track' => 'infusions',
                'unit' => $unit->id,
                'room' => $room->id,
            ]))
            ->assertOk()
            ->assertSee('Calendario de SI-01')
            ->assertSee('Volver a salas')
            ->assertSee('operational-oncology-filter is-active', false);

        $this->actingAs($user)
            ->get(route('operational.dashboard', [
                'area' => 'oncology',
                'section' => 'infusion-room-catalog',
                'unit' => $unit->id,
                'edit_room' => $room->id,
            ]))
            ->assertOk()
            ->assertSee('Editar sala de infusion')
            ->assertSee('SI-01');

        $this->actingAs($user)
            ->get(route('operational.dashboard', [
                'area' => 'oncology',
                'section' => 'infusion-room-catalog',
                'oncology_track' => 'infusions',
                'unit' => $unit->id,
                'room' => $room->id,
            ]))
            ->assertOk()
            ->assertSee('data-infusion-room-detail', false)
            ->assertSee('INFORMACI&Oacute;N GENERAL', false)
            ->assertSee('Responsable Oncologia');

        $this->actingAs($user)
            ->put(route('operational.infusion-rooms.update', $room), [
                'unit' => $unit->id,
                'location' => 'Centro oncologico actualizado',
                'floor' => '2',
                'unit_number' => 'SI-01',
                'simultaneous_capacity' => 8,
                'responsible_name' => 'Nueva Responsable',
                'status' => 'inactive',
                'schedule' => [
                    'friday' => ['enabled' => 1, 'start' => '08:00', 'end' => '16:00'],
                ],
            ])
            ->assertRedirect(route('operational.dashboard', [
                'area' => 'oncology',
                'section' => 'infusion-room-catalog',
                'oncology_track' => 'infusions',
                'unit' => $unit->id,
            ]));

        $room->refresh();
        $this->assertSame('Centro oncologico actualizado', $room->location);
        $this->assertSame(8, $room->simultaneous_capacity);
        $this->assertSame('inactive', $room->status);
        $this->assertSame(1, $room->schedules()->count());
        $this->assertDatabaseHas('procedure_area_schedules', [
            'procedure_area_id' => $room->id,
            'day_of_week' => 5,
        ]);
    }

    public function test_operational_patient_edit_button_opens_and_updates_the_patient(): void
    {
        $user = User::query()->create([
            'name' => 'Superadministrador Pacientes',
            'username' => 'superadmin.operational.patient',
            'email' => 'superadmin.operational.patient@test.local',
            'role' => 'superadmin',
            'module' => 'superadmin',
            'status' => 'active',
        ]);
        $unit = MedicalUnit::query()->create(['name' => 'Unidad Pacientes Operativos', 'status' => 'active']);
        $patient = Patient::query()->create([
            'first_name' => 'Arturo',
            'last_name' => 'Hernandez',
            'full_name' => 'Arturo Hernandez',
            'curp' => 'HEAA720314HMCRRR08',
            'status' => 'active',
            'metadata' => ['nss_federal' => '04967231458', 'state' => 'Mexico'],
        ]);
        ProviderRequest::query()->create([
            'patient_id' => $patient->id,
            'medical_unit_id' => $unit->id,
            'request_type' => 'chemo',
            'status' => 'requested',
            'requested_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('operational.patients.index', [
                'unit' => $unit->id,
                'edit_patient' => $patient->id,
            ]))
            ->assertOk()
            ->assertSee('Editar paciente')
            ->assertSee('HEAA720314HMCRRR08');

        $this->actingAs($user)
            ->patch(route('operational.patients.update', $patient), [
                'unit' => $unit->id,
                'first_name' => 'Arturo Javier',
                'last_name' => 'Hernandez',
                'curp' => 'HEAA720314HMCRRR08',
                'platform_number' => 'USR-HGCH-0001',
                'nss_federal' => '04967231458',
                'nss_estatal' => 'MEX-248391',
                'state' => 'Mexico',
            ])
            ->assertRedirect(route('operational.patients.index', ['unit' => $unit->id]));

        $patient->refresh();
        $this->assertSame('Arturo Javier Hernandez', $patient->full_name);
        $this->assertSame('USR-HGCH-0001', $patient->platform_number);
        $this->assertSame('MEX-248391', data_get($patient->metadata, 'nss_estatal'));
    }

    public function test_hospitalization_opens_its_calendar_carousel_and_filters_requests(): void
    {
        $user = User::query()->create([
            'name' => 'Superadministrador Hospitalizacion',
            'username' => 'superadmin.operational.hospitalization',
            'email' => 'superadmin.operational.hospitalization@test.local',
            'role' => 'superadmin',
            'module' => 'superadmin',
            'status' => 'active',
        ]);
        $unit = MedicalUnit::query()->create(['name' => 'Unidad Calendario Hospitalizacion', 'status' => 'active']);
        $patient = Patient::query()->create([
            'first_name' => 'Paciente',
            'last_name' => 'Hospitalario',
            'full_name' => 'Paciente Hospitalario',
            'curp' => 'PAHO900101HMCXXX01',
            'status' => 'active',
        ]);
        ProviderRequest::query()->create([
            'patient_id' => $patient->id,
            'medical_unit_id' => $unit->id,
            'external_id' => 'HOSP-NPT-001',
            'request_type' => 'npt',
            'status' => 'accepted',
            'requested_at' => Carbon::parse('2026-09-03 08:00'),
            'required_at' => Carbon::parse('2026-09-08 10:30'),
            'payload' => [
                'service' => 'Nutricion parenteral',
                'doctor' => 'Dra. Ana Ruiz',
                'ward' => 'Piso 2 - Cama 14',
                'diagnosis' => 'Desnutricion severa',
                'prescription_items' => [
                    ['medication_name' => 'Aminoacidos', 'dose' => '500 ml', 'diluent' => 'Solucion base', 'final_volume' => '1200 ml', 'route' => 'IV', 'duration' => '12 h', 'hour' => '10:30'],
                ],
            ],
        ]);
        ProviderRequest::query()->create([
            'patient_id' => $patient->id,
            'medical_unit_id' => $unit->id,
            'external_id' => 'HOSP-IMP-002',
            'request_type' => 'import',
            'status' => 'requested',
            'requested_at' => Carbon::parse('2026-09-04 09:00'),
            'required_at' => Carbon::parse('2026-09-10 12:00'),
            'payload' => ['service' => 'Medicamento especial', 'doctor' => 'Dr. Carlos Mendez'],
        ]);

        $response = $this->actingAs($user)->get(route('operational.dashboard', [
            'area' => 'nursing',
            'section' => 'calendar',
            'hospitalization_track' => 'all',
            'unit' => $unit->id,
            'calendar_month' => '2026-09',
        ]));

        $response->assertOk()
            ->assertSee('Calendario de Hospitalización')
            ->assertSee('operational-compact-carousel')
            ->assertDontSee('Nutrición Parenteral')
            ->assertDontSee('Medicamentos Especiales')
            ->assertDontSee('Calendario de servicios')
            ->assertDontSee('Agenda de servicios y solicitudes de Hospitalizacion')
            ->assertSee('data-calendar-mode="hospitalization"', false)
            ->assertSee('Ver solicitud')
            ->assertSee('data-mixture-detail-dialog', false)
            ->assertSee('Solicitud de mezcla hospitalaria');

        preg_match('/<script type="application\/json" data-service-calendar-events>(.*?)<\/script>/s', $response->getContent(), $matches);
        $events = collect(json_decode($matches[1] ?? '[]', true, flags: JSON_THROW_ON_ERROR));
        $this->assertCount(2, $events);
        $this->assertSame(['HOSP-IMP-002', 'HOSP-NPT-001'], $events->pluck('folio')->sort()->values()->all());
        $this->assertTrue($events->every(fn (array $event) => $event['detailLabel'] === 'Ver solicitud'));
        $nutritionEvent = $events->firstWhere('folio', 'HOSP-NPT-001');
        $this->assertSame('Desnutricion severa', $nutritionEvent['mixture']['patient']['diagnosis']);
        $this->assertSame('Aminoacidos', $nutritionEvent['mixture']['medications'][0]['medication']);

        $nutritionResponse = $this->actingAs($user)->get(route('operational.dashboard', [
            'area' => 'nursing',
            'section' => 'calendar',
            'hospitalization_track' => 'nutrition',
            'unit' => $unit->id,
            'calendar_month' => '2026-09',
        ]));
        preg_match('/<script type="application\/json" data-service-calendar-events>(.*?)<\/script>/s', $nutritionResponse->getContent(), $nutritionMatches);
        $nutritionEvents = collect(json_decode($nutritionMatches[1] ?? '[]', true, flags: JSON_THROW_ON_ERROR));

        $this->assertCount(1, $nutritionEvents);
        $this->assertSame('HOSP-NPT-001', $nutritionEvents->first()['folio']);
    }

    public function test_oncology_center_can_open_shared_request_form_and_save_it_in_preparation(): void
    {
        $user = User::query()->create([
            'name' => 'Operador Centro Oncologico',
            'username' => 'operador.centro.oncologico',
            'email' => 'operador.centro.oncologico@test.local',
            'role' => 'superadmin',
            'module' => 'superadmin',
            'status' => 'active',
        ]);
        $unit = MedicalUnit::query()->create(['name' => 'Unidad Solicitudes Oncologicas', 'status' => 'active']);
        $patient = Patient::query()->create([
            'first_name' => 'Elena',
            'last_name' => 'Ramirez Soto',
            'full_name' => 'Elena Ramirez Soto',
            'birth_date' => '1980-04-12',
            'sex' => 'female',
            'status' => 'active',
            'metadata' => ['medical_unit_id' => $unit->id, 'weight' => 64, 'height' => 164, 'body_surface' => 1.7],
        ]);

        $this->actingAs($user)
            ->get(route('operational.dashboard', [
                'area' => 'oncology',
                'section' => 'calendar',
                'oncology_track' => 'infusions',
                'unit' => $unit->id,
                'new_infusion' => 1,
            ]))
            ->assertOk()
            ->assertSee('doctor-oncology-request-modal is-operational', false)
            ->assertSee('Solicitud de mezcla oncol&oacute;gica', false)
            ->assertSee('Asignaci&oacute;n de sala de infusi&oacute;n', false)
            ->assertSee('name="save_mode" value="preparation"', false)
            ->assertSee('name="save_mode" value="scheduled"', false)
            ->assertSee('Agregar medicamento')
            ->assertSee('data-oncology-remove-medication', false)
            ->assertSee('Guardar y asignar sala');

        $this->actingAs($user)
            ->post(route('operational.service-requests.store'), [
                'workflow' => 'oncology_center',
                'save_mode' => 'preparation',
                'unit' => $unit->id,
                'request_type' => 'chemo',
                'patient_id' => $patient->id,
                'service' => 'Quimioterapia',
                'required_at' => '2026-09-07',
                'diagnosis' => 'Cancer de mama',
                'notes' => 'Validar esquema antes de asignar sala.',
                'priority' => 'routine',
                'oncology' => [
                    'request_date' => '2026-09-07',
                    'facility' => $unit->name,
                    'sex' => 'Femenino',
                    'age' => 46,
                    'weight' => 64,
                    'height' => 164,
                    'birth_date' => '1980-04-12',
                    'body_surface' => 1.7,
                    'doctor_name' => 'Dra. Andrea Castillo',
                    'professional_license' => 'CED-ONC-100',
                    'medications' => [[
                        'medication' => 'Paclitaxel',
                        'dose' => '300',
                        'diluents' => ['CS'],
                        'dilution_volume' => 500,
                        'boluses_per_day' => 1,
                        'infusion_minutes' => 180,
                        'delivery_dates' => ['2026-09-07'],
                    ]],
                ],
            ])
            ->assertRedirect(route('operational.dashboard', [
                'area' => 'oncology',
                'section' => 'services-preparation',
                'oncology_track' => 'infusions',
                'unit' => $unit->id,
            ]));

        $saved = ProviderRequest::query()->where('patient_id', $patient->id)->firstOrFail();
        $this->assertSame('draft', $saved->status);
        $this->assertSame('oncology_center', data_get($saved->payload, 'source'));
        $this->assertSame('Paclitaxel', data_get($saved->payload, 'mixture_medications.0.medication_name'));
        $this->assertNull(data_get($saved->payload, 'infusion_assignment'));

        $this->actingAs($user)
            ->get(route('operational.dashboard', [
                'area' => 'oncology',
                'section' => 'services-preparation',
                'unit' => $unit->id,
            ]))
            ->assertOk()
            ->assertSee($saved->external_id)
            ->assertSee('Elena Ramirez Soto')
            ->assertSee('data-oncology-workflow-table', false)
            ->assertSee('Ordenar por fecha');
    }

    public function test_oncology_center_can_save_room_assignment_and_schedule_calendar_event(): void
    {
        $user = User::query()->create([
            'name' => 'Coordinador de Infusiones',
            'username' => 'coordinador.infusiones',
            'email' => 'coordinador.infusiones@test.local',
            'role' => 'superadmin',
            'module' => 'superadmin',
            'status' => 'active',
        ]);
        $unit = MedicalUnit::query()->create(['name' => 'Unidad Agenda Oncologica', 'status' => 'active']);
        $patient = Patient::query()->create([
            'first_name' => 'Sofia',
            'last_name' => 'Mendoza Ruiz',
            'full_name' => 'Sofia Mendoza Ruiz',
            'birth_date' => '1978-03-10',
            'sex' => 'female',
            'status' => 'active',
            'metadata' => ['medical_unit_id' => $unit->id],
        ]);
        $room = ProcedureArea::query()->create([
            'medical_unit_id' => $unit->id,
            'type' => 'infusion',
            'location' => 'Primer piso',
            'unit_number' => 'SI-02',
            'simultaneous_capacity' => 2,
            'responsible_name' => 'Enf. Monica Reyes',
            'status' => 'active',
        ]);
        $room->schedules()->create([
            'day_of_week' => Carbon::MONDAY,
            'starts_at' => '08:00',
            'ends_at' => '17:00',
            'active' => true,
        ]);

        $this->actingAs($user)
            ->post(route('operational.service-requests.store'), [
                'workflow' => 'oncology_center',
                'save_mode' => 'scheduled',
                'unit' => $unit->id,
                'request_type' => 'chemo',
                'patient_id' => $patient->id,
                'service' => 'Quimioterapia',
                'required_at' => '2026-09-07',
                'diagnosis' => 'Linfoma',
                'priority' => 'routine',
                'oncology' => [
                    'request_date' => '2026-09-07',
                    'facility' => $unit->name,
                    'sex' => 'Femenino',
                    'age' => 48,
                    'weight' => 70,
                    'height' => 168,
                    'birth_date' => '1978-03-10',
                    'doctor_name' => 'Dr. Luis Herrera',
                    'medications' => [[
                        'medication' => 'Rituximab',
                        'dose' => '500',
                        'diluents' => ['CS'],
                        'dilution_volume' => 500,
                        'infusion_minutes' => 90,
                        'delivery_dates' => ['2026-09-07'],
                    ]],
                ],
                'assignment' => [
                    'procedure_area_id' => $room->id,
                    'seat' => $room->id.'-2',
                    'application_date' => '2026-09-07',
                    'starts_at' => '09:30',
                    'duration_minutes' => 90,
                    'nurse' => 'Enf. Monica Reyes',
                    'session_type' => 'Quimioterapia',
                    'notes' => 'Preparar sillon antes del ingreso.',
                ],
            ])
            ->assertRedirect(route('operational.dashboard', [
                'area' => 'oncology',
                'section' => 'calendar',
                'oncology_track' => 'infusions',
                'unit' => $unit->id,
                'calendar_month' => '2026-09',
            ]));

        $saved = ProviderRequest::query()->where('patient_id', $patient->id)->firstOrFail();
        $this->assertSame('requested', $saved->status);
        $this->assertSame($room->id, data_get($saved->payload, 'infusion_assignment.procedure_area_id'));
        $this->assertSame(2, data_get($saved->payload, 'infusion_assignment.seat_number'));
        $this->assertSame('11:00', data_get($saved->payload, 'infusion_assignment.ends_at'));
        $this->assertSame('scheduled', data_get($saved->payload, 'infusion_room_status'));

        $this->actingAs($user)
            ->get(route('operational.dashboard', [
                'area' => 'oncology',
                'section' => 'services-scheduled',
                'unit' => $unit->id,
            ]))
            ->assertOk()
            ->assertSee($saved->external_id)
            ->assertSee('SI-02')
            ->assertSee('Sillon o cama 2')
            ->assertSee('Agendada');

        $calendarResponse = $this->actingAs($user)->get(route('operational.dashboard', [
            'area' => 'oncology',
            'section' => 'calendar',
            'oncology_track' => 'infusions',
            'unit' => $unit->id,
            'calendar_month' => '2026-09',
        ]));
        preg_match('/<script type="application\/json" data-service-calendar-events>(.*?)<\/script>/s', $calendarResponse->getContent(), $matches);
        $events = collect(json_decode($matches[1] ?? '[]', true, flags: JSON_THROW_ON_ERROR));
        $event = $events->firstWhere('requestId', $saved->id);

        $this->assertNotNull($event);
        $this->assertSame('2026-09-07', $event['date']);
        $this->assertSame('09:30', $event['time']);
        $this->assertSame('scheduled', $event['status']);
    }

    public function test_oncology_calendar_assigns_available_infusion_room_and_blocks_capacity_overlap(): void
    {
        $user = User::query()->create([
            'name' => 'Superadministrador Calendario',
            'username' => 'superadmin.operational.calendar',
            'email' => 'superadmin.operational.calendar@test.local',
            'role' => 'superadmin',
            'module' => 'superadmin',
            'status' => 'active',
        ]);
        $unit = MedicalUnit::query()->create(['name' => 'Unidad Calendario Oncologico', 'status' => 'active']);
        $room = ProcedureArea::query()->create([
            'medical_unit_id' => $unit->id,
            'type' => 'infusion',
            'location' => 'Centro oncologico',
            'unit_number' => 'SI-CAL-01',
            'simultaneous_capacity' => 1,
            'status' => 'active',
        ]);
        $room->schedules()->create([
            'day_of_week' => 1,
            'starts_at' => '08:00',
            'ends_at' => '16:00',
            'active' => true,
        ]);
        $first = ProviderRequest::query()->create([
            'medical_unit_id' => $unit->id,
            'request_type' => 'chemo',
            'status' => 'requested',
            'requested_at' => now(),
            'payload' => ['service' => 'Quimioterapia'],
        ]);
        $second = ProviderRequest::query()->create([
            'medical_unit_id' => $unit->id,
            'request_type' => 'chemo',
            'status' => 'requested',
            'requested_at' => now(),
            'payload' => ['service' => 'Quimioterapia'],
        ]);
        $monday = Carbon::now()->next(Carbon::MONDAY)->toDateString();

        $this->actingAs($user)
            ->patch(route('operational.infusion-assignments.update', $first), [
                'procedure_area_id' => $room->id,
                'application_date' => $monday,
                'starts_at' => '09:00',
                'ends_at' => '11:00',
            ])
            ->assertRedirect(route('operational.dashboard', [
                'area' => 'oncology',
                'section' => 'calendar',
                'unit' => $unit->id,
            ]));

        $first->refresh();
        $this->assertSame($room->id, data_get($first->payload, 'infusion_assignment.procedure_area_id'));
        $this->assertSame('SI-CAL-01', data_get($first->payload, 'infusion_assignment.room_number'));
        $this->assertSame('scheduled', data_get($first->payload, 'infusion_room_status'));

        $this->actingAs($user)
            ->from(route('operational.dashboard', ['area' => 'oncology', 'section' => 'calendar', 'unit' => $unit->id]))
            ->patch(route('operational.infusion-assignments.update', $second), [
                'procedure_area_id' => $room->id,
                'application_date' => $monday,
                'starts_at' => '10:00',
                'ends_at' => '12:00',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('infusion_room');

        $this->assertNull(data_get($second->fresh()->payload, 'infusion_assignment'));

        $this->actingAs($user)
            ->get(route('operational.dashboard', [
                'area' => 'oncology',
                'section' => 'calendar',
                'unit' => $unit->id,
                'schedule_request' => $first->id,
            ]))
            ->assertOk()
            ->assertSee('data-operational-service-calendar', false)
            ->assertSee('data-service-calendar-filter="service"', false)
            ->assertSee('AGENDAR SALA DE INFUSI&Oacute;N', false)
            ->assertSee('SI-CAL-01')
            ->assertSee('Reprogramar');
    }

    public function test_oncology_mixture_calendar_creates_one_event_per_medication_and_updates_its_date(): void
    {
        $user = User::query()->create([
            'name' => 'Superadministrador Mezclas',
            'username' => 'superadmin.operational.mixes',
            'email' => 'superadmin.operational.mixes@test.local',
            'role' => 'superadmin',
            'module' => 'superadmin',
            'status' => 'active',
        ]);
        $unit = MedicalUnit::query()->create(['name' => 'Unidad Mezclas Oncologicas', 'status' => 'active']);
        $patient = Patient::query()->create([
            'first_name' => 'Maria',
            'last_name' => 'Gonzalez Lopez',
            'full_name' => 'Maria Gonzalez Lopez',
            'birth_date' => now()->subYears(52)->toDateString(),
            'sex' => 'female',
            'status' => 'active',
            'metadata' => ['weight' => 68, 'height' => 1.62, 'body_surface_area' => 1.72],
        ]);
        $providerRequest = ProviderRequest::query()->create([
            'patient_id' => $patient->id,
            'medical_unit_id' => $unit->id,
            'external_id' => 'INF-2025-09841',
            'request_type' => 'chemo',
            'status' => 'requested',
            'requested_at' => now(),
            'required_at' => Carbon::parse('2026-09-08 08:00'),
            'payload' => [
                'service' => 'Quimioterapia',
                'doctor' => 'Dr. Carlos Mendez',
                'diagnosis' => 'Cancer de mama',
                'infusion_assignment' => [
                    'application_date' => '2026-09-08',
                    'starts_at' => '08:00',
                    'ends_at' => '11:00',
                    'room_number' => 'SI-MIX-01',
                    'seat_number' => 1,
                    'status' => 'scheduled',
                ],
                'prescription_items' => [
                    ['medication_name' => 'Paclitaxel', 'dose' => '300 mg', 'diluent' => 'Sol. salina', 'final_volume' => '500 ml', 'route' => 'IV', 'infusion_duration' => '3 h', 'hour' => '08:00'],
                    ['medication_name' => 'Ondansetron', 'dose' => '8 mg', 'diluent' => 'Sol. salina', 'final_volume' => '50 ml', 'route' => 'IV', 'infusion_duration' => '15 min', 'hour' => '07:30'],
                ],
            ],
        ]);
        $unassignedRequest = ProviderRequest::query()->create([
            'patient_id' => $patient->id,
            'medical_unit_id' => $unit->id,
            'external_id' => 'INF-SIN-SALA',
            'request_type' => 'chemo',
            'status' => 'requested',
            'requested_at' => now(),
            'required_at' => Carbon::parse('2026-09-09 10:00'),
            'payload' => [
                'service' => 'Quimioterapia',
                'doctor' => 'Dr. Carlos Mendez',
                'prescription_items' => [
                    ['medication_name' => 'Fluorouracilo', 'dose' => '500 mg'],
                ],
            ],
        ]);

        $response = $this->actingAs($user)->get(route('operational.dashboard', [
            'area' => 'oncology',
            'section' => 'calendar',
            'oncology_track' => 'mixes',
            'unit' => $unit->id,
            'calendar_month' => '2026-09',
        ]));

        $response->assertOk()
            ->assertSee('data-mixture-detail-dialog', false)
            ->assertSee('Paclitaxel')
            ->assertSee('Ondansetron')
            ->assertSee('Ver mezcla');

        preg_match('/<script type="application\/json" data-service-calendar-events>(.*?)<\/script>/s', $response->getContent(), $matches);
        $events = json_decode($matches[1] ?? '[]', true, flags: JSON_THROW_ON_ERROR);
        $this->assertCount(2, $events);
        $this->assertNotContains($unassignedRequest->id, array_column($events, 'requestId'));
        $this->assertSame([0, 1], array_column($events, 'medicationIndex'));
        $this->assertSame(['2026-09-08', '2026-09-08'], array_column($events, 'date'));

        $newDate = '2026-10-06';
        $this->actingAs($user)
            ->patch(route('operational.mixture-schedules.update', $providerRequest), [
                'medication_index' => 1,
                'application_date' => $newDate,
                'oncology_track' => 'mixes',
            ])
            ->assertRedirect(route('operational.dashboard', [
                'area' => 'oncology',
                'section' => 'calendar',
                'oncology_track' => 'mixes',
                'unit' => $unit->id,
                'calendar_month' => '2026-10',
            ]));

        $providerRequest->refresh();
        $this->assertSame($newDate, data_get($providerRequest->payload, 'mixture_schedule.1.application_date'));
        $this->assertSame('Ondansetron', data_get($providerRequest->payload, 'mixture_schedule.1.medication_name'));

        $updatedResponse = $this->actingAs($user)->get(route('operational.dashboard', [
            'area' => 'oncology',
            'section' => 'calendar',
            'oncology_track' => 'mixes',
            'unit' => $unit->id,
            'calendar_month' => '2026-10',
        ]));
        preg_match('/<script type="application\/json" data-service-calendar-events>(.*?)<\/script>/s', $updatedResponse->getContent(), $updatedMatches);
        $updatedEvents = collect(json_decode($updatedMatches[1] ?? '[]', true, flags: JSON_THROW_ON_ERROR));

        $this->assertSame($newDate, $updatedEvents->firstWhere('medicationIndex', 1)['date']);
        $this->assertSame('2026-09-08', $updatedEvents->firstWhere('medicationIndex', 0)['date']);
    }

    public function test_doctor_oncology_request_stays_pending_until_an_infusion_room_is_assigned(): void
    {
        $user = User::query()->create([
            'name' => 'Coordinador Centro Oncologico',
            'username' => 'coordinador.pendientes',
            'email' => 'coordinador.pendientes@test.local',
            'role' => 'superadmin',
            'module' => 'superadmin',
            'status' => 'active',
        ]);
        $unit = MedicalUnit::query()->create(['name' => 'Hospital Solicitudes Medicas', 'status' => 'active']);
        $patient = Patient::query()->create([
            'first_name' => 'Elena',
            'last_name' => 'Ruiz Vega',
            'full_name' => 'Elena Ruiz Vega',
            'platform_number' => 'PAC-ONC-001',
            'birth_date' => '1984-02-14',
            'sex' => 'female',
            'status' => 'active',
        ]);
        $room = ProcedureArea::query()->create([
            'medical_unit_id' => $unit->id,
            'type' => 'infusion',
            'location' => 'Primer piso',
            'unit_number' => 'SI-MED-01',
            'simultaneous_capacity' => 2,
            'responsible_name' => 'Enf. Laura Gomez',
            'status' => 'active',
        ]);
        $room->schedules()->create([
            'day_of_week' => Carbon::TUESDAY,
            'starts_at' => '07:00',
            'ends_at' => '19:00',
            'active' => true,
        ]);
        $providerRequest = ProviderRequest::query()->create([
            'patient_id' => $patient->id,
            'medical_unit_id' => $unit->id,
            'external_id' => 'ONC-MED-0001',
            'request_type' => 'chemo',
            'status' => 'requested',
            'requested_at' => Carbon::parse('2026-09-02 08:30'),
            'required_at' => Carbon::parse('2026-09-08 09:00'),
            'payload' => [
                'source' => 'doctor_module',
                'doctor' => 'Dra. Laura Mendoza',
                'service' => 'Quimioterapia',
                'diagnosis' => 'Cancer de mama',
                'notes' => 'Validar signos vitales antes de iniciar.',
                'volume' => 500,
                'clinical_format' => [
                    'facility' => $unit->name,
                    'floor' => '2',
                    'bed' => '204',
                    'patient_identifier' => $patient->platform_number,
                    'sex' => 'Femenino',
                    'age' => 42,
                    'weight' => 68,
                    'height' => 162,
                    'birth_date' => '1984-02-14',
                    'body_surface' => 1.72,
                    'doctor_name' => 'Dra. Laura Mendoza',
                    'professional_license' => 'CED-ONC-2026',
                    'medications' => [[
                        'medication' => 'Paclitaxel',
                        'dose' => '300',
                        'diluents' => ['CS'],
                        'dilution_volume' => 500,
                        'boluses_per_day' => 1,
                        'infusion_minutes' => 120,
                        'delivery_dates' => ['2026-09-08'],
                    ]],
                ],
            ],
        ]);

        $pendingUrl = route('operational.dashboard', [
            'area' => 'oncology',
            'section' => 'services-pending',
            'oncology_track' => 'infusions',
            'unit' => $unit->id,
        ]);
        $this->actingAs($user)
            ->get($pendingUrl)
            ->assertOk()
            ->assertSee('Solicitudes pendientes')
            ->assertSee('Asignar sala')
            ->assertSee('Asignar')
            ->assertSee($providerRequest->external_id)
            ->assertSee('assign_request='.$providerRequest->id, false);

        $this->assertNull(data_get($providerRequest->fresh()->payload, 'infusion_assignment'));

        $calendarUrl = route('operational.dashboard', [
            'area' => 'oncology',
            'section' => 'calendar',
            'oncology_track' => 'infusions',
            'unit' => $unit->id,
            'calendar_month' => '2026-09',
        ]);
        $calendarBeforeAssignment = $this->actingAs($user)->get($calendarUrl);
        preg_match('/<script type="application\/json" data-service-calendar-events>(.*?)<\/script>/s', $calendarBeforeAssignment->getContent(), $calendarBeforeMatches);
        $this->assertNotContains(
            $providerRequest->id,
            collect(json_decode($calendarBeforeMatches[1] ?? '[]', true, flags: JSON_THROW_ON_ERROR))->pluck('requestId')->all(),
        );

        $this->actingAs($user)
            ->get($pendingUrl.'&assign_request='.$providerRequest->id)
            ->assertOk()
            ->assertSee('data-oncology-incoming-request', false)
            ->assertSee('Solicitud de mezcla oncol&oacute;gica', false)
            ->assertSee('Asignaci&oacute;n de sala de infusi&oacute;n', false)
            ->assertSee('Elena Ruiz Vega')
            ->assertSee('Dra. Laura Mendoza')
            ->assertSee('Paclitaxel')
            ->assertSee('SI-MED-01')
            ->assertSee('Guardar y asignar sala');

        $this->actingAs($user)
            ->patch(route('operational.infusion-assignments.update', $providerRequest), [
                'assignment' => [
                    'procedure_area_id' => $room->id,
                    'seat' => $room->id.'-1',
                    'application_date' => '2026-09-08',
                    'starts_at' => '09:00',
                    'duration_minutes' => 120,
                    'nurse' => 'Enf. Laura Gomez',
                    'session_type' => 'Quimioterapia',
                    'notes' => 'Sillon preparado.',
                ],
            ])
            ->assertRedirect(route('operational.dashboard', [
                'area' => 'oncology',
                'section' => 'services-scheduled',
                'oncology_track' => 'infusions',
                'unit' => $unit->id,
            ]));

        $providerRequest->refresh();
        $this->assertSame($room->id, data_get($providerRequest->payload, 'infusion_assignment.procedure_area_id'));
        $this->assertSame(1, data_get($providerRequest->payload, 'infusion_assignment.seat_number'));
        $this->assertSame('scheduled', data_get($providerRequest->payload, 'infusion_room_status'));
        $this->assertSame('Paclitaxel', data_get($providerRequest->payload, 'mixture_medications.0.medication_name'));

        $this->actingAs($user)->get($pendingUrl)->assertDontSee($providerRequest->external_id);
        $this->actingAs($user)
            ->get(route('operational.dashboard', [
                'area' => 'oncology',
                'section' => 'services-scheduled',
                'oncology_track' => 'infusions',
                'unit' => $unit->id,
            ]))
            ->assertOk()
            ->assertSee($providerRequest->external_id)
            ->assertSee('SI-MED-01')
            ->assertSee('Sillon o cama 1');

        $calendarAfterAssignment = $this->actingAs($user)->get($calendarUrl);
        preg_match('/<script type="application\/json" data-service-calendar-events>(.*?)<\/script>/s', $calendarAfterAssignment->getContent(), $calendarAfterMatches);
        $scheduledEvent = collect(json_decode($calendarAfterMatches[1] ?? '[]', true, flags: JSON_THROW_ON_ERROR))
            ->firstWhere('requestId', $providerRequest->id);
        $this->assertNotNull($scheduledEvent);
        $this->assertSame('scheduled', $scheduledEvent['status']);
    }
}

