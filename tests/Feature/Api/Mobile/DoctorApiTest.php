<?php

namespace Tests\Feature\Api\Mobile;

use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\ClinicalRecord;
use App\Models\Doctor;
use App\Models\MedicalUnit;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\ProviderRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DoctorApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_doctor_only_lists_related_patients_and_own_agenda(): void
    {
        [$user, $doctor] = $this->doctorAccount('doctor-owner');
        [, $otherDoctor] = $this->doctorAccount('doctor-other');
        $related = Patient::query()->create(['full_name' => 'Paciente relacionado', 'primary_doctor_id' => $doctor->id, 'status' => 'active']);
        $unrelated = Patient::query()->create(['full_name' => 'Paciente ajeno', 'primary_doctor_id' => $otherDoctor->id, 'status' => 'active']);
        $ownAppointment = Appointment::query()->create(['patient_id' => $related->id, 'doctor_id' => $doctor->id, 'status' => 'scheduled', 'starts_at' => now()->addHour()]);
        $foreignAppointment = Appointment::query()->create(['patient_id' => $unrelated->id, 'doctor_id' => $otherDoctor->id, 'status' => 'scheduled', 'starts_at' => now()->addHour()]);
        $token = $this->mobileToken($user);

        $this->withToken($token)->getJson('/api/mobile/v1/doctor/patients')
            ->assertOk()
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.id', $related->id)
            ->assertJsonMissing(['id' => $unrelated->id]);

        $this->withToken($token)->getJson('/api/mobile/v1/doctor/appointments')
            ->assertOk()
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.id', $ownAppointment->id);

        $this->withToken($token)->getJson('/api/mobile/v1/doctor/appointments/'.$ownAppointment->id)
            ->assertOk()
            ->assertJsonPath('data.appointment.patient.id', $related->id);

        $this->withToken($token)->getJson('/api/mobile/v1/doctor/appointments/'.$foreignAppointment->id)
            ->assertNotFound();

        $this->withToken($token)->patchJson('/api/mobile/v1/doctor/appointments/'.$ownAppointment->id, ['status' => 'in_progress'])
            ->assertOk()
            ->assertJsonPath('data.appointment.status', 'in_progress');

        $this->withToken($token)->patchJson('/api/mobile/v1/doctor/appointments/'.$foreignAppointment->id, ['status' => 'in_progress'])
            ->assertNotFound();

        $this->assertDatabaseHas('audit_logs', ['event' => 'mobile.doctor.appointment.updated', 'auditable_id' => $ownAppointment->id]);
    }

    public function test_doctor_can_view_related_patient_and_create_audited_note(): void
    {
        [$user, $doctor] = $this->doctorAccount('doctor-note');
        $patient = Patient::query()->create(['full_name' => 'Paciente nota', 'primary_doctor_id' => $doctor->id, 'status' => 'active']);
        $other = Patient::query()->create(['full_name' => 'Paciente sin relación', 'status' => 'active']);
        $token = $this->mobileToken($user);

        $this->withToken($token)->getJson('/api/mobile/v1/doctor/patients/'.$patient->id)
            ->assertOk()
            ->assertJsonPath('data.patient.id', $patient->id);

        $this->withToken($token)->getJson('/api/mobile/v1/doctor/patients/'.$other->id)
            ->assertNotFound();

        $this->withToken($token)->postJson('/api/mobile/v1/doctor/patients/'.$patient->id.'/clinical-records', [
            'record_type' => 'follow_up',
            'title' => 'Seguimiento móvil',
            'summary' => 'Paciente con evolución clínica favorable.',
            'recorded_at' => now()->toIso8601String(),
        ])->assertCreated()
            ->assertJsonPath('data.clinical_record.title', 'Seguimiento móvil');

        $record = ClinicalRecord::query()->where('patient_id', $patient->id)->firstOrFail();
        $this->assertSame($doctor->id, $record->doctor_id);
        $this->assertDatabaseHas('audit_logs', ['event' => 'mobile.doctor.patient.record.created', 'auditable_id' => $record->id]);
        $this->assertGreaterThanOrEqual(1, AuditLog::query()->where('event', 'mobile.doctor.patient.viewed')->count());
    }

    public function test_doctor_can_create_prescription_and_laboratory_request_for_related_patient(): void
    {
        [$user, $doctor] = $this->doctorAccount('doctor-clinical-write');
        $patient = Patient::query()->create(['full_name' => 'Paciente clínico', 'primary_doctor_id' => $doctor->id, 'status' => 'active']);
        $token = $this->mobileToken($user);

        $this->withToken($token)->postJson('/api/mobile/v1/doctor/prescriptions', [
            'patient_id' => $patient->id,
            'issued_at' => now()->toIso8601String(),
            'diagnosis' => 'Cefalea tensional',
            'notes' => 'Revisar en siete días.',
            'items' => [[
                'medication_name' => 'Paracetamol',
                'dose' => '500 mg',
                'frequency' => 'Cada 8 horas',
                'duration' => '3 días',
                'quantity' => 9,
                'instructions' => 'Tomar después de alimentos.',
            ]],
        ])->assertCreated()
            ->assertJsonPath('data.prescription.patient.id', $patient->id)
            ->assertJsonPath('data.prescription.items.0.medication_name', 'Paracetamol');

        $prescription = Prescription::query()->where('doctor_id', $doctor->id)->firstOrFail();
        $this->assertDatabaseHas('audit_logs', ['event' => 'mobile.doctor.prescription.created', 'auditable_id' => $prescription->id]);

        $this->withToken($token)->postJson('/api/mobile/v1/doctor/requests/laboratory', [
            'patient_id' => $patient->id,
            'service' => 'Biometría hemática completa',
            'diagnosis' => 'Evaluación de anemia',
            'priority' => 'routine',
        ])->assertCreated()
            ->assertJsonPath('data.request.type', 'clinical_labs')
            ->assertJsonPath('data.request.patient.id', $patient->id);

        $providerRequest = ProviderRequest::query()->where('patient_id', $patient->id)->firstOrFail();
        $this->assertSame($doctor->id, data_get($providerRequest->payload, 'doctor_id'));
        $this->assertDatabaseHas('audit_logs', ['event' => 'mobile.doctor.service_request.created', 'auditable_id' => $providerRequest->id]);

        $this->withToken($token)->getJson('/api/mobile/v1/doctor/requests')
            ->assertOk()
            ->assertJsonCount(1, 'data.items');
    }

    public function test_patient_cannot_open_doctor_endpoints(): void
    {
        $patientUser = User::query()->create(['name' => 'Paciente', 'username' => 'patient-doctor-endpoints', 'password' => Hash::make('password'), 'role' => 'patient', 'module' => 'patient', 'status' => 'active']);

        $this->withToken($patientUser->createToken('Pixel', ['mobile:access', 'role:patient'], now()->addDay())->plainTextToken)
            ->getJson('/api/mobile/v1/doctor/dashboard')
            ->assertForbidden()
            ->assertJsonPath('error.code', 'role_not_allowed');
    }

    public function test_doctor_can_prevalidate_and_create_an_idempotent_mobile_npt_request(): void
    {
        config(['cbta.base_url' => 'http://cbta.test', 'cbta.token' => 'test-token']);
        $unit = MedicalUnit::query()->create(['name' => 'Hospital Móvil', 'cbta_external_code' => 'MOBILE-NPT', 'status' => 'active']);
        [$user, $doctor] = $this->doctorAccount('doctor-mobile-npt');
        $doctor->update(['medical_unit_id' => $unit->id, 'professional_license' => 'CED-MOBILE']);
        $patient = Patient::query()->create(['full_name' => 'Paciente NPT Móvil', 'primary_doctor_id' => $doctor->id, 'status' => 'active']);
        Http::fake([
            'http://cbta.test/api/internal/v1/medical-units/MOBILE-NPT/catalogs/npt' => Http::response(['data' => [
                'catalog_type' => 'npt', 'catalog_version' => 'mobile-v1',
                'medical_unit' => ['external_code' => 'MOBILE-NPT', 'name' => 'Hospital Móvil'],
                'items' => [['product_code' => 'GLUCOSE', 'presentation_code' => 'GLUCOSE-500', 'generic_name' => 'Glucosa']],
            ]]),
            'http://cbta.test/api/internal/v1/mixture-requests/prevalidate' => Http::response(['data' => [
                'valid' => true, 'catalog_type' => 'npt', 'catalog_version' => 'mobile-v1',
                'medical_unit' => ['external_code' => 'MOBILE-NPT'], 'items' => [['valid' => true]], 'errors' => [],
            ]]),
        ]);
        $token = $this->mobileToken($user);

        $this->withToken($token)->getJson('/api/mobile/v1/doctor/mixtures/catalogs/npt')
            ->assertOk()->assertJsonPath('data.catalog.items.0.product_code', 'GLUCOSE');

        $payload = [
            'request_type' => 'npt', 'patient_id' => $patient->id, 'service' => 'Nutrición parenteral',
            'diagnosis' => 'Soporte nutricional', 'priority' => 'routine',
            'npt' => ['clinical_service' => 'Nutrición clínica', 'registration' => 'REG-MOBILE', 'weight' => 70,
                'sex' => 'Femenino', 'birth_date' => '1986-04-12', 'route' => 'Central', 'infusion_hours' => 24,
                'total_volume' => 1200, 'npt_type' => 'Individualizada', 'delivery_at' => now()->addHours(4)->format('Y-m-d H:i:s'),
                'destination_hospital' => 'Hospital Móvil', 'doctor_name' => $doctor->full_name, 'professional_license' => 'CED-MOBILE'],
            'integration_catalog_version' => 'mobile-v1',
            'integration_items' => [['catalog_item' => 'GLUCOSE|GLUCOSE-500', 'quantity' => 100, 'unit' => 'ml']],
        ];

        $created = $this->withToken($token)->withHeader('Idempotency-Key', 'mobile-npt-001')
            ->postJson('/api/mobile/v1/doctor/mixtures', $payload)
            ->assertCreated()->assertJsonPath('data.duplicate', false);
        $requestId = $created->json('data.request.id');

        $this->withToken($token)->withHeader('Idempotency-Key', 'mobile-npt-001')
            ->postJson('/api/mobile/v1/doctor/mixtures', $payload)
            ->assertOk()->assertJsonPath('data.duplicate', true)->assertJsonPath('data.request.id', $requestId);
        $this->assertDatabaseCount('provider_requests', 1);

        $this->withToken($token)->getJson('/api/mobile/v1/doctor/mixtures/'.$requestId)
            ->assertOk()->assertJsonPath('data.request.authorizations.nursing', 'pending');
    }

    private function doctorAccount(string $username): array
    {
        $user = User::query()->create(['name' => 'Doctor API', 'username' => $username, 'email' => $username.'@test.local', 'password' => Hash::make('password'), 'role' => 'doctor', 'module' => 'doctor', 'status' => 'active']);
        $doctor = Doctor::query()->create(['user_id' => $user->id, 'full_name' => 'Dr. '.$username, 'specialty' => 'Medicina interna', 'status' => 'active']);

        return [$user, $doctor];
    }

    private function mobileToken(User $user): string
    {
        return $user->createToken('Pixel', ['mobile:access', 'role:doctor'], now()->addDay())->plainTextToken;
    }
}
