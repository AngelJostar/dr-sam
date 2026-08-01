<?php

namespace Tests\Feature;

use App\Models\MedicationDelivery;
use App\Models\Hospitalization;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InsuranceHealthModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_insurance_admin_can_open_native_dashboard(): void
    {
        $admin = User::query()->create([
            'name' => 'Admin Aseguradora',
            'username' => 'aseguradora.admin',
            'email' => 'aseguradora.admin@test.local',
            'role' => 'insurance_admin',
            'module' => 'insurance_health',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->get(route('insurance.dashboard'))
            ->assertOk()
            ->assertSee('native-shell')
            ->assertSee('insurance-health-native-screen')
            ->assertSee('insurance-health-native-sidebar')
            ->assertSee('insurance-health-native-workspace')
            ->assertSee('Control clinico-administrativo')
            ->assertDontSee('<iframe');
    }

    public function test_insurance_internal_views_use_native_visual_layer(): void
    {
        $admin = User::query()->create([
            'name' => 'Admin Aseguradora',
            'username' => 'aseguradora.admin',
            'email' => 'aseguradora.admin@test.local',
            'role' => 'insurance_admin',
            'module' => 'insurance_health',
            'status' => 'active',
        ]);

        $this->actingAs($admin);

        $this->get(route('insurance.patients.index'))
            ->assertOk()
            ->assertSee('insurance-health-native-sidebar')
            ->assertSee('insurance-section-hero')
            ->assertSee('insurance-filter-bar')
            ->assertSee('insurance-table-panel');

        $this->get(route('insurance.deliveries.index'))
            ->assertOk()
            ->assertSee('insurance-health-native-sidebar')
            ->assertSee('insurance-section-hero')
            ->assertSee('insurance-filter-bar')
            ->assertSee('insurance-table-panel');

        $this->get(route('insurance.hospitalizations.index'))
            ->assertOk()
            ->assertSee('insurance-health-native-sidebar')
            ->assertSee('insurance-section-hero')
            ->assertSee('insurance-filter-bar')
            ->assertSee('insurance-form-panel')
            ->assertSee('insurance-table-panel');

        $this->get(route('insurance.invoices.index'))
            ->assertOk()
            ->assertSee('insurance-health-native-sidebar')
            ->assertSee('insurance-health-native-workspace')
            ->assertSee('insurance-form-panel')
            ->assertSee('insurance-table-panel');

        $this->get(route('insurance.authorizations.index'))
            ->assertOk()
            ->assertSee('insurance-health-native-sidebar')
            ->assertSee('insurance-health-native-workspace')
            ->assertSee('insurance-form-panel')
            ->assertSee('insurance-table-panel');

        $this->get(route('insurance.documents.index'))
            ->assertOk()
            ->assertSee('insurance-health-native-sidebar')
            ->assertSee('insurance-health-native-workspace')
            ->assertSee('insurance-form-panel')
            ->assertSee('insurance-table-panel');

        $this->get(route('insurance.patients.create'))
            ->assertOk()
            ->assertSee('insurance-patient-form')
            ->assertSee('insurance-form-panel');

        $this->get(route('insurance.reports.index'))
            ->assertOk()
            ->assertSee('insurance-health-native-sidebar')
            ->assertSee('insurance-health-native-workspace')
            ->assertSee('insurance-report-panel');

        $patient = Patient::query()->create([
            'full_name' => 'Paciente Visual',
            'email' => 'paciente.visual@test.local',
            'status' => 'active',
            'risk_level' => 'medium',
        ]);

        $hospitalization = Hospitalization::query()->create([
            'patient_id' => $patient->id,
            'hospital_name' => 'Hospital Visual',
            'admitted_at' => now(),
            'area' => 'hospitalizacion',
            'event_type' => 'programmed',
            'status' => 'active',
            'authorized_amount' => 1000,
        ]);

        $this->get(route('insurance.patients.show', $patient))
            ->assertOk()
            ->assertSee('insurance-record-header')
            ->assertSee('insurance-record-summary')
            ->assertSee('insurance-detail-panel')
            ->assertSee('insurance-inline-form');

        $this->get(route('insurance.hospitalizations.show', $hospitalization))
            ->assertOk()
            ->assertSee('insurance-record-header')
            ->assertSee('insurance-detail-panel')
            ->assertSee('insurance-timeline-panel');

        $this->get(route('insurance.admin.users'))
            ->assertOk()
            ->assertSee('insurance-health-native-sidebar')
            ->assertSee('insurance-health-native-workspace')
            ->assertSee('insurance-admin-hero')
            ->assertSee('insurance-admin-panel')
            ->assertSee('insurance-permissions-panel');
    }

    public function test_insurance_admin_can_run_core_patient_flow(): void
    {
        $admin = User::query()->create([
            'name' => 'Admin Aseguradora',
            'username' => 'aseguradora.admin',
            'email' => 'aseguradora.admin@test.local',
            'role' => 'insurance_admin',
            'module' => 'insurance_health',
            'status' => 'active',
        ]);

        $this->actingAs($admin);

        $this->post('/insurance/patients', [
            'full_name' => 'Paciente Asegurado Demo',
            'birth_date' => '1980-05-10',
            'sex' => 'Femenino',
            'phone' => '5555551111',
            'email' => 'paciente.asegurado@test.local',
            'address' => 'Calle Salud 100',
            'status' => 'active',
            'risk_level' => 'high',
            'enrolled_at' => '2026-07-05',
            'policy_number' => 'POL-TEST-0001',
            'insurer_name' => 'Aseguradora Test',
            'plan_name' => 'Plan Plus',
        ])->assertRedirect();

        $patient = Patient::query()->where('email', 'paciente.asegurado@test.local')->firstOrFail();

        $this->post('/insurance/diagnoses', [
            'patient_id' => $patient->id,
            'condition_name' => 'Diabetes mellitus',
            'diagnosed_at' => '2026-01-01',
            'cie10' => 'E11',
            'status' => 'in_surveillance',
        ])->assertRedirect(route('insurance.patients.show', $patient));

        $this->post('/insurance/treatments', [
            'patient_id' => $patient->id,
            'medication_name' => 'Metformina',
            'active_substance' => 'Metformina',
            'presentation' => 'Tableta 850 mg',
            'dose' => '850 mg',
            'frequency' => 'Cada 12 horas',
            'duration' => 'Continuo',
            'starts_at' => '2026-07-05',
            'ends_at' => '2026-12-31',
            'requires_authorization' => '1',
            'status' => 'active',
        ])->assertRedirect(route('insurance.patients.show', $patient));

        $this->post('/insurance/deliveries', [
            'patient_id' => $patient->id,
            'quantity_delivered' => 60,
            'covered_period' => '30 dias',
            'scheduled_delivery_date' => '2026-07-10',
            'delivery_address' => 'Calle Salud 100',
            'status' => 'pending',
        ])->assertRedirect(route('insurance.patients.show', $patient));

        $delivery = MedicationDelivery::query()->where('patient_id', $patient->id)->firstOrFail();

        $this->patch("/insurance/deliveries/{$delivery->id}/status", [
            'status' => 'delivered',
            'actual_delivery_date' => '2026-07-10',
            'patient_acceptance' => 'Aceptado por paciente',
        ])->assertRedirect();

        $this->post('/insurance/hospitalizations', [
            'patient_id' => $patient->id,
            'hospital_name' => 'Hospital Test',
            'admitted_at' => '2026-07-05 08:00:00',
            'area' => 'hospitalizacion',
            'event_type' => 'emergency',
            'authorization_number' => 'AUTH-TEST-001',
            'status' => 'active',
            'authorized_amount' => 50000,
        ])->assertRedirect();

        $hospitalizationId = $patient->hospitalizations()->firstOrFail()->id;

        $this->post('/insurance/hospitalization-notes', [
            'hospitalization_id' => $hospitalizationId,
            'note_date' => '2026-07-05',
            'administrative_evolution' => 'Seguimiento administrativo inicial.',
            'general_clinical_status' => 'Estable',
        ])->assertRedirect(route('insurance.hospitalizations.show', $hospitalizationId));

        $this->post('/insurance/invoices', [
            'hospitalization_id' => $hospitalizationId,
            'provider_name' => 'Hospital Test',
            'provider_rfc' => 'HTE260101AB1',
            'invoice_number' => 'FAC-TEST-001',
            'fiscal_uuid' => 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee',
            'invoice_date' => '2026-07-05',
            'concept_type' => 'room',
            'subtotal' => 10000,
            'vat' => 1600,
            'withholdings' => 0,
            'total' => 11600,
            'currency' => 'MXN',
            'status' => 'received',
        ])->assertRedirect();

        $this->post('/insurance/documents', [
            'patient_id' => $patient->id,
            'name' => 'Poliza test',
            'document_type' => 'policy',
            'status' => 'current',
        ])->assertRedirect();

        $this->get(route('insurance.patients.show', $patient))->assertOk()->assertSee('Expediente del paciente');
        $this->get(route('insurance.reports.index'))->assertOk()->assertSee('Reportes operativos');

        $this->assertDatabaseHas('audit_logs', ['event' => 'insurance.patient.created']);
    }

    public function test_read_only_user_cannot_create_patient(): void
    {
        $user = User::query()->create([
            'name' => 'Consulta',
            'username' => 'aseguradora.consulta',
            'role' => 'read_only',
            'module' => 'insurance_health',
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->post('/insurance/patients', [
                'full_name' => 'No Permitido',
                'status' => 'active',
                'risk_level' => 'low',
                'policy_number' => 'POL-NOPE',
                'insurer_name' => 'Aseguradora Test',
            ])
            ->assertForbidden();
    }
}
