<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Doctor;
use App\Models\DoctorAvailabilityRule;
use App\Models\DoctorClinic;
use App\Models\ClinicalEncounter;
use App\Models\MedicalUnit;
use App\Models\MixtureIntegration;
use App\Models\Patient;
use App\Models\PatientOrder;
use App\Models\Prescription;
use App\Models\ProviderRequest;
use App\Models\ProviderRequestStatusEvent;
use App\Models\ProcedureArea;
use App\Models\User;
use App\Services\Integrations\Cbta\MixtureIntegrationSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DoctorPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_doctor_user_can_open_native_blade_portal(): void
    {
        $user = User::query()->create([
            'name' => 'Dr. Demo',
            'username' => 'doctor.demo',
            'email' => 'doctor.demo@test.local',
            'role' => 'doctor',
            'module' => 'doctor',
            'status' => 'active',
        ]);

        $doctor = Doctor::query()->create([
            'user_id' => $user->id,
            'full_name' => 'Dr. Demo',
            'professional_license' => 'CED-TEST',
            'specialty' => 'Medicina interna',
            'status' => 'active',
        ]);

        $patient = Patient::query()->create([
            'platform_number' => 'PAC-MED-001',
            'full_name' => 'Paciente Medico',
            'status' => 'active',
        ]);

        Appointment::query()->create([
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'specialty' => 'Medicina interna',
            'status' => 'scheduled',
            'starts_at' => now()->addDay(),
        ]);

        $this->actingAs($user)
            ->get(route('doctor.dashboard'))
            ->assertOk()
            ->assertSee('Asistente Clinico')
            ->assertSee('doctor-assistant-native-screen')
            ->assertSee('doctor-assistant-native-menu')
            ->assertSee('puedo ayudarte hoy?')
            ->assertSee('Dr. Demo')
            ->assertDontSee('Servicios contratados')
            ->assertDontSee('Paciente Medico')
            ->assertDontSee('<iframe');
    }

    public function test_unit_doctors_can_claim_a_pending_consultation_only_once(): void
    {
        [$user, $doctor] = $this->createDoctor();
        $unit = MedicalUnit::query()->create(['name' => 'Hospital Demo Dr Sam', 'status' => 'active']);
        $doctor->update(['medical_unit_id' => $unit->id, 'specialty' => 'Medicina interna']);
        $secondUser = User::query()->create([
            'name' => 'Dra. Segunda', 'username' => 'doctor.segunda', 'role' => 'doctor', 'module' => 'doctor', 'status' => 'active',
        ]);
        $secondDoctor = Doctor::query()->create([
            'user_id' => $secondUser->id, 'medical_unit_id' => $unit->id,
            'full_name' => 'Dra. Segunda', 'specialty' => 'Medicina interna', 'status' => 'active',
        ]);
        $patient = Patient::query()->create(['full_name' => 'Paciente Hospital Demo', 'status' => 'active']);
        $room = ProcedureArea::query()->create([
            'medical_unit_id' => $unit->id, 'type' => 'consulting', 'unit_number' => 'C-01', 'status' => 'active',
        ]);
        $startsAt = now()->addWeek()->startOfDay()->addHours(10);
        $appointment = Appointment::query()->create([
            'patient_id' => $patient->id,
            'medical_unit_id' => $unit->id,
            'procedure_area_id' => $room->id,
            'specialty' => 'Medicina interna',
            'modality' => 'Presencial',
            'status' => 'scheduled',
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addMinutes(30),
            'metadata' => ['source' => 'unit_consultation_calendar'],
        ]);
        $otherPatient = Patient::query()->create(['full_name' => 'Paciente Pediatria', 'status' => 'active']);
        $otherSpecialty = Appointment::query()->create([
            'patient_id' => $otherPatient->id, 'medical_unit_id' => $unit->id,
            'specialty' => 'Pediatria', 'status' => 'scheduled',
            'starts_at' => $startsAt->copy()->addHour(), 'ends_at' => $startsAt->copy()->addMinutes(90),
            'metadata' => ['source' => 'unit_consultation_calendar'],
        ]);

        $this->actingAs($user)->get(route('doctor.dashboard', ['section' => 'agenda']))
            ->assertOk()->assertSee('Citas de la unidad por tomar')->assertSee('Paciente Hospital Demo')
            ->assertDontSee('Paciente Pediatria')
            ->assertSee(route('doctor.appointments.claim', $appointment));
        $this->actingAs($user)->post(route('doctor.appointments.claim', $otherSpecialty))->assertNotFound();
        $this->actingAs($secondUser)->get(route('doctor.dashboard', ['section' => 'agenda']))
            ->assertOk()->assertSee('Paciente Hospital Demo');

        $this->actingAs($user)->post(route('doctor.appointments.claim', $appointment))
            ->assertRedirect(route('doctor.dashboard', ['section' => 'agenda']));
        $this->assertSame($doctor->id, $appointment->fresh()->doctor_id);
        $claimAudit = AuditLog::query()->where('event', 'doctor.institutional_appointment.claimed')
            ->where('auditable_id', $appointment->id)->firstOrFail();
        $this->assertContains('doctor_id', data_get($claimAudit->payload, 'changed_fields'));
        $this->actingAs($user)->get(route('doctor.dashboard', ['section' => 'agenda']))
            ->assertOk()->assertSee('Mis citas de la unidad')->assertSee('Paciente Hospital Demo');

        $this->actingAs($secondUser)->post(route('doctor.appointments.claim', $appointment))
            ->assertSessionHasErrors('appointment');
        $this->assertSame($doctor->id, $appointment->fresh()->doctor_id);

        $otherUnit = MedicalUnit::query()->create(['name' => 'Otra unidad', 'status' => 'active']);
        $secondDoctor->update(['medical_unit_id' => $otherUnit->id]);
        $this->actingAs($secondUser)->post(route('doctor.appointments.claim', $appointment))->assertNotFound();
    }

    public function test_claim_ignores_private_hours_but_not_existing_doctor_appointments(): void
    {
        [$user, $doctor] = $this->createDoctor();
        $unit = MedicalUnit::query()->create(['name' => 'Hospital Publico', 'status' => 'active']);
        $doctor->update(['medical_unit_id' => $unit->id, 'specialty' => 'Medicina interna']);
        $patient = Patient::query()->create(['full_name' => 'Paciente por tomar', 'status' => 'active']);
        $clinic = DoctorClinic::query()->create([
            'doctor_id' => $doctor->id, 'name' => 'Consulta privada', 'status' => 'active',
        ]);
        DoctorAvailabilityRule::query()->create([
            'doctor_id' => $doctor->id,
            'doctor_clinic_id' => $clinic->id,
            'weekday' => 1,
            'start_time' => '09:00',
            'end_time' => '13:00',
            'recurrence_start' => '2029-01-01',
            'status' => 'published',
        ]);
        $startsAt = \Illuminate\Support\Carbon::parse('2030-01-24 10:00:00');
        $busy = Appointment::query()->create([
            'patient_id' => $patient->id, 'doctor_id' => $doctor->id,
            'specialty' => 'Medicina interna', 'status' => 'scheduled',
            'starts_at' => $startsAt, 'ends_at' => $startsAt->copy()->addMinutes(30),
            'metadata' => ['source' => 'doctor_module'],
        ]);
        $pending = Appointment::query()->create([
            'patient_id' => $patient->id, 'medical_unit_id' => $unit->id,
            'specialty' => 'Medicina interna', 'status' => 'scheduled',
            'starts_at' => $startsAt, 'ends_at' => $startsAt->copy()->addMinutes(30),
            'metadata' => ['source' => 'unit_consultation_calendar'],
        ]);

        $this->actingAs($user)->post(route('doctor.appointments.claim', $pending))
            ->assertSessionHasErrors('starts_at');
        $this->assertNull($pending->fresh()->doctor_id);

        $busy->update(['status' => 'cancelled']);
        $this->actingAs($user)->post(route('doctor.appointments.claim', $pending))
            ->assertRedirect(route('doctor.dashboard', ['section' => 'agenda']));
        $this->assertSame($doctor->id, $pending->fresh()->doctor_id);
    }

    public function test_private_doctor_receives_native_operational_services(): void
    {
        [$user, $doctor] = $this->createDoctor();

        $this->actingAs($user)
            ->get(route('doctor.dashboard', ['section' => 'services']))
            ->assertOk()
            ->assertSee('parenteral')
            ->assertSee('clinical_labs')
            ->assertDontSee('Sin servicios operativos asignados para Privada.');

        $doctor->refresh();
        $this->assertSame(
            ['Nutrici' . "\u{00F3}" . 'n parenteral', 'An' . "\u{00E1}" . 'lisis Cl' . "\u{00ED}" . 'nicos'],
            collect($doctor->metadata['service_assignments'] ?? [])->pluck('name')->all()
        );
    }

    public function test_doctor_sees_important_cbta_mixture_updates(): void
    {
        [$user, $doctor] = $this->createDoctor();
        $patient = Patient::query()->create([
            'platform_number' => 'PAC-NOTIFY-1',
            'full_name' => 'Paciente Notificado',
            'primary_doctor_id' => $doctor->id,
            'status' => 'active',
        ]);
        $providerRequest = ProviderRequest::query()->create([
            'patient_id' => $patient->id,
            'external_id' => 'NPT-NOTIFY-1',
            'request_type' => 'npt',
            'status' => 'ready',
            'requested_at' => now(),
            'payload' => ['doctor_id' => $doctor->id, 'service' => 'Nutricion parenteral'],
        ]);
        ProviderRequestStatusEvent::query()->create([
            'provider_request_id' => $providerRequest->id,
            'status' => 'ready',
            'actor' => 'cbta.integration',
            'occurred_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('doctor.dashboard', ['section' => 'services']))
            ->assertOk()
            ->assertSee('Actualizaciones de Mezclas')
            ->assertSee('NPT-NOTIFY-1')
            ->assertSeeText('La mezcla está lista para entrega.');
    }

    public function test_doctor_can_download_own_cbta_document_through_the_private_proxy(): void
    {
        [$user, $doctor] = $this->createDoctor();
        $patient = Patient::query()->create([
            'platform_number' => 'PAC-DOCUMENT-1',
            'full_name' => 'Paciente Documento',
            'primary_doctor_id' => $doctor->id,
            'status' => 'active',
        ]);
        $providerRequest = ProviderRequest::query()->create([
            'patient_id' => $patient->id,
            'external_id' => 'NPT-DOCUMENT-1',
            'request_type' => 'npt',
            'status' => 'requested',
            'requested_at' => now(),
            'payload' => ['doctor_id' => $doctor->id],
        ]);
        MixtureIntegration::query()->create([
            'provider_request_id' => $providerRequest->id,
            'local_external_id' => (string) str()->uuid(),
            'cbta_request_id' => 'CBTA-DOCUMENT-1',
            'sync_status' => 'synced',
            'metadata' => ['remote_documents' => [[
                'id' => 91,
                'type' => 'authorization',
                'name' => 'autorizacion.pdf',
            ]]],
        ]);
        config()->set('cbta.base_url', 'https://cbta.test');
        config()->set('cbta.token', 'service-token');
        Http::fake([
            'https://cbta.test/api/internal/v1/mixture-requests/CBTA-DOCUMENT-1/documents/91' => Http::response(
                'private-pdf', 200, ['Content-Type' => 'application/pdf']
            ),
        ]);

        $response = $this->actingAs($user)->get(route(
            'doctor.service_requests.documents.download',
            [$providerRequest, 91]
        ));

        $response->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition', 'attachment; filename="autorizacion.pdf"');
        $this->assertStringContainsString('private', (string) $response->headers->get('cache-control'));
        $this->assertStringContainsString('no-store', (string) $response->headers->get('cache-control'));
        $this->assertSame('private-pdf', $response->getContent());
    }

    public function test_doctor_can_download_the_official_cbta_remission(): void
    {
        [$user, $doctor] = $this->createDoctor();
        $patient = Patient::query()->create([
            'platform_number' => 'PAC-REMISSION-1', 'full_name' => 'Paciente Remision',
            'primary_doctor_id' => $doctor->id, 'status' => 'active',
        ]);
        $providerRequest = ProviderRequest::query()->create([
            'patient_id' => $patient->id, 'external_id' => 'NPT-REMISSION-1',
            'request_type' => 'npt', 'status' => 'ready', 'requested_at' => now(),
            'payload' => ['doctor_id' => $doctor->id, 'cbta' => ['remission' => [
                'available' => true, 'number' => 'REM-1001',
            ]]],
        ]);
        MixtureIntegration::query()->create([
            'provider_request_id' => $providerRequest->id,
            'local_external_id' => (string) str()->uuid(),
            'cbta_request_id' => 'CBTA-REMISSION-1', 'sync_status' => 'synced',
        ]);
        config()->set('cbta.base_url', 'https://cbta.test');
        config()->set('cbta.token', 'service-token');
        Http::fake([
            'https://cbta.test/api/internal/v1/mixture-requests/CBTA-REMISSION-1/remission' => Http::response(
                'remission-pdf', 200, ['Content-Type' => 'application/pdf']
            ),
        ]);

        $response = $this->actingAs($user)->get(route(
            'doctor.service_requests.remission.download', $providerRequest
        ));

        $response->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition', 'attachment; filename="remision-REM-1001.pdf"');
        $this->assertSame('remission-pdf', $response->getContent());
    }

    public function test_doctor_can_open_the_oncology_mixture_request_format(): void
    {
        [$user, $doctor] = $this->createDoctor();
        Patient::query()->create([
            'platform_number' => 'PAC-ONC-001',
            'full_name' => 'Paciente Oncologia',
            'primary_doctor_id' => $doctor->id,
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->get(route('doctor.dashboard', ['section' => 'services', 'type' => 'chemo', 'action' => 'request']))
            ->assertOk()
            ->assertSee('doctor-oncology-request-modal')
            ->assertSee('Solicitud de mezcla oncol&oacute;gica', false)
            ->assertSee('Informaci&oacute;n general', false)
            ->assertSee('Tabla de medicamentos')
            ->assertDontSee('V&iacute;a de administraci&oacute;n', false)
            ->assertDontSee('oncology[medications][0][routes][]', false)
            ->assertSee('Observaciones adicionales y comentarios sobre v&iacute;as de administraci&oacute;n', false)
            ->assertSee('data-oncology-add-medication', false)
            ->assertSee('data-oncology-remove-medication', false)
            ->assertSee('Agregar medicamento')
            ->assertSee('Guardar solicitud');
    }

    public function test_doctor_agenda_renders_its_calendar_workspace(): void
    {
        [$user, $doctor] = $this->createDoctor();

        $this->actingAs($user)
            ->get(route('doctor.dashboard', ['section' => 'agenda']))
            ->assertOk()
            ->assertSee('doctor-agenda-native')
            ->assertSee('Calendario')
            ->assertSee('Visualiza y gestiona tus citas y actividades');
    }

    public function test_doctor_video_schedule_renders_the_isolated_patient_step(): void
    {
        [$user, $doctor] = $this->createDoctor();

        $this->actingAs($user)
            ->get(route('doctor.dashboard', ['section' => 'video', 'action' => 'schedule']))
            ->assertOk()
            ->assertSee('doctor-video-scheduler')
            ->assertSee('Agendar videollamada')
            ->assertSee('Seleccionar paciente')
            ->assertSee('doctor-video-steps')
            ->assertSee('Continuar');
    }

    public function test_video_schedule_redirect_renders_the_ready_step(): void
    {
        [$user, $doctor] = $this->createDoctor();
        $patient = Patient::query()->create([
            'platform_number' => 'PAC-VIDEO-LISTO',
            'full_name' => 'Paciente Video Listo',
            'primary_doctor_id' => $doctor->id,
            'status' => 'active',
        ]);
        $clinic = DoctorClinic::query()->create([
            'doctor_id' => $doctor->id,
            'name' => 'Consultorio virtual',
            'location_type' => 'virtual',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->post(route('doctor.appointments.store'), [
            'patient_id' => $patient->id,
            'doctor_clinic_id' => $clinic->id,
            'starts_at' => now()->addDay()->format('Y-m-d H:i:s'),
            'duration' => 20,
            'reason' => 'Primera vez',
            'modality' => 'Video llamada',
        ]);

        $appointment = Appointment::query()->where('doctor_id', $doctor->id)->firstOrFail();
        $destination = route('doctor.dashboard', [
            'section' => 'video',
            'action' => 'schedule',
            'scheduled' => 1,
            'scheduled_confirmation' => 1,
            'appointment' => $appointment->id,
        ]);

        $response->assertRedirect($destination);

        $this->actingAs($user)
            ->get($destination)
            ->assertOk()
            ->assertSee('Videoconsulta agendada')
            ->assertSee('Ver en agenda')
            ->assertSee('Agendar otra videoconsulta');

        $this->actingAs($user)
            ->withSession([
                'video_schedule_confirmation' => [
                    'appointment_id' => $appointment->id,
                    'patient_name' => $patient->full_name,
                    'patient_number' => $patient->platform_number,
                    'starts_at' => $appointment->starts_at->toIso8601String(),
                    'duration' => 20,
                ],
            ])
            ->get(route('doctor.dashboard', ['section' => 'video', 'action' => 'schedule']))
            ->assertOk()
            ->assertSee('Videoconsulta agendada')
            ->assertSee('Agendar otra videoconsulta');
    }

    public function test_video_schedule_creates_a_virtual_clinic_when_the_doctor_has_none(): void
    {
        [$user, $doctor] = $this->createDoctor();
        $patient = Patient::query()->create([
            'platform_number' => 'PAC-VIDEO-VIRTUAL',
            'full_name' => 'Paciente Virtual',
            'primary_doctor_id' => $doctor->id,
            'status' => 'active',
        ]);

        $this->actingAs($user)->post(route('doctor.appointments.store'), [
            'patient_id' => $patient->id,
            'starts_at' => now()->addDays(3)->format('Y-m-d H:i:s'),
            'duration' => 20,
            'reason' => 'Videollamada de prueba',
            'modality' => 'Video llamada',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('doctor_clinics', [
            'doctor_id' => $doctor->id,
            'name' => 'Consultorio virtual',
            'location_type' => 'virtual',
            'status' => 'active',
        ]);
    }

    public function test_doctor_can_publish_availability_and_overlaps_are_rejected(): void
    {
        [$user, $doctor] = $this->createDoctor();
        $clinic = DoctorClinic::query()->create(['doctor_id' => $doctor->id, 'name' => 'Consultorio 5', 'status' => 'active']);
        $payload = ['doctor_clinic_id' => $clinic->id, 'weekday' => 1, 'start_time' => '08:00', 'end_time' => '12:00', 'recurrence_start' => '2026-07-21', 'mode' => 'in_person'];

        $this->actingAs($user)->post(route('doctor.availability.store'), $payload)->assertSessionHasNoErrors();
        $this->actingAs($user)->post(route('doctor.availability.store'), [...$payload, 'start_time' => '11:00', 'end_time' => '13:00'])->assertSessionHasErrors('schedule');

        $this->assertSame(1, DoctorAvailabilityRule::query()->count());
    }

    public function test_doctor_can_manage_own_clinics(): void
    {
        [$user, $doctor] = $this->createDoctor();

        $this->actingAs($user)->post(route('doctor.clinics.store'), [
            'name' => 'Consultorio Norte',
            'address' => 'Av. Central 100',
            'location_type' => 'in_person',
        ])->assertSessionHasNoErrors();

        $clinic = DoctorClinic::query()->firstOrFail();
        $this->actingAs($user)->patch(route('doctor.clinics.update', $clinic), [
            'name' => 'Consultorio Norte Actualizado',
            'address' => 'Av. Central 200',
            'location_type' => 'hybrid',
            'status' => 'active',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('doctor_clinics', [
            'id' => $clinic->id,
            'doctor_id' => $doctor->id,
            'name' => 'Consultorio Norte Actualizado',
            'location_type' => 'hybrid',
        ]);

        $this->actingAs($user)->delete(route('doctor.clinics.destroy', $clinic))->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('doctor_clinics', ['id' => $clinic->id]);
    }

    public function test_doctor_can_create_and_cancel_an_appointment_from_agenda(): void
    {
        [$user, $doctor] = $this->createDoctor();
        $patient = Patient::query()->create(['platform_number' => 'PAC-AGENDA', 'full_name' => 'Paciente Agenda', 'status' => 'active']);
        $clinic = DoctorClinic::query()->create(['doctor_id' => $doctor->id, 'name' => 'Consultorio Agenda', 'status' => 'active']);

        $this->actingAs($user)->post(route('doctor.appointments.store'), [
            'patient_id' => $patient->id,
            'doctor_clinic_id' => $clinic->id,
            'starts_at' => '2026-07-27 10:00',
            'duration' => 30,
            'modality' => 'Presencial',
            'reason' => 'Primera consulta',
        ])->assertSessionHasNoErrors();

        $appointment = Appointment::query()->where('doctor_id', $doctor->id)->where('reason', 'Primera consulta')->firstOrFail();
        $this->assertSame('Consultorio Agenda', $appointment->location);
        $this->assertSame($clinic->id, (int) data_get($appointment->metadata, 'doctor_clinic_id'));

        $this->actingAs($user)->patch(route('doctor.appointments.status', $appointment), ['status' => 'cancelled'])
            ->assertRedirect(route('doctor.dashboard', ['section' => 'agenda']))
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'cancelled']);
        $this->assertDatabaseHas('appointment_status_events', ['appointment_id' => $appointment->id, 'from_status' => 'scheduled', 'to_status' => 'cancelled']);
    }

    public function test_doctor_can_create_edit_and_open_a_patient_record(): void
    {
        [$user, $doctor] = $this->createDoctor();

        $this->actingAs($user)->post(route('doctor.patients.store'), [
            'platform_number' => 'PAC-EXP-001',
            'first_name' => 'Ana',
            'last_name' => 'López',
            'curp' => 'LOPA900101MQTPRN01',
            'birth_date' => '1990-01-01',
            'sex' => 'Femenino',
            'phone' => '5555550101',
            'email' => 'ana@example.test',
            'status' => 'active',
            'general_observations' => 'Antecedente familiar de diabetes.',
        ])->assertSessionHasNoErrors();

        $patient = Patient::query()->where('platform_number', 'PAC-EXP-001')->firstOrFail();
        $this->assertSame($doctor->id, $patient->primary_doctor_id);
        $this->assertSame('Ana López', $patient->full_name);

        $this->actingAs($user)->patch(route('doctor.patients.update', $patient), [
            'platform_number' => 'PAC-EXP-001',
            'first_name' => 'Ana María',
            'last_name' => 'López',
            'curp' => 'LOPA900101MQTPRN01',
            'birth_date' => '1990-01-01',
            'sex' => 'Femenino',
            'phone' => '5555550102',
            'email' => 'ana@example.test',
            'status' => 'active',
        ])->assertSessionHasNoErrors();

        $this->actingAs($user)->post(route('doctor.patients.records.store', $patient), [
            'record_type' => 'follow_up',
            'title' => 'Seguimiento metabólico',
            'summary' => 'Paciente estable y sin datos de alarma.',
            'recorded_at' => '2026-07-23 10:00:00',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('patients', ['id' => $patient->id, 'full_name' => 'Ana María López', 'phone' => '5555550102']);
        $this->assertDatabaseHas('clinical_records', ['patient_id' => $patient->id, 'doctor_id' => $doctor->id, 'title' => 'Seguimiento metabólico']);
        $this->actingAs($user)->get(route('doctor.dashboard', ['section' => 'patients', 'patient' => $patient->id]))
            ->assertOk()
            ->assertSee('Expediente')
            ->assertSee('Seguimiento');
    }

    public function test_doctor_cannot_edit_an_unrelated_patient(): void
    {
        [$user] = $this->createDoctor();
        $patient = Patient::query()->create(['platform_number' => 'PAC-AJENO', 'first_name' => 'Paciente', 'last_name' => 'Ajeno', 'full_name' => 'Paciente Ajeno', 'status' => 'active']);

        $this->actingAs($user)->patch(route('doctor.patients.update', $patient), [
            'first_name' => 'Cambio',
            'last_name' => 'No autorizado',
            'status' => 'active',
        ])->assertNotFound();
    }

    public function test_doctor_can_start_and_complete_a_clinical_encounter(): void
    {
        [$user, $doctor] = $this->createDoctor();
        $patient = Patient::query()->create(['platform_number' => 'PAC-002', 'full_name' => 'Paciente Consulta', 'primary_doctor_id' => $doctor->id, 'status' => 'active']);
        $this->actingAs($user)->post(route('doctor.encounters.store'), ['patient_id' => $patient->id, 'reason' => 'Dolor abdominal'])->assertSessionHasNoErrors();
        $encounter = ClinicalEncounter::query()->firstOrFail();
        $this->actingAs($user)->patch(route('doctor.encounters.complete', $encounter), ['assessment' => 'Gastritis', 'treatment_plan' => 'Tratamiento indicado'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('clinical_encounters', ['id' => $encounter->id, 'status' => 'completed', 'assessment' => 'Gastritis']);
    }

    public function test_doctor_can_schedule_and_open_a_video_consultation(): void
    {
        [$user, $doctor] = $this->createDoctor();
        $patient = Patient::query()->create(['platform_number' => 'PAC-VIDEO', 'full_name' => 'Paciente Video', 'primary_doctor_id' => $doctor->id, 'status' => 'active']);
        $clinic = DoctorClinic::query()->create(['doctor_id' => $doctor->id, 'name' => 'Consultorio virtual', 'location_type' => 'virtual', 'status' => 'active']);

        $this->actingAs($user)->post(route('doctor.appointments.store'), [
            'patient_id' => $patient->id,
            'doctor_clinic_id' => $clinic->id,
            'starts_at' => now()->addDay()->format('Y-m-d H:i:s'),
            'duration' => 30,
            'reason' => 'Seguimiento remoto',
            'modality' => 'Video llamada',
        ])->assertSessionHasNoErrors();

        $appointment = Appointment::query()->firstOrFail();
        $this->actingAs($user)->post(route('doctor.encounters.store'), [
            'appointment_id' => $appointment->id,
            'patient_id' => $patient->id,
            'reason' => $appointment->reason,
            'modality' => 'Video llamada',
        ])->assertSessionHasNoErrors();

        $encounter = ClinicalEncounter::query()->firstOrFail();
        $this->assertSame('Video llamada', data_get($encounter->metadata, 'modality'));
        $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'in_progress']);
    }

    public function test_doctor_can_save_clinical_progress_and_complete_the_encounter(): void
    {
        [$user, $doctor] = $this->createDoctor();
        $patient = Patient::query()->create(['platform_number' => 'PAC-CLINICA', 'full_name' => 'Paciente Clinica', 'primary_doctor_id' => $doctor->id, 'status' => 'active']);

        $this->actingAs($user)->post(route('doctor.encounters.store'), [
            'patient_id' => $patient->id,
            'reason' => 'Control',
        ])->assertSessionHasNoErrors();

        $encounter = ClinicalEncounter::query()->firstOrFail();
        $this->actingAs($user)->patch(route('doctor.encounters.update', $encounter), [
            'symptoms' => 'Dolor moderado',
            'vital_signs' => ['blood_pressure' => '120/80', 'temperature' => '36.5'],
            'background' => ['summary' => 'Sin antecedentes relevantes'],
            'examination' => 'Exploración sin hallazgos de alarma',
            'assessment' => 'En observación',
        ])->assertSessionHasNoErrors();

        $this->actingAs($user)->patch(route('doctor.encounters.complete', $encounter), [
            'reason' => 'Control',
            'symptoms' => 'Dolor moderado',
            'vital_signs' => ['blood_pressure' => '120/80', 'temperature' => '36.5'],
            'background' => ['summary' => 'Sin antecedentes relevantes'],
            'examination' => 'Exploración sin hallazgos de alarma',
            'assessment' => 'Evolución favorable',
            'treatment_plan' => 'Continuar vigilancia',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('clinical_encounters', ['id' => $encounter->id, 'status' => 'completed', 'assessment' => 'Evolución favorable']);
        $this->assertDatabaseHas('clinical_records', ['patient_id' => $patient->id, 'doctor_id' => $doctor->id, 'summary' => 'Evolución favorable']);
    }

    public function test_doctor_can_open_an_encounter_from_own_appointment(): void
    {
        [$user, $doctor] = $this->createDoctor();
        $patient = Patient::query()->create(['platform_number' => 'PAC-CITA', 'full_name' => 'Paciente con Cita', 'status' => 'active']);
        $appointment = Appointment::query()->create(['doctor_id' => $doctor->id, 'patient_id' => $patient->id, 'status' => 'scheduled', 'starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addMinutes(30)]);

        $this->actingAs($user)->post(route('doctor.encounters.store'), ['appointment_id' => $appointment->id, 'patient_id' => $patient->id, 'reason' => 'Seguimiento'])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('clinical_encounters', ['appointment_id' => $appointment->id, 'doctor_id' => $doctor->id, 'status' => 'in_progress']);
    }

    public function test_doctor_can_issue_a_prescription_and_send_it_to_external_pharmacy(): void
    {
        [$user, $doctor] = $this->createDoctor();
        $patient = Patient::query()->create(['platform_number' => 'PAC-RX', 'full_name' => 'Paciente Receta', 'status' => 'active']);
        Appointment::query()->create(['doctor_id' => $doctor->id, 'patient_id' => $patient->id, 'status' => 'scheduled', 'starts_at' => now()->addDay()]);

        $this->actingAs($user)->post(route('doctor.prescriptions.store'), [
            'patient_id' => $patient->id,
            'issued_at' => '2026-07-21',
            'diagnosis' => 'Dolor agudo',
            'items' => [[
                'medication_name' => 'Paracetamol',
                'cnis' => '010.000.0104.00',
                'dose' => '500 mg',
                'frequency' => 'Cada 8 horas',
                'duration' => '5 días',
                'quantity' => 10,
                'instructions' => 'Tomar con alimentos',
            ]],
        ])->assertSessionHasNoErrors()->assertRedirect(route('doctor.dashboard', ['section' => 'prescriptions']));

        $prescription = Prescription::query()->firstOrFail();
        $this->assertSame($doctor->id, $prescription->doctor_id);
        $this->assertSame('doctor_module', data_get($prescription->metadata, 'source'));
        $this->assertDatabaseHas('prescription_items', ['prescription_id' => $prescription->id, 'medication_name' => 'Paracetamol']);
        $this->assertDatabaseHas('patient_orders', ['patient_id' => $patient->id, 'channel' => 'prescription', 'status' => 'received']);
        $this->assertSame($prescription->id, (int) data_get(PatientOrder::query()->firstOrFail()->metadata, 'prescription_id'));
    }

    public function test_doctor_can_edit_a_pending_prescription_and_pharmacy_receives_the_changes(): void
    {
        [$user, $doctor] = $this->createDoctor();
        $patient = Patient::query()->create(['platform_number' => 'PAC-RX-EDIT', 'full_name' => 'Paciente Receta Editable', 'primary_doctor_id' => $doctor->id, 'status' => 'active']);

        $this->actingAs($user)->post(route('doctor.prescriptions.store'), [
            'patient_id' => $patient->id,
            'issued_at' => '2026-07-21',
            'diagnosis' => 'Dolor',
            'items' => [['medication_name' => 'Paracetamol', 'quantity' => 5]],
        ])->assertSessionHasNoErrors();

        $prescription = Prescription::query()->firstOrFail();
        $this->actingAs($user)->patch(route('doctor.prescriptions.update', $prescription), [
            'issued_at' => '2026-07-22',
            'diagnosis' => 'Dolor controlado',
            'notes' => 'Ajuste de tratamiento',
            'items' => [[
                'medication_name' => 'Metamizol sódico',
                'dose' => '500 mg',
                'route' => 'Oral',
                'frequency' => 'Cada 12 horas',
                'duration' => '3 días',
                'quantity' => 6,
            ]],
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('prescription_items', ['prescription_id' => $prescription->id, 'medication_name' => 'Metamizol sódico']);
        $order = PatientOrder::query()->with('items')->firstOrFail();
        $this->assertSame('Metamizol sódico', $order->items->first()->product_name);
        $this->assertSame(6, $order->items->first()->quantity);
    }

    public function test_doctor_can_send_each_clinical_request_type_to_the_operational_flow(): void
    {
        [$user, $doctor] = $this->createDoctor();
        $patient = Patient::query()->create(['platform_number' => 'PAC-SOL', 'full_name' => 'Paciente Solicitudes', 'primary_doctor_id' => $doctor->id, 'status' => 'active']);

        foreach (['clinical_labs', 'npt', 'chemo'] as $type) {
            $payload = [
                'request_type' => $type,
                'patient_id' => $patient->id,
                'service' => $type === 'clinical_labs' ? 'Biometría hemática' : ($type === 'npt' ? 'Nutrición parenteral' : 'Quimioterapia'),
                'diagnosis' => 'Diagnóstico de prueba',
                'notes' => 'Indicaciones clínicas',
                'medication' => $type === 'chemo' ? 'Cisplatino' : null,
                'priority' => 'urgent',
            ];
            if ($type === 'npt') {
                $payload['npt'] = [
                    'clinical_service' => 'Nutrición clínica',
                    'registration' => 'REG-NPT-1',
                    'weight' => 70,
                    'sex' => 'Masculino',
                    'birth_date' => '1980-01-01',
                    'route' => 'Central',
                    'infusion_hours' => 24,
                    'total_volume' => 1200,
                    'npt_type' => 'Individualizada',
                    'products' => ['glucose_50' => 100, 'amino_acids_standard_10' => 80],
                    'delivery_at' => now()->addHours(4)->format('Y-m-d H:i:s'),
                    'destination_hospital' => 'Hospital de prueba',
                    'doctor_name' => $doctor->full_name,
                    'professional_license' => $doctor->professional_license,
                ];
            }
            if ($type === 'chemo') {
                $payload['oncology'] = [
                    'request_date' => '2026-07-23',
                    'facility' => 'Hospital de prueba',
                    'floor' => '2',
                    'bed' => '201',
                    'patient_identifier' => 'REG-ONC-1',
                    'weight' => 70,
                    'height' => 170,
                    'sex' => 'Masculino',
                    'birth_date' => '1980-01-01',
                    'medications' => [[
                        'catalog_item' => 'CISPLATINO|CISPLATINO-50',
                        'medication' => 'Cisplatino',
                        'dose' => 50,
                        'diluent_id' => 1,
                        'route_id' => 1,
                        'dilution_volume' => 500,
                        'infusion_minutes' => 120,
                        'delivery_dates' => [now()->addDay()->format('Y-m-d H:i')],
                    ]],
                    'doctor_name' => $doctor->full_name,
                    'professional_license' => $doctor->professional_license,
                ];
                $payload['authorization_file'] = UploadedFile::fake()->create('autorizacion.pdf', 20, 'application/pdf');
            }

            $this->actingAs($user)->post(route('doctor.service_requests.store'), $payload)
                ->assertSessionHasNoErrors()
                ->assertRedirect(route('doctor.dashboard', ['section' => 'requests']));
        }

        $this->assertSame(3, ProviderRequest::query()->count());
        $this->assertSame(['chemo', 'clinical_labs', 'npt'], ProviderRequest::query()->pluck('request_type')->sort()->values()->all());
        $this->assertSame(3, ProviderRequest::query()->where('status', 'requested')->count());
        $this->assertSame(3, ProviderRequest::query()->where('payload->doctor_id', $doctor->id)->count());
        $this->assertSame(1200, (int) ProviderRequest::query()->where('request_type', 'npt')->firstOrFail()->payload['clinical_format']['total_volume']);
        $this->assertSame('Cisplatino', ProviderRequest::query()->where('request_type', 'chemo')->firstOrFail()->payload['clinical_format']['medications'][0]['medication']);
        $this->assertDatabaseCount('provider_request_status_events', 3);
    }

    public function test_npt_create_url_renders_the_focused_request_form(): void
    {
        [$user] = $this->createDoctor();

        $response = $this->actingAs($user)->get(route('doctor.dashboard', [
            'section' => 'services',
            'type' => 'npt',
            'action' => 'create',
        ]));

        $response->assertOk()
            ->assertSee('doctor-service-request-page', false)
            ->assertSee('doctor-service-request-focus', false)
            ->assertSee('Solicitud de nutrición parenteral')
            ->assertSee('name="request_type" value="npt"', false)
            ->assertSee('name="service"', false)
            ->assertSee('name="diagnosis"', false)
            ->assertSee('name="npt[bed]"', false)
            ->assertSee('name="npt[floor]"', false)
            ->assertSee('data-npt-infusion-time', false)
            ->assertSee('data-npt-infusion-rate', false)
            ->assertSee('data-npt-request-form', false)
            ->assertSee('3 horas y 30 minutos después de la hora actual')
            ->assertSee('name="npt[total_volume]"', false)
            ->assertSee('Datos del paciente y servicio')
            ->assertSee('Administración de la mezcla')
            ->assertSee('Componentes de la nutrición parenteral')
            ->assertSee('Entrega y responsable médico')
            ->assertSee('ADULTO')
            ->assertSee('PEDIÁTRICO')
            ->assertDontSee('Adulto / tricámara')
            ->assertDontSee('name="priority"', false)
            ->assertDontSee('name="required_at"', false)
            ->assertDontSee('name="npt[infusion_set]"', false)
            ->assertDontSee('name="request_type" value="chemo"', false)
            ->assertDontSee('name="request_type" value="clinical_labs"', false);
    }

    public function test_doctor_can_submit_multiple_oncology_mixtures_with_multiple_medications(): void
    {
        [$user, $doctor] = $this->createDoctor();
        $patient = Patient::query()->create([
            'platform_number' => 'PAC-ONC-MULTI', 'full_name' => 'Paciente Mezclas',
            'primary_doctor_id' => $doctor->id, 'status' => 'active',
        ]);
        $medication = fn (string $code, string $name, int $dose): array => [
            'catalog_item' => $code.'|'.$code.'-P', 'medication' => $name, 'dose' => $dose,
            'diluent_id' => 1, 'route_id' => 1,
        ];

        $this->actingAs($user)->post(route('doctor.service_requests.store'), [
            'request_type' => 'chemo', 'patient_id' => $patient->id, 'service' => 'Oncología',
            'diagnosis' => 'Diagnóstico de prueba', 'priority' => 'routine',
            'oncology' => [
                'facility' => 'Hospital', 'floor' => '2', 'bed' => '201',
                'patient_identifier' => 'REG-MULTI', 'sex' => 'Femenino', 'weight' => 60,
                'height' => 165,
                'birth_date' => '1986-04-12', 'doctor_name' => $doctor->full_name,
                'professional_license' => $doctor->professional_license, 'mixture_count' => 2,
                'mixtures' => [
                    ['medications' => [$medication('MED-A', 'Medicamento A', 50), $medication('MED-B', 'Medicamento B', 25)],
                        'dilution_volume' => 250, 'infusion_minutes' => 90,
                        'delivery_dates' => ['2026-10-20 10:00'], 'set_infusion' => true],
                    ['medications' => [$medication('MED-C', 'Medicamento C', 75)],
                        'dilution_volume' => 500, 'infusion_minutes' => 120,
                        'delivery_dates' => ['2026-10-21 11:00'], 'set_infusion' => false],
                ],
            ],
        ])->assertSessionHasNoErrors();

        $format = ProviderRequest::query()->sole()->payload['clinical_format'];
        $this->assertCount(2, $format['mixtures']);
        $this->assertCount(3, $format['medications']);
        $this->assertSame([0, 0, 1], array_column($format['medications'], 'mixture_index'));
    }

    public function test_npt_rejects_time_and_infusion_rate_when_both_are_filled(): void
    {
        [$user, $doctor] = $this->createDoctor();
        $patient = Patient::query()->create([
            'platform_number' => 'PAC-INFUSION',
            'full_name' => 'Paciente Infusion',
            'primary_doctor_id' => $doctor->id,
            'status' => 'active',
        ]);
        $payload = $this->validNptRequestPayload($patient, $doctor);
        $payload['npt']['infusion_rate'] = 50;

        $this->actingAs($user)
            ->from(route('doctor.dashboard', ['section' => 'services', 'type' => 'npt', 'action' => 'create']))
            ->post(route('doctor.service_requests.store'), $payload)
            ->assertSessionHasErrors(['npt.infusion_rate']);

        $this->assertDatabaseCount('provider_requests', 0);
    }

    public function test_npt_form_shows_medicine_additives_and_excludes_auxiliary_materials(): void
    {
        config(['cbta.base_url' => 'http://cbta.test', 'cbta.token' => 'test-token']);
        [$user, $doctor] = $this->createDoctor();
        $unit = MedicalUnit::query()->create([
            'name' => 'Hospital con aditivos',
            'code' => 'DRSAM-ADDITIVES',
            'cbta_external_code' => 'CBTA-ADDITIVES',
            'status' => 'active',
        ]);
        $doctor->update([
            'medical_unit_id' => $unit->id,
            'metadata' => [
                'service_assignments' => [[
                    'context' => 'institutional',
                    'institution' => 'Hospital con aditivos',
                    'name' => 'Nutrición parenteral',
                    'category' => 'Nutrición',
                    'specialty' => 'Nutrición clínica',
                    'request_type' => 'npt',
                    'status' => 'active',
                ]],
            ],
        ]);
        $user->unsetRelation('doctor');

        Http::fake([
            'http://cbta.test/api/internal/v1/medical-units/CBTA-ADDITIVES/catalogs/npt' => Http::response(['data' => [
                'catalog_version' => 'npt-additives-v1',
                'items' => [
                    ['product_code' => 'ALBUMIN-25', 'presentation_code' => 'ALBUMIN-25-50ML', 'generic_name' => 'ALBÚMINA 25%', 'commercial_name' => 'ALBÚMINA HUMANA', 'presentation' => 'FCO AMP 50ML', 'category' => 'Aditivos'],
                    ['product_code' => 'EVA-3000', 'presentation_code' => 'EVA-3000-UNIT', 'generic_name' => 'BOLSA EVA 3000 ML', 'commercial_name' => 'Bolsa', 'presentation' => 'UNIDAD', 'category' => 'Bolsa Eva'],
                    ['product_code' => 'WATER-500', 'presentation_code' => 'WATER-500ML', 'generic_name' => 'AGUA INYECTABLE', 'commercial_name' => 'Agua', 'presentation' => 'FRASCO 500ML', 'category' => 'Otra'],
                    ['product_code' => 'INFUSION-SET', 'presentation_code' => 'INFUSION-SET-UNIT', 'generic_name' => 'SET DE INFUSIÓN', 'commercial_name' => 'Optima', 'presentation' => 'UNIDAD', 'category' => 'Set de Infusión'],
                ],
            ]]),
            'http://cbta.test/api/internal/v1/medical-units/CBTA-ADDITIVES/catalogs/oncology' => Http::response(['data' => [
                'catalog_version' => 'oncology-empty-v1',
                'items' => [],
            ]]),
        ]);

        $response = $this->actingAs($user)->get(route('doctor.dashboard', [
            'section' => 'services',
            'type' => 'npt',
            'action' => 'create',
        ]));

        Http::assertSent(fn ($request): bool => str_ends_with($request->url(), '/medical-units/CBTA-ADDITIVES/catalogs/npt'));
        $response->assertOk()
            ->assertSee('Aditivos')
            ->assertSee('ALBÚMINA 25%')
            ->assertDontSee('BOLSA EVA 3000 ML')
            ->assertDontSee('AGUA INYECTABLE')
            ->assertDontSee('SET DE INFUSIÓN');
    }

    public function test_real_npt_form_prevalidates_with_cbta_and_persists_the_integration_result(): void
    {
        config(['cbta.base_url' => 'http://cbta.test', 'cbta.token' => 'test-token']);
        [$user, $doctor] = $this->createDoctor();
        $unit = MedicalUnit::query()->create([
            'name' => 'Hospital Integrado',
            'code' => 'DRSAM-INT',
            'cbta_external_code' => 'CBTA-HOSP-01',
            'status' => 'active',
        ]);
        $doctor->update(['medical_unit_id' => $unit->id]);
        $patient = Patient::query()->create([
            'platform_number' => 'PAC-CBTA-01',
            'full_name' => 'Paciente Integrado',
            'primary_doctor_id' => $doctor->id,
            'status' => 'active',
        ]);

        Http::fake([
            'http://cbta.test/api/internal/v1/mixture-requests/prevalidate' => Http::response([
                'data' => [
                    'valid' => true,
                    'catalog_type' => 'npt',
                    'catalog_version' => 'npt-v3',
                    'medical_unit' => ['external_code' => 'CBTA-HOSP-01'],
                    'items' => [[
                        'product_code' => 'GLUCOSE-50',
                        'presentation_code' => 'GLUCOSE-50-500ML',
                        'quantity' => 100,
                        'unit' => 'ml',
                    ]],
                    'errors' => [],
                ],
            ]),
            'http://cbta.test/api/internal/v1/mixture-requests' => Http::response([
                'data' => [
                    'request_id' => '019ca6bc-b8f0-7bd7-a819-913ccfc1045d',
                    'local_external_id' => 'ignored-in-assertion',
                    'status' => 'received',
                    'catalog_type' => 'npt',
                    'catalog_version' => 'npt-v3',
                ],
            ], 201),
        ]);

        $response = $this->actingAs($user)->post(route('doctor.service_requests.store'), array_merge(
            $this->validNptRequestPayload($patient, $doctor),
            [
                'integration_catalog_version' => 'npt-v3',
                'integration_items' => [[
                    'catalog_item' => 'GLUCOSE-50|GLUCOSE-50-500ML',
                    'quantity' => 100,
                    'unit' => 'ml',
                ]],
            ]
        ));

        $response->assertSessionHasNoErrors()
            ->assertSessionHas('sweet_alert.title', 'Solicitud creada correctamente')
            ->assertRedirect(route('doctor.dashboard', ['section' => 'requests']));

        $providerRequest = ProviderRequest::query()->sole();
        $integration = MixtureIntegration::query()->sole();
        $this->assertSame($providerRequest->id, $integration->provider_request_id);
        $this->assertSame('awaiting_authorizations', $integration->sync_status);
        $this->assertSame('npt-v3', $integration->catalog_version);
        $this->assertNull($integration->cbta_request_id);
        Http::assertNotSent(fn ($request): bool => $request->url() === 'http://cbta.test/api/internal/v1/mixture-requests');

        $payload = $providerRequest->payload;
        $payload['authorizations'] = ['operational' => 'approved', 'pharmacy' => 'approved'];
        $providerRequest->update(['payload' => $payload]);

        $this->assertTrue(app(MixtureIntegrationSyncService::class)->sync($integration->fresh()));
        $integration->refresh();

        $this->assertSame('synced', $integration->sync_status);
        $this->assertSame('019ca6bc-b8f0-7bd7-a819-913ccfc1045d', $integration->cbta_request_id);
        $this->assertSame('received', $integration->remote_status);
        $this->assertSame('npt', $integration->metadata['catalog_type']);
        $this->assertNotNull($integration->payload_hash);

        Http::assertSent(fn ($request): bool => $request->url() === 'http://cbta.test/api/internal/v1/mixture-requests/prevalidate'
            && $request['medical_unit_code'] === 'CBTA-HOSP-01'
            && $request['catalog_version'] === 'npt-v3'
            && $request['items'][0]['product_code'] === 'GLUCOSE-50');
    }

    public function test_rejected_cbta_prevalidation_does_not_create_request_or_integration(): void
    {
        config(['cbta.base_url' => 'http://cbta.test', 'cbta.token' => 'test-token']);
        [$user, $doctor] = $this->createDoctor();
        $unit = MedicalUnit::query()->create([
            'name' => 'Hospital Integrado',
            'code' => 'DRSAM-REJECT',
            'cbta_external_code' => 'CBTA-HOSP-02',
            'status' => 'active',
        ]);
        $doctor->update(['medical_unit_id' => $unit->id]);
        $patient = Patient::query()->create([
            'platform_number' => 'PAC-CBTA-02',
            'full_name' => 'Paciente Rechazado',
            'primary_doctor_id' => $doctor->id,
            'status' => 'active',
        ]);

        Http::fake([
            'http://cbta.test/api/internal/v1/mixture-requests/prevalidate' => Http::response([
                'data' => [
                    'valid' => false,
                    'catalog_type' => 'npt',
                    'catalog_version' => 'npt-v4',
                    'medical_unit' => ['external_code' => 'CBTA-HOSP-02'],
                    'items' => [],
                    'errors' => [['code' => 'insufficient_stock', 'message' => 'Stock insuficiente para el producto solicitado.']],
                ],
            ]),
        ]);

        $this->actingAs($user)->from(route('doctor.dashboard'))->post(
            route('doctor.service_requests.store'),
            array_merge($this->validNptRequestPayload($patient, $doctor), [
                'integration_catalog_version' => 'npt-v4',
                'integration_items' => [[
                    'catalog_item' => 'GLUCOSE-50|GLUCOSE-50-500ML',
                    'quantity' => 999999,
                    'unit' => 'ml',
                ]],
            ])
        )->assertRedirect(route('doctor.dashboard'))
            ->assertSessionHasErrors('integration_items');

        $this->assertDatabaseCount('provider_requests', 0);
        $this->assertDatabaseCount('mixture_integrations', 0);
    }

    public function test_cbta_creation_failure_keeps_the_local_request_ready_for_retry(): void
    {
        config(['cbta.base_url' => 'http://cbta.test', 'cbta.token' => 'test-token']);
        [$user, $doctor] = $this->createDoctor();
        $unit = MedicalUnit::query()->create([
            'name' => 'Hospital Reintento',
            'code' => 'DRSAM-RETRY',
            'cbta_external_code' => 'CBTA-HOSP-RETRY',
            'status' => 'active',
        ]);
        $doctor->update(['medical_unit_id' => $unit->id]);
        $patient = Patient::query()->create([
            'platform_number' => 'PAC-CBTA-RETRY',
            'full_name' => 'Paciente Reintento',
            'primary_doctor_id' => $doctor->id,
            'status' => 'active',
        ]);

        Http::fake([
            'http://cbta.test/api/internal/v1/mixture-requests/prevalidate' => Http::response(['data' => [
                'valid' => true,
                'catalog_type' => 'npt',
                'catalog_version' => 'npt-v5',
                'medical_unit' => ['external_code' => 'CBTA-HOSP-RETRY'],
                'items' => [['valid' => true]],
                'errors' => [],
            ]]),
            'http://cbta.test/api/internal/v1/mixture-requests' => Http::response(['message' => 'Temporalmente no disponible'], 503),
        ]);

        $this->actingAs($user)->post(route('doctor.service_requests.store'), array_merge(
            $this->validNptRequestPayload($patient, $doctor),
            [
                'integration_catalog_version' => 'npt-v5',
                'integration_items' => [[
                    'catalog_item' => 'GLUCOSE-50|GLUCOSE-50-500ML',
                    'quantity' => 100,
                    'unit' => 'ml',
                ]],
            ]
        ))->assertSessionHasNoErrors();

        $this->assertDatabaseCount('provider_requests', 1);
        $integration = MixtureIntegration::query()->sole();
        $this->assertSame('awaiting_authorizations', $integration->sync_status);

        $providerRequest = ProviderRequest::query()->sole();
        $payload = $providerRequest->payload;
        $payload['authorizations'] = ['operational' => 'approved', 'pharmacy' => 'approved'];
        $providerRequest->update(['payload' => $payload]);

        $this->assertFalse(app(MixtureIntegrationSyncService::class)->sync($integration->fresh()));
        $integration->refresh();
        $this->assertSame('failed', $integration->sync_status);
        $this->assertNull($integration->cbta_request_id);
        $this->assertNotNull($integration->last_error);
    }

    private function validNptRequestPayload(Patient $patient, Doctor $doctor): array
    {
        return [
            'request_type' => 'npt',
            'patient_id' => $patient->id,
            'service' => 'Nutricion parenteral',
            'diagnosis' => 'Diagnostico de integracion',
            'priority' => 'routine',
            'npt' => [
                'clinical_service' => 'Nutricion clinica',
                'registration' => 'REG-CBTA',
                'weight' => 70,
                'sex' => 'Masculino',
                'birth_date' => '1980-01-01',
                'route' => 'Central',
                'infusion_hours' => 24,
                'total_volume' => 1200,
                'npt_type' => 'Individualizada',
                'delivery_at' => now()->addHours(4)->format('Y-m-d H:i:s'),
                'destination_hospital' => 'Hospital Integrado',
                'doctor_name' => $doctor->full_name,
                'professional_license' => $doctor->professional_license,
            ],
        ];
    }

    private function createDoctor(): array
    {
        $user = User::query()->create(['name' => 'Dr. Horarios', 'username' => 'doctor.horarios', 'email' => 'horarios@test.local', 'role' => 'doctor', 'module' => 'doctor', 'status' => 'active']);
        $doctor = Doctor::query()->create(['user_id' => $user->id, 'full_name' => 'Dr. Horarios', 'professional_license' => 'CED-HOR', 'status' => 'active']);

        return [$user, $doctor];
    }
}
