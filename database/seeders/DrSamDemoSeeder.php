<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\ClinicalRecord;
use App\Models\ContractedService;
use App\Models\Doctor;
use App\Models\Institution;
use App\Models\InventoryItem;
use App\Models\MedicalUnit;
use App\Models\MedicationCatalogItem;
use App\Models\MessengerProfile;
use App\Models\OperationalArea;
use App\Models\OperationalProfile;
use App\Models\Patient;
use App\Models\PatientOrder;
use App\Models\PatientOrderItem;
use App\Models\PharmacyProduct;
use App\Models\PlatformModule;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\Provider;
use App\Models\ProviderRequest;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class DrSamDemoSeeder extends Seeder
{
    public function run(): void
    {
        $users = $this->seedUsers();
        $institutions = $this->seedInstitutions($users);
        $unit = $this->seedMedicalUnit($institutions['inst-portal']);
        $services = $this->seedServices($institutions['inst-portal'], $unit);
        $this->seedOperationalAreas($users, $unit);
        $doctor = $this->seedDoctor($users['jimmy.carter'], $unit);
        $patient = $this->seedPatient($users['paciente']);
        $provider = $this->seedProviders($users);
        $product = $this->seedPharmacy($institutions['inst-portal'], $unit);
        $this->seedCareFlow($doctor, $patient, $product);
        $this->seedProviderFlow($provider, $patient, $unit, $services['nutricion-parenteral']);
        $this->seedMessenger($users['mensajero']);
        $this->seedModules();
    }

    private function seedUsers(): array
    {
        $users = [];

        foreach (config('drsam.demo_users', []) as $demoUser) {
            $users[$demoUser['username']] = User::query()->updateOrCreate(
                ['username' => $demoUser['username']],
                [
                    'name' => $demoUser['name'],
                    'email' => $demoUser['username'].'@demo.drsam.local',
                    'email_verified_at' => now(),
                    'password' => Hash::make('Demo2026'),
                    'role' => $demoUser['role'],
                    'module' => $demoUser['module'],
                    'status' => 'active',
                    'is_demo' => true,
                    'passwordless_review' => true,
                    'metadata' => [
                        'external_id' => $demoUser['external_id'] ?? null,
                    ],
                ],
            );
        }

        return $users;
    }

    private function seedInstitutions(array $users): array
    {
        $rows = [
            'inst-portal' => ['name' => 'Imss Bienestar Estado de Mexico', 'owner' => 'institucion'],
            'inst-angeles' => ['name' => 'Operadora de Hospitales Angeles', 'owner' => 'angeles'],
            'inst-imss-cdmx' => ['name' => 'Imss Bienestar Ciudad de Mexico', 'owner' => 'imss.cdmx'],
            'inst-t2' => ['name' => 'IMSS Bienestar Estado de Mexico', 'owner' => 'imss.bienestar'],
        ];

        $institutions = [];

            foreach ($rows as $externalId => $row) {
                $institutions[$externalId] = Institution::query()->updateOrCreate(
                    ['external_id' => $externalId],
                [
                    'owner_user_id' => $users[$row['owner']]->id ?? null,
                    'name' => $row['name'],
                    'type' => 'institution',
                    'status' => 'active',
                    'metadata' => ['source' => 'demo'],
                ],
            );
        }

        return $institutions;
    }

    private function seedMedicalUnit(Institution $institution): MedicalUnit
    {
        return MedicalUnit::query()->updateOrCreate(
            ['external_id' => 'demo-hospital-general-dr-sam'],
            [
                'institution_id' => $institution->id,
                'code' => 'DRSAM-DEMO',
                'clues' => 'DRSAM000001',
                'name' => 'Hospital General Demo Dr. Sam',
                'city' => 'Ciudad de Mexico',
                'municipality' => 'Benito Juarez',
                'state' => 'Ciudad de Mexico',
                'entity' => 'Ciudad de Mexico',
                'type' => 'Hospital General',
                'typology' => 'Hospital General',
                'care_level' => 'Segundo Nivel',
                'address' => 'Unidad demo para revision de flujos',
                'beds' => 80,
                'unit_username' => 'unidad.demo',
                'status' => 'active',
                'partidas' => ['1', '13'],
                'subpartidas' => ['Nutricion parenteral', 'Mezclas oncologicas'],
                'source_sheets' => ['Seeder demo'],
                'metadata' => ['review_scope' => true],
            ],
        );
    }

    private function seedServices(Institution $institution, MedicalUnit $unit): array
    {
        $serviceRows = [
            'nutricion-parenteral' => ['category' => 'Farmaceuticos', 'specialty' => 'Central de Mezclas de Nutricion Parenteral', 'name' => 'Nutricion parenteral'],
            'quimioterapias' => ['category' => 'Farmaceuticos', 'specialty' => 'Central de Mezclas Oncologicas', 'name' => 'Quimioterapias'],
            'medicamentos-importacion' => ['category' => 'Farmaceuticos', 'specialty' => 'Medicamentos de importacion', 'name' => 'Importacion de medicamentos'],
            'consulta-externa' => ['category' => 'Atencion medica', 'specialty' => 'Consulta Externa', 'name' => 'Consulta externa'],
            'farmacia-digital' => ['category' => 'Farmacia', 'specialty' => 'Farmacia Digital', 'name' => 'Pedido y entrega de medicamentos'],
        ];

        $services = [];

            foreach ($serviceRows as $externalId => $row) {
            $service = Service::query()->updateOrCreate(
                ['external_id' => $externalId],
                [
                    'category' => $row['category'],
                    'specialty' => $row['specialty'],
                    'name' => $row['name'],
                    'status' => 'active',
                ],
            );

            ContractedService::query()->updateOrCreate(
                [
                    'institution_id' => $institution->id,
                    'medical_unit_id' => $unit->id,
                    'service_id' => $service->id,
                ],
                [
                    'contract_number' => 'DEMO-2026-'.$service->id,
                    'status' => 'active',
                    'starts_at' => Carbon::parse('2026-01-01'),
                    'ends_at' => Carbon::parse('2026-12-31'),
                    'metadata' => ['review' => true],
                ],
            );

                $services[$externalId] = $service;
        }

        return $services;
    }

    private function seedOperationalAreas(array $users, MedicalUnit $unit): void
    {
        $areas = [
            'enfermeria' => ['label' => 'Enfermeria', 'user' => 'op.enfermeria', 'permissions' => ['view-history', 'view-detail', 'update-nursing', 'download-reports']],
            'farmacia' => ['label' => 'Farmacia intrahospitalaria', 'user' => 'op.farmacia', 'permissions' => ['view-history', 'view-detail', 'update-pharmacy-npt', 'update-pharmacy-chemo', 'download-reports']],
            'farmacia-externa' => ['label' => 'Farmacia Externa', 'user' => 'op.farmacia.externa', 'permissions' => ['view-history', 'view-detail', 'manage-external-pharmacy', 'download-reports']],
            'oncologia' => ['label' => 'Centro Oncologico', 'user' => 'op.oncologia', 'permissions' => ['view-history', 'view-detail', 'update-oncology', 'download-reports']],
            'consulta' => ['label' => 'Consulta Externa', 'user' => 'op.consulta', 'permissions' => ['view-history', 'view-detail', 'update-outpatient', 'download-reports']],
        ];

        foreach ($areas as $key => $row) {
            $area = OperationalArea::query()->updateOrCreate(
                ['key' => $key],
                [
                    'label' => $row['label'],
                    'role_label' => str_contains($key, 'farmacia') ? 'Responsable de Area' : 'Operador',
                    'default_permissions' => $row['permissions'],
                ],
            );

            OperationalProfile::query()->updateOrCreate(
                ['user_id' => $users[$row['user']]->id, 'operational_area_id' => $area->id],
                [
                    'medical_unit_id' => $unit->id,
                    'role_label' => $area->role_label,
                    'status' => 'active',
                    'permissions' => $row['permissions'],
                    'metadata' => ['source_area' => $row['label']],
                ],
            );
        }
    }

    private function seedDoctor(User $user, MedicalUnit $unit): Doctor
    {
        return Doctor::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'medical_unit_id' => $unit->id,
                'external_id' => 'doc-carter',
                'full_name' => 'Dr. Carter Jimmy',
                'professional_license' => 'CED-DEMO-2026',
                'specialty' => 'Medicina interna',
                'subspecialty' => 'Nutricion clinica',
                'service_name' => 'Nutricion parenteral',
                'status' => 'active',
                'verified_at' => now(),
                'metadata' => ['review' => true],
            ],
        );
    }

    private function seedPatient(User $user): Patient
    {
        return Patient::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'platform_number' => '100000001',
                'first_name' => 'Claudia Beatriz',
                'last_name' => 'Salinas Vega',
                'full_name' => 'Claudia Beatriz Salinas Vega',
                'birth_date' => Carbon::parse('1986-04-12'),
                'sex' => 'Femenino',
                'curp' => null,
                'phone' => '5555550101',
                'email' => $user->email,
                'status' => 'active',
                'email_verified_at' => now(),
                'profile_completed_at' => now(),
                'metadata' => ['review' => true],
            ],
        );
    }

    private function seedProviders(array $users): Provider
    {
        $rows = [
            'proveedor' => ['name' => 'Proveedor NPT', 'type' => 'npt'],
            'proveedor.importacion' => ['name' => 'Proveedor Importacion de Medicamentos', 'type' => 'import'],
        ];

        $first = null;

        foreach ($rows as $username => $row) {
            $provider = Provider::query()->updateOrCreate(
                ['user_id' => $users[$username]->id],
                [
                    'name' => $row['name'],
                    'provider_type' => $row['type'],
                    'status' => 'active',
                    'metadata' => ['review' => true],
                ],
            );

            $first ??= $provider;
        }

        return $first;
    }

    private function seedPharmacy(Institution $institution, MedicalUnit $unit): PharmacyProduct
    {
        MedicationCatalogItem::query()->updateOrCreate(
            ['external_id' => 'med-cnis-010-000-0104-00'],
            [
                'institution_id' => $institution->id,
                'cnis' => '010.000.0104.00',
                'name' => 'Paracetamol',
                'generic_name' => 'Paracetamol',
                'therapeutic_group' => 'Analgesia',
                'description' => 'Tableta 500 mg, envase con 10 tabletas',
                'presentation' => 'Tableta',
                'requires_prescription' => false,
                'status' => 'active',
            ],
        );

        $product = PharmacyProduct::query()->updateOrCreate(
            ['external_id' => 'imss-bienestar-010-000-0104-00'],
            [
                'cnis' => '010.000.0104.00',
                'name' => 'Paracetamol Tableta',
                'generic_name' => 'Paracetamol',
                'commercial_name' => 'No especificada',
                'dose' => '500',
                'unit' => 'MG',
                'dosage_form' => 'Tableta',
                'presentation' => 'Envase con 10 tabletas',
                'requires_prescription' => false,
                'controlled' => false,
                'cold_chain' => false,
                'sector_health' => true,
                'price' => 352,
                'status' => 'active',
            ],
        );

        InventoryItem::query()->updateOrCreate(
            ['pharmacy_product_id' => $product->id, 'medical_unit_id' => $unit->id, 'warehouse' => 'Farmacia demo'],
            [
                'lot' => 'L-DRSAM-001',
                'quantity' => 70,
                'status' => 'available',
            ],
        );

        return $product;
    }

    private function seedCareFlow(Doctor $doctor, Patient $patient, PharmacyProduct $product): void
    {
        Appointment::query()->updateOrCreate(
            ['patient_id' => $patient->id, 'doctor_id' => $doctor->id, 'starts_at' => Carbon::parse('2026-07-01 10:00:00')],
            [
                'medical_unit_id' => $doctor->medical_unit_id,
                'specialty' => 'Medicina interna',
                'modality' => 'video',
                'status' => 'scheduled',
                'ends_at' => Carbon::parse('2026-07-01 10:30:00'),
                'reason' => 'Consulta demo de seguimiento',
            ],
        );

        ClinicalRecord::query()->updateOrCreate(
            ['patient_id' => $patient->id, 'record_type' => 'summary'],
            [
                'doctor_id' => $doctor->id,
                'title' => 'Resumen clinico demo',
                'summary' => 'Registro semilla para validar el historial clinico.',
                'payload' => ['source' => 'seeder'],
                'recorded_at' => now(),
            ],
        );

        $prescription = Prescription::query()->updateOrCreate(
            ['code' => 'RX-DEMO-0001'],
            [
                'patient_id' => $patient->id,
                'doctor_id' => $doctor->id,
                'status' => 'active',
                'issued_at' => now(),
                'notes' => 'Receta demo para flujo paciente-farmacia.',
            ],
        );

        PrescriptionItem::query()->updateOrCreate(
            ['prescription_id' => $prescription->id, 'medication_name' => $product->name],
            [
                'dose' => '500 mg',
                'frequency' => 'Cada 8 horas',
                'duration' => '3 dias',
                'instructions' => 'Tomar con alimentos.',
            ],
        );

        $order = PatientOrder::query()->updateOrCreate(
            ['order_number' => 'PED-DEMO-0001'],
            [
                'patient_id' => $patient->id,
                'channel' => 'digital',
                'status' => 'created',
                'subtotal' => 352,
                'total' => 352,
                'ordered_at' => now(),
            ],
        );

        PatientOrderItem::query()->updateOrCreate(
            ['patient_order_id' => $order->id, 'pharmacy_product_id' => $product->id],
            [
                'product_name' => $product->name,
                'quantity' => 1,
                'unit_price' => 352,
                'total' => 352,
            ],
        );
    }

    private function seedProviderFlow(Provider $provider, Patient $patient, MedicalUnit $unit, Service $service): void
    {
        ProviderRequest::query()->updateOrCreate(
            ['external_id' => 'npt-demo-0001'],
            [
                'provider_id' => $provider->id,
                'patient_id' => $patient->id,
                'medical_unit_id' => $unit->id,
                'request_type' => 'npt',
                'status' => 'requested',
                'requested_at' => now(),
                'required_at' => now()->addDay(),
                'payload' => [
                    'service' => $service->name,
                    'source' => 'seeder',
                    'review' => true,
                ],
            ],
        );
    }

    private function seedMessenger(User $user): void
    {
        MessengerProfile::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'external_id' => 'messenger-luis',
                'phone' => '5555550202',
                'vehicle' => 'Unidad refrigerada demo',
                'status' => 'active',
                'metadata' => ['review' => true],
            ],
        );
    }

    private function seedModules(): void
    {
        foreach (config('drsam.modules', []) as $key => $module) {
            PlatformModule::query()->updateOrCreate(
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
}
