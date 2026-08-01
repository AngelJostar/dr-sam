<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\DoctorAvailabilityRule;
use App\Models\DoctorClinic;
use App\Models\ClinicalEncounter;
use App\Models\Patient;
use App\Models\PatientOrder;
use App\Models\Prescription;
use App\Models\ProviderRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
                    'infusion_rate' => 50,
                    'total_volume' => 1200,
                    'npt_type' => 'Individualizada',
                    'products' => ['glucose_50' => 100, 'amino_acids_standard_10' => 80],
                    'delivery_at' => '2026-07-24 10:00:00',
                    'destination_hospital' => 'Hospital de prueba',
                    'doctor_name' => $doctor->full_name,
                    'professional_license' => $doctor->professional_license,
                ];
            }
            if ($type === 'chemo') {
                $payload['oncology'] = [
                    'request_date' => '2026-07-23',
                    'weight' => 70,
                    'sex' => 'Masculino',
                    'medications' => [[
                        'medication' => 'Cisplatino',
                        'dose' => '50 mg',
                        'diluents' => ['CS'],
                        'dilution_volume' => 500,
                        'infusion_minutes' => 120,
                        'routes' => ['IV'],
                        'delivery_dates' => ['2026-07-24'],
                    ]],
                    'doctor_name' => $doctor->full_name,
                    'professional_license' => $doctor->professional_license,
                ];
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

    private function createDoctor(): array
    {
        $user = User::query()->create(['name' => 'Dr. Horarios', 'username' => 'doctor.horarios', 'email' => 'horarios@test.local', 'role' => 'doctor', 'module' => 'doctor', 'status' => 'active']);
        $doctor = Doctor::query()->create(['user_id' => $user->id, 'full_name' => 'Dr. Horarios', 'professional_license' => 'CED-HOR', 'status' => 'active']);

        return [$user, $doctor];
    }
}
