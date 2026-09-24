<?php

namespace Tests\Feature;

use App\Models\ContractedService;
use App\Models\Doctor;
use App\Models\Institution;
use App\Models\InventoryItem;
use App\Models\MedicalUnit;
use App\Models\MixtureIntegration;
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
use Illuminate\Support\Facades\Http;
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

        $operationalRequest = ProviderRequest::query()->create([
            'provider_id' => $provider->id,
            'patient_id' => $patient->id,
            'medical_unit_id' => $unit->id,
            'external_id' => 'REQ-OP-001',
            'request_type' => 'npt',
            'status' => 'requested',
            'requested_at' => now(),
            'required_at' => now()->addDay(),
        ]);
        MixtureIntegration::query()->create([
            'provider_request_id' => $operationalRequest->id,
            'local_external_id' => '10000000-0000-4000-8000-000000000001',
            'cbta_request_id' => 'CBTA-OP-001',
            'remote_status' => 'preparing',
            'sync_status' => 'synced',
            'metadata' => ['catalog_type' => 'npt'],
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

        ProviderRequest::query()->create([
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
            ->assertSee('REQ-OP-001')
            ->assertSee('detail_request='.$operationalRequest->id, false);

        $this->actingAs($user)
            ->get(route('operational.dashboard', ['section' => 'history', 'detail_request' => $operationalRequest->id]))
            ->assertOk()
            ->assertSee('Detalle de solicitud')
            ->assertSee('REQ-OP-001')
            ->assertSee('Paciente Operativo')
            ->assertSee('Componentes solicitados')
            ->assertSee('Seguimiento');

        $this->actingAs($user)
            ->get(route('operational.dashboard', ['section' => 'patients']))
            ->assertOk()
            ->assertSee('Catalogo de pacientes')
            ->assertSee('Nuevo paciente')
            ->assertSee('Alta de paciente')
            ->assertSee('Paciente')
            ->assertSee('Operativo')
            ->assertSee('USR-HGCH-001');

        $this->actingAs($user)
            ->get(route('operational.dashboard', ['section' => 'patients', 'modal' => 'patient']))
            ->assertOk()
            ->assertSee('operational-modal is-open')
            ->assertSee('Guardar paciente');

        $this->actingAs($user)
            ->get(route('operational.dashboard', ['area' => 'oncology']))
            ->assertOk()
            ->assertSee('Centro Oncologico')
            ->assertSee('QT-001')
            ->assertSee('Oncologia medica')
            ->assertSee('Protocolo por validar')
            ->assertDontSee('REQ-OP-001');

        $this->actingAs($user)
            ->get(route('operational.dashboard', ['area' => 'oncology', 'section' => 'services-pending']))
            ->assertOk()
            ->assertSee('data-authorization-area="oncology"', false)
            ->assertSee('Centro Onc. Pendiente');

        $this->actingAs($user)
            ->get(route('operational.dashboard', ['area' => 'oncology', 'section' => 'history']))
            ->assertOk()
            ->assertSee('Historial de solicitudes enviadas por hospitales')
            ->assertSee('QT-001');

        $this->actingAs($user)
            ->get(route('operational.dashboard', ['area' => 'oncology', 'section' => 'patients']))
            ->assertOk()
            ->assertSee('Catalogo de pacientes')
            ->assertSee('Ana Sofia')
            ->assertSee('USR-ONC-001');

        $this->actingAs($user)
            ->get(route('operational.dashboard', ['area' => 'oncology', 'section' => 'patients', 'modal' => 'patient']))
            ->assertOk()
            ->assertSee('operational-modal is-open')
            ->assertSee('Guardar paciente');

        foreach ([
            'services-pending' => 'Solicitudes pendientes',
            'services-history' => 'Historial de servicios',
            'service-create' => 'Nuevo servicio',
            'service-format' => 'SOLICITUD DE ONCOLÃ“GICOS',
            'mixes' => 'Mezclas programadas',
            'mix-history' => 'Historial de mezclas',
            'calendar' => 'data-operational-service-calendar',
            'infusion-rooms' => 'Inicio de salas de infusi',
        ] as $operationalSection => $expectedText) {
            $this->actingAs($user)
                ->get(route('operational.dashboard', ['area' => 'oncology', 'section' => $operationalSection]))
                ->assertOk()
                ->assertSee($expectedText);
        }

        $this->actingAs($user)
            ->post(route('operational.patients.store'), [
                'area' => 'nursing',
                'first_name' => 'Paciente Nueva',
                'last_name' => 'Operativa',
                'curp' => 'NUOP900101MMCXXX03',
                'state' => 'MÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â©xico',
            ])
            ->assertRedirect(route('operational.patients.index', ['unit' => $unit->id]));

        $this->assertDatabaseHas('patients', ['full_name' => 'Paciente Nueva Operativa']);

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
            ->assertSee('Solicitudes pendientes')
            ->assertSee('Historial de solicitudes')
            ->assertSee('REQ-OP-001')
            ->assertSee('QT-001')
            ->assertSee('data-authorization-area="oncology"', false)
            ->assertSee('Remision de entrega');

        $this->actingAs($user)
            ->get(route('operational.dashboard', ['area' => 'inpatient-pharmacy', 'section' => 'history']))
            ->assertOk()
            ->assertSee('Historial de solicitudes enviadas por hospitales');

        $this->actingAs($user)
            ->get(route('operational.dashboard', ['area' => 'inpatient-pharmacy', 'section' => 'patients']))
            ->assertOk()
            ->assertSee('Catalogo de pacientes')
            ->assertSee('Nuevo paciente')
            ->assertSee('Paciente')
            ->assertSee('Ana Sofia');

        $this->actingAs($user)
            ->get(route('operational.dashboard', ['area' => 'inpatient-pharmacy', 'section' => 'patients', 'modal' => 'patient']))
            ->assertOk()
            ->assertSee('operational-modal is-open')
            ->assertSee('Guardar paciente');
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
            'payload' => [
                'prescription_id' => $prescription->id,
                'prescription_code' => 'RX-OP-STATUS',
                'authorizations' => ['oncology' => 'approved', 'pharmacy' => 'approved'],
            ],
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

    public function test_inspected_mixture_cannot_be_sent_to_provider_again(): void
    {
        $user = User::query()->create([
            'name' => 'Super Admin Inspeccion',
            'username' => 'super.inspeccion',
            'email' => 'super.inspeccion@test.local',
            'role' => 'superadmin',
            'module' => 'superadmin',
            'status' => 'active',
        ]);
        $unit = MedicalUnit::query()->create(['name' => 'Unidad Inspeccion', 'status' => 'active']);
        $providerRequest = ProviderRequest::query()->create([
            'medical_unit_id' => $unit->id,
            'request_type' => 'npt',
            'status' => 'ready',
            'requested_at' => now(),
            'payload' => ['authorizations' => ['nursing' => 'approved', 'pharmacy' => 'approved']],
        ]);
        MixtureIntegration::query()->create([
            'provider_request_id' => $providerRequest->id,
            'local_external_id' => '20000000-0000-4000-8000-000000000001',
            'sync_status' => 'synced',
            'remote_status' => 'ready',
            'metadata' => ['catalog_type' => 'npt'],
        ]);

        $this->actingAs($user)
            ->patch(route('operational.provider-requests.status', $providerRequest), [
                'status' => 'accepted',
                'provider_name' => 'Prodifem',
            ])
            ->assertStatus(422);

        $this->assertSame('ready', $providerRequest->fresh()->status);
        $this->assertNull(data_get($providerRequest->fresh()->payload, 'provider_assignment'));
    }

    public function test_authorizations_are_independent_and_restricted_to_the_operational_area(): void
    {
        config(['cbta.base_url' => 'http://cbta.test', 'cbta.token' => 'test-token']);
        Http::fake([
            'http://cbta.test/api/internal/v1/mixture-requests/prevalidate' => Http::response(['data' => [
                'valid' => true,
                'catalog_type' => 'npt',
                'catalog_version' => 'npt-final-v2',
                'medical_unit' => ['external_code' => 'CBTA-AUTH-01'],
                'items' => [['product_code' => 'GLUCOSE', 'presentation_code' => 'GLUCOSE-500', 'quantity' => 100, 'unit' => 'ml']],
                'errors' => [],
            ]]),
            'http://cbta.test/api/internal/v1/mixture-requests' => Http::response(['data' => [
                'request_id' => 'CBTA-AUTHORIZED-1',
                'status' => 'received',
                'catalog_type' => 'npt',
                'catalog_version' => 'npt-final-v2',
                'documents' => [],
            ]], 201),
        ]);

        $unit = MedicalUnit::query()->create(['name' => 'Unidad Autorizaciones', 'cbta_external_code' => 'CBTA-AUTH-01', 'status' => 'active']);
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
            'payload' => [
                'authorizations' => ['operational' => 'pending', 'pharmacy' => 'pending'],
                'authorization_requirements' => ['operational', 'pharmacy'],
                'integration_items' => [['product_code' => 'GLUCOSE', 'presentation_code' => 'GLUCOSE-500', 'quantity' => 100, 'unit' => 'ml']],
            ],
        ]);
        $integration = MixtureIntegration::query()->create([
            'provider_request_id' => $providerRequest->id,
            'local_external_id' => '10000000-0000-4000-8000-000000000099',
            'sync_status' => 'awaiting_authorizations',
            'catalog_version' => 'npt-initial-v1',
            'metadata' => ['catalog_type' => 'npt'],
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
        $this->assertSame('awaiting_authorizations', $integration->fresh()->sync_status);
        Http::assertNotSent(fn ($request): bool => $request->url() === 'http://cbta.test/api/internal/v1/mixture-requests');

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
        $this->assertSame('synced', $integration->fresh()->sync_status);
        $this->assertSame('CBTA-AUTHORIZED-1', $integration->fresh()->cbta_request_id);
        $this->assertSame('npt-final-v2', $integration->fresh()->catalog_version);
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

    public function test_spanish_oncology_operator_can_approve_oncology_authorization(): void
    {
        $unit = MedicalUnit::query()->create(['name' => 'Unidad Oncologia', 'status' => 'active']);
        $area = OperationalArea::query()->create(['key' => 'oncologia', 'label' => 'Centro Oncologico']);
        $user = User::query()->create([
            'name' => 'Operador Centro Oncologico',
            'username' => 'op.oncologia.test',
            'email' => 'op.oncologia.test@example.test',
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
            'external_id' => 'ONC-AUTH-001',
            'request_type' => 'chemo',
            'status' => 'requested',
            'requested_at' => now(),
            'payload' => ['authorizations' => ['oncology' => 'pending', 'pharmacy' => 'pending']],
        ]);

        $this->actingAs($user)
            ->get(route('operational.dashboard', ['area' => 'oncology', 'section' => 'services-pending']))
            ->assertOk()
            ->assertSee('data-authorization-toggle="true"', false)
            ->assertSee('name="authorization" value="oncology"', false);

        $this->actingAs($user)
            ->patch(route('operational.provider-requests.authorizations.update', $providerRequest), [
                'authorization' => 'oncology',
                'operating_area' => 'oncology',
                'status' => 'approved',
            ])
            ->assertRedirect();

        $this->assertSame('approved', data_get($providerRequest->fresh()->payload, 'authorizations.oncology'));

        $this->actingAs($user)
            ->get(route('operational.dashboard', ['area' => 'inpatient-pharmacy', 'section' => 'pending']))
            ->assertOk()
            ->assertDontSee('data-authorization-toggle="true"', false);

        $this->actingAs($user)
            ->patch(route('operational.provider-requests.authorizations.update', $providerRequest), [
                'authorization' => 'pharmacy',
                'operating_area' => 'inpatient-pharmacy',
                'status' => 'approved',
            ])
            ->assertForbidden();
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
        $this->assertSame('nursing', data_get($providerRequest->payload, 'cancellation.area'));
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
                'section' => 'infusion-room-catalog',
                'oncology_track' => 'infusions',
                'unit' => $unit->id,
                'edit_room' => $room->id,
            ]))
            ->assertOk()
            ->assertSee('Editar sala de infusion')
            ->assertSee('SI-01');

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
            ->assertRedirect();

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
            ->get(route('operational.dashboard', [
                'area' => 'oncology',
                'section' => 'patients',
                'unit' => $unit->id,
                'edit_patient' => $patient->id,
            ]))
            ->assertOk()
            ->assertSee('Editar paciente')
            ->assertSee('HEAA720314HMCRRR08');

        $this->actingAs($user)
            ->patch(route('operational.patients.update', $patient), [
                'area' => 'oncology',
                'first_name' => 'Arturo Javier',
                'last_name' => 'Hernandez',
                'curp' => 'HEAA720314HMCRRR08',
                'platform_number' => 'USR-HGCH-0001',
                'nss_federal' => '04967231458',
                'nss_estatal' => 'MEX-248391',
                'state' => 'Mexico',
            ])
            ->assertRedirect(route('operational.patients.index'));

        $patient->refresh();
        $this->assertSame('Arturo Javier Hernandez', $patient->full_name);
        $this->assertSame('USR-HGCH-0001', $patient->platform_number);
        $this->assertSame('MEX-248391', data_get($patient->metadata, 'nss_estatal'));
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
            ->assertSee('AGENDAR SALA DE INFUSI&Oacute;N', false)
            ->assertSee('SI-CAL-01')
            ->assertSee('Reprogramar');
    }
}

