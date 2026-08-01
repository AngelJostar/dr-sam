<?php

namespace Database\Seeders;

use App\Models\Authorization;
use App\Models\ChronicCondition;
use App\Models\Document;
use App\Models\Doctor;
use App\Models\Hospital;
use App\Models\Hospitalization;
use App\Models\HospitalizationDailyNote;
use App\Models\InsurancePolicy;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\MedicalUnit;
use App\Models\Medication;
use App\Models\MedicationDelivery;
use App\Models\Patient;
use App\Models\PatientDiagnosis;
use App\Models\Permission;
use App\Models\PharmacyProduct;
use App\Models\Provider;
use App\Models\Role;
use App\Models\Treatment;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class InsuranceHealthSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedRolesAndPermissions();
        $conditions = $this->seedChronicConditions();
        $medications = $this->seedMedications();
        $hospital = $this->seedHospital();
        $this->seedDemoFlow($conditions, $medications, $hospital);
    }

    private function seedRolesAndPermissions(): void
    {
        $permissions = [
            'insurance.view' => 'Consultar modulo',
            'insurance.manage_patients' => 'Gestionar pacientes',
            'insurance.manage_treatments' => 'Gestionar diagnosticos y tratamientos',
            'insurance.manage_deliveries' => 'Gestionar entregas',
            'insurance.manage_hospitalizations' => 'Gestionar hospitalizaciones',
            'insurance.manage_billing' => 'Gestionar facturacion',
            'insurance.manage_authorizations' => 'Gestionar autorizaciones',
            'insurance.manage_documents' => 'Gestionar documentos',
            'insurance.admin_users' => 'Administrar usuarios y roles',
        ];

        $permissionModels = [];

        foreach ($permissions as $key => $name) {
            $permissionModels[$key] = Permission::query()->updateOrCreate(
                ['key' => $key],
                ['name' => $name, 'module' => 'insurance_health'],
            );
        }

        $roles = [
            'insurance_admin' => ['Administrador', array_keys($permissions)],
            'medical_auditor' => ['Medico auditor', ['insurance.view', 'insurance.manage_treatments', 'insurance.manage_authorizations', 'insurance.manage_documents']],
            'patient_coordinator' => ['Coordinador de pacientes', ['insurance.view', 'insurance.manage_patients', 'insurance.manage_treatments', 'insurance.manage_documents']],
            'delivery_coordinator' => ['Coordinador de entregas', ['insurance.view', 'insurance.manage_deliveries', 'insurance.manage_documents']],
            'hospital_coordinator' => ['Coordinador hospitalario', ['insurance.view', 'insurance.manage_hospitalizations', 'insurance.manage_authorizations', 'insurance.manage_documents']],
            'billing' => ['Facturacion', ['insurance.view', 'insurance.manage_billing', 'insurance.manage_documents']],
            'read_only' => ['Consulta solo lectura', ['insurance.view']],
        ];

        foreach ($roles as $key => [$name, $rolePermissions]) {
            $role = Role::query()->updateOrCreate(
                ['key' => $key],
                [
                    'name' => $name,
                    'module' => 'insurance_health',
                    'description' => 'Rol operativo del modulo aseguradora salud.',
                    'is_system' => true,
                ],
            );

            $role->permissions()->sync(collect($rolePermissions)->map(fn (string $permission) => $permissionModels[$permission]->id)->all());
        }
    }

    private function seedChronicConditions(): array
    {
        $rows = [
            ['name' => 'Diabetes mellitus', 'default_cie10' => 'E11'],
            ['name' => 'Hipertension arterial', 'default_cie10' => 'I10'],
            ['name' => 'Insuficiencia renal cronica', 'default_cie10' => 'N18'],
            ['name' => 'Cancer', 'default_cie10' => 'C80'],
            ['name' => 'EPOC', 'default_cie10' => 'J44'],
            ['name' => 'Enfermedad cardiovascular', 'default_cie10' => 'I25'],
            ['name' => 'Artritis reumatoide', 'default_cie10' => 'M06'],
            ['name' => 'Esclerosis multiple', 'default_cie10' => 'G35'],
            ['name' => 'VIH', 'default_cie10' => 'B24'],
            ['name' => 'Otras', 'default_cie10' => null],
        ];

        $conditions = [];

        foreach ($rows as $row) {
            $conditions[$row['name']] = ChronicCondition::query()->updateOrCreate(
                ['name' => $row['name']],
                [
                    'default_cie10' => $row['default_cie10'],
                    'status' => 'active',
                ],
            );
        }

        return $conditions;
    }

    private function seedMedications(): array
    {
        $pharmacyProduct = PharmacyProduct::query()->first();
        $rows = [
            'Metformina' => ['active_substance' => 'Metformina', 'presentation' => 'Tableta 850 mg', 'default_dose' => '850 mg'],
            'Telmisartan' => ['active_substance' => 'Telmisartan', 'presentation' => 'Tableta 40 mg', 'default_dose' => '40 mg'],
            'Paracetamol Tableta' => ['active_substance' => 'Paracetamol', 'presentation' => 'Tableta 500 mg', 'default_dose' => '500 mg'],
        ];

        $medications = [];

        foreach ($rows as $name => $row) {
            $medications[$name] = Medication::query()->updateOrCreate(
                ['name' => $name],
                [
                    'pharmacy_product_id' => $name === 'Paracetamol Tableta' ? $pharmacyProduct?->id : null,
                    'active_substance' => $row['active_substance'],
                    'presentation' => $row['presentation'],
                    'default_dose' => $row['default_dose'],
                    'requires_authorization' => $name !== 'Paracetamol Tableta',
                    'status' => 'active',
                ],
            );
        }

        return $medications;
    }

    private function seedHospital(): Hospital
    {
        $unit = MedicalUnit::query()->where('external_id', 'demo-hospital-general-dr-sam')->first();
        $provider = Provider::query()->first();

        return Hospital::query()->updateOrCreate(
            ['name' => 'Hospital General Demo Dr. Sam'],
            [
                'medical_unit_id' => $unit?->id,
                'provider_id' => $provider?->id,
                'rfc' => 'HGD260101AB1',
                'network_type' => 'network',
                'address' => $unit?->address,
                'contact_phone' => '5555550303',
                'status' => 'active',
            ],
        );
    }

    private function seedDemoFlow(array $conditions, array $medications, Hospital $hospital): void
    {
        $patient = Patient::query()->where('platform_number', '100000001')->first();
        $doctor = Doctor::query()->first();
        $provider = Provider::query()->first();
        $admin = User::query()->where('username', 'aseguradora.admin')->first();

        if (! $patient) {
            return;
        }

        $patient->update([
            'rfc' => 'SAVC860412AB1',
            'address' => 'Av. Insurgentes Sur 100, Ciudad de Mexico',
            'primary_doctor_id' => $doctor?->id,
            'risk_level' => 'high',
            'enrolled_at' => Carbon::parse('2026-01-15'),
            'general_observations' => 'Paciente demo para seguimiento cronico-degenerativo aseguradora.',
            'updated_by' => $admin?->id,
        ]);

        InsurancePolicy::query()->updateOrCreate(
            ['policy_number' => 'GMM-DRSAM-0001'],
            [
                'patient_id' => $patient->id,
                'insurer_name' => 'Aseguradora Salud Integral',
                'plan_name' => 'GMM Corporativo Plus',
                'employer_name' => 'Empresa Demo SA de CV',
                'status' => 'active',
                'starts_at' => Carbon::parse('2026-01-01'),
                'ends_at' => Carbon::parse('2026-12-31'),
                'created_by' => $admin?->id,
                'updated_by' => $admin?->id,
            ],
        );

        $diagnosis = PatientDiagnosis::query()->updateOrCreate(
            ['patient_id' => $patient->id, 'condition_name' => 'Diabetes mellitus'],
            [
                'chronic_condition_id' => $conditions['Diabetes mellitus']->id,
                'diagnosed_at' => Carbon::parse('2024-03-10'),
                'cie10' => 'E11',
                'doctor_id' => $doctor?->id,
                'specialty' => 'Medicina interna',
                'indicated_treatment' => 'Control metabolico y apego farmacologico.',
                'follow_up_frequency' => 'Mensual',
                'required_studies' => 'Hemoglobina glucosilada, quimica sanguinea, EGO.',
                'administrative_notes' => 'Requiere autorizacion para terapia continua.',
                'status' => 'in_surveillance',
                'created_by' => $admin?->id,
                'updated_by' => $admin?->id,
            ],
        );

        $treatment = Treatment::query()->updateOrCreate(
            ['patient_id' => $patient->id, 'medication_name' => 'Metformina'],
            [
                'patient_diagnosis_id' => $diagnosis->id,
                'medication_id' => $medications['Metformina']->id,
                'prescribing_doctor_id' => $doctor?->id,
                'active_substance' => 'Metformina',
                'presentation' => 'Tableta 850 mg',
                'dose' => '850 mg',
                'frequency' => 'Cada 12 horas',
                'duration' => 'Tratamiento continuo',
                'starts_at' => Carbon::parse('2026-01-15'),
                'ends_at' => Carbon::parse('2026-12-31'),
                'requires_authorization' => true,
                'status' => 'active',
                'created_by' => $admin?->id,
                'updated_by' => $admin?->id,
            ],
        );

        MedicationDelivery::query()->updateOrCreate(
            ['patient_id' => $patient->id, 'treatment_id' => $treatment->id, 'scheduled_delivery_date' => Carbon::parse('2026-07-10')],
            [
                'medication_id' => $medications['Metformina']->id,
                'provider_id' => $provider?->id,
                'quantity_delivered' => 60,
                'covered_period' => '30 dias',
                'delivery_address' => $patient->address,
                'delivery_responsible' => 'Coordinacion entregas',
                'status' => 'pending',
                'created_by' => $admin?->id,
                'updated_by' => $admin?->id,
            ],
        );

        $hospitalization = Hospitalization::query()->updateOrCreate(
            ['patient_id' => $patient->id, 'authorization_number' => 'AUTH-HOSP-0001'],
            [
                'hospital_id' => $hospital->id,
                'doctor_id' => $doctor?->id,
                'hospital_name' => $hospital->name,
                'admitted_at' => Carbon::parse('2026-07-01 08:45:00'),
                'reason' => 'Descompensacion metabolica con requerimiento de vigilancia.',
                'admission_diagnosis' => 'Diabetes mellitus descompensada',
                'area' => 'hospitalizacion',
                'event_type' => 'complication',
                'status' => 'active',
                'stay_days' => 5,
                'procedures_summary' => 'Monitoreo metabolico y ajuste terapeutico.',
                'inpatient_medications' => 'Soluciones, insulina y medicamentos de soporte.',
                'studies_performed' => 'Laboratorio seriado.',
                'administrative_notes' => 'Pendiente documentacion completa de egreso.',
                'authorized_amount' => 85000,
                'created_by' => $admin?->id,
                'updated_by' => $admin?->id,
            ],
        );

        HospitalizationDailyNote::query()->updateOrCreate(
            ['hospitalization_id' => $hospitalization->id, 'note_date' => Carbon::parse('2026-07-05')],
            [
                'administrative_evolution' => 'Continua en vigilancia. Se solicita prorroga por estancia prolongada.',
                'general_clinical_status' => 'Estable con riesgo administrativo',
                'pending_studies' => 'Laboratorio de control',
                'pending_authorizations' => 'Prorroga de estancia',
                'prolonged_stay_risk' => true,
                'possible_discharge_date' => Carbon::parse('2026-07-07'),
                'captured_by' => $admin?->id,
                'created_by' => $admin?->id,
                'updated_by' => $admin?->id,
            ],
        );

        Authorization::query()->updateOrCreate(
            ['patient_id' => $patient->id, 'authorization_number' => 'AUTH-HOSP-0001'],
            [
                'hospitalization_id' => $hospitalization->id,
                'type' => 'hospitalization',
                'medical_request' => 'Ingreso hospitalario por descompensacion.',
                'justification' => 'Requiere vigilancia y ajuste terapeutico.',
                'requested_at' => Carbon::parse('2026-07-01'),
                'responded_at' => Carbon::parse('2026-07-01'),
                'status' => 'authorized',
                'authorized_amount' => 85000,
                'valid_until' => Carbon::parse('2026-07-08'),
                'created_by' => $admin?->id,
                'updated_by' => $admin?->id,
            ],
        );

        $invoice = Invoice::query()->updateOrCreate(
            ['fiscal_uuid' => '11111111-2222-3333-4444-555555555555'],
            [
                'hospitalization_id' => $hospitalization->id,
                'hospital_id' => $hospital->id,
                'provider_id' => $provider?->id,
                'provider_name' => $hospital->name,
                'provider_rfc' => $hospital->rfc,
                'invoice_number' => 'FAC-HOSP-0001',
                'invoice_date' => Carbon::parse('2026-07-04'),
                'concept' => 'Servicios hospitalarios',
                'subtotal' => 62000,
                'vat' => 9920,
                'withholdings' => 0,
                'total' => 71920,
                'currency' => 'MXN',
                'status' => 'in_review',
                'created_by' => $admin?->id,
                'updated_by' => $admin?->id,
            ],
        );

        InvoiceItem::query()->updateOrCreate(
            ['invoice_id' => $invoice->id, 'concept_type' => 'room'],
            [
                'description' => 'Habitacion y servicios hospitalarios',
                'quantity' => 5,
                'unit_price' => 12400,
                'subtotal' => 62000,
                'total' => 71920,
                'created_by' => $admin?->id,
                'updated_by' => $admin?->id,
            ],
        );

        Document::query()->updateOrCreate(
            ['patient_id' => $patient->id, 'name' => 'Poliza GMM demo'],
            [
                'document_type' => 'policy',
                'uploaded_by' => $admin?->id,
                'loaded_at' => now(),
                'expires_at' => Carbon::parse('2026-12-31'),
                'status' => 'current',
                'created_by' => $admin?->id,
                'updated_by' => $admin?->id,
            ],
        );
    }
}
