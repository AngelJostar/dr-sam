<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\DoctorClinic;
use App\Models\DoctorAvailabilityRule;
use App\Models\MedicalUnit;
use App\Models\Prescription;
use App\Models\OperationalArea;
use App\Models\OperationalProfile;
use App\Models\Patient;
use App\Models\ProcedureArea;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OutpatientModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_operational_user_can_use_outpatient_module(): void
    {
        $user = User::query()->create(['name' => 'Consulta Externa', 'username' => 'op.consulta.test', 'role' => 'operational', 'module' => 'operational_outpatient', 'status' => 'active']);
        $unit = MedicalUnit::query()->create(['name' => 'H.G. CHIMALHUACAN', 'code' => 'MCIMB001841', 'status' => 'active', 'metadata' => ['procedure_areas' => [['id' => 'c-5', 'type' => 'consulting', 'number' => 'Consultorio 5', 'location' => 'Consulta externa', 'floor' => 'PB', 'capacity' => 1, 'status' => 'active', 'schedule' => ['monday' => ['enabled' => true, 'start' => '08:00', 'end' => '16:00']]]]]]);
        $area = OperationalArea::query()->create(['key' => 'consulta-externa-test', 'label' => 'Consulta Externa']);
        OperationalProfile::query()->create(['user_id' => $user->id, 'medical_unit_id' => $unit->id, 'operational_area_id' => $area->id, 'status' => 'active']);
        $patient = Patient::query()->create(['full_name' => 'Arturo Hernandez', 'platform_number' => 'USR-HGCH-0001', 'status' => 'active']);
        $doctor = Doctor::query()->create(['medical_unit_id' => $unit->id, 'full_name' => 'Dr. Carter Jimmy', 'specialty' => 'Medicina Interna', 'status' => 'active']);

        foreach (['agenda' => 'Agenda institucional generada', 'prescriptions' => 'Recetas emitidas', 'patients' => 'Catálogo de pacientes', 'rooms' => 'Catálogo de consultorios'] as $section => $text) {
            $this->actingAs($user)->get(route('outpatient.dashboard', ['section' => $section]))->assertOk()->assertSee($text);
        }

        $this->actingAs($user)
            ->get(route('outpatient.dashboard', ['section' => 'agenda']))
            ->assertOk()
            ->assertDontSee('<th>Acciones</th>', false)
            ->assertDontSee('name="status" onchange=', false);

        $this->actingAs($user)
            ->get(route('outpatient.dashboard', ['section' => 'prescriptions']))
            ->assertOk()
            ->assertDontSee('<th>Acciones</th>', false)
            ->assertDontSee('>Ver</a>', false)
            ->assertDontSee('>Editar</a>', false);

        $this->artisan('drsam:migrate-procedure-areas', ['--unit' => $unit->id])->assertSuccessful();
        $this->artisan('drsam:migrate-procedure-areas', ['--unit' => $unit->id])->assertSuccessful();
        $room = $unit->procedureAreas()->with('schedules')->firstOrFail();
        $this->assertSame(1, $unit->procedureAreas()->count());
        $this->assertSame(1, $room->schedules()->count());
        $this->actingAs($user)->get(route('outpatient.dashboard', ['section' => 'rooms']))
            ->assertOk()->assertSee('Consultorio 5')->assertSee('c-5');

        $this->actingAs($user)->post(route('outpatient.appointments.store'), [
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'specialty' => 'Medicina Interna',
            'location' => 'Consulta externa',
            'procedure_area_id' => $room->id,
            'modality' => 'Presencial',
            'starts_at' => now()->next('Monday')->setTime(9, 0)->format('Y-m-d H:i:s'),
            'duration' => 30,
            'reason' => 'Primera Vez',
        ])->assertRedirect(route('outpatient.dashboard'));

        $appointment = Appointment::query()->firstOrFail();
        $this->assertSame('scheduled', $appointment->status);
        $this->assertSame($room->id, $appointment->procedure_area_id);
        $this->assertSame('Consultorio 5', $appointment->location);

        $this->actingAs($user)->patch(route('outpatient.appointments.update', $appointment), ['status' => 'completed'])->assertRedirect();
        $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'completed']);
        $this->assertDatabaseHas('appointment_status_events', [
            'appointment_id' => $appointment->id,
            'from_status' => 'scheduled',
            'to_status' => 'completed',
        ]);
    }

    public function test_outpatient_respects_published_doctor_schedule_and_rejects_overlaps(): void
    {
        $user = User::query()->create(['name' => 'Consulta Externa', 'username' => 'op.agenda.test', 'role' => 'operational', 'module' => 'operational_outpatient', 'status' => 'active']);
        $unit = MedicalUnit::query()->create(['name' => 'Unidad Agenda', 'code' => 'UNI-AGENDA', 'status' => 'active']);
        $area = OperationalArea::query()->create(['key' => 'consulta-agenda', 'label' => 'Consulta Externa']);
        OperationalProfile::query()->create(['user_id' => $user->id, 'medical_unit_id' => $unit->id, 'operational_area_id' => $area->id, 'status' => 'active']);
        $patient = Patient::query()->create(['full_name' => 'Paciente Agenda', 'platform_number' => 'PAC-AGENDA', 'status' => 'active']);
        $doctor = Doctor::query()->create(['medical_unit_id' => $unit->id, 'full_name' => 'Dra. Agenda', 'specialty' => 'Medicina Interna', 'status' => 'active']);
        $clinic = DoctorClinic::query()->create(['doctor_id' => $doctor->id, 'medical_unit_id' => $unit->id, 'name' => 'Consultorio Agenda', 'status' => 'active']);
        DoctorAvailabilityRule::query()->create(['doctor_id' => $doctor->id, 'doctor_clinic_id' => $clinic->id, 'weekday' => 1, 'start_time' => '08:00', 'end_time' => '12:00', 'recurrence_start' => '2026-07-01', 'status' => 'published']);
        $payload = ['patient_id' => $patient->id, 'doctor_id' => $doctor->id, 'specialty' => 'Medicina Interna', 'location' => 'Consulta externa', 'modality' => 'Presencial', 'duration' => 30, 'reason' => 'Primera Vez'];

        $this->actingAs($user)->post(route('outpatient.appointments.store'), [...$payload, 'starts_at' => '2026-07-27 09:00:00'])->assertSessionHasNoErrors();
        $this->actingAs($user)->post(route('outpatient.appointments.store'), [...$payload, 'starts_at' => '2026-07-27 09:15:00'])->assertSessionHasErrors('starts_at');
        $this->actingAs($user)->post(route('outpatient.appointments.store'), [...$payload, 'starts_at' => '2026-07-28 09:00:00'])->assertSessionHasErrors('starts_at');

        $this->assertSame(1, Appointment::query()->count());
    }

    public function test_outpatient_patient_catalog_supports_create_and_edit_like_native(): void
    {
        $user = User::query()->create(['name' => 'Consulta Pacientes', 'username' => 'op.pacientes.test', 'role' => 'operational', 'module' => 'operational_outpatient', 'status' => 'active']);
        $unit = MedicalUnit::query()->create(['name' => 'H.G. Pacientes', 'code' => 'UNI-PAC', 'status' => 'active']);
        $area = OperationalArea::query()->create(['key' => 'consulta-pacientes', 'label' => 'Consulta Externa']);
        OperationalProfile::query()->create(['user_id' => $user->id, 'medical_unit_id' => $unit->id, 'operational_area_id' => $area->id, 'status' => 'active']);

        $payload = ['first_name' => 'Arturo', 'last_name' => 'Hernandez', 'age' => 54, 'curp' => 'HEAA720314HMCRRR08', 'nss_federal' => '04967231458', 'nss_estatal' => 'MEX-248391', 'platform_number' => 'USR-HGCH-0001', 'state' => 'México'];
        $this->actingAs($user)->post(route('outpatient.patients.store'), $payload)->assertRedirect(route('outpatient.dashboard', ['section' => 'patients']));
        $patient = Patient::query()->firstOrFail();

        $this->actingAs($user)->get(route('outpatient.dashboard', ['section' => 'patients']))
            ->assertOk()->assertSee('NSS federal')->assertSee('Editar')->assertSee('04967231458');

        $this->actingAs($user)->patch(route('outpatient.patients.update', $patient), [...$payload, 'last_name' => 'Hernandez Villanueva', 'nss_estatal' => 'MEX-830422'])
            ->assertRedirect(route('outpatient.dashboard', ['section' => 'patients']));
        $this->assertDatabaseHas('patients', ['id' => $patient->id, 'full_name' => 'Arturo Hernandez Villanueva']);
        $this->assertSame('MEX-830422', Patient::query()->findOrFail($patient->id)->metadata['nss_estatal']);
    }

    public function test_outpatient_room_catalog_supports_create_edit_and_weekly_availability(): void
    {
        $user = User::query()->create(['name' => 'Consulta Consultorios', 'username' => 'op.consultorios.test', 'role' => 'operational', 'module' => 'operational_outpatient', 'status' => 'active']);
        $unit = MedicalUnit::query()->create(['name' => 'Unidad Consulta', 'code' => 'UCE-01', 'status' => 'active']);
        OperationalProfile::query()->create(['user_id' => $user->id, 'medical_unit_id' => $unit->id, 'status' => 'active']);

        $payload = ['name' => 'Consultorio 5', 'code' => 'C-05', 'location' => 'Consulta externa', 'specialty' => 'Medicina interna', 'modality' => 'Presencial', 'status' => 'active', 'capacity' => 2, 'schedule' => ['monday' => ['enabled' => 1, 'start' => '08:00', 'end' => '16:00']]];
        $this->actingAs($user)->post(route('outpatient.rooms.store'), $payload)
            ->assertRedirect(route('outpatient.dashboard', ['section' => 'rooms']));

        $room = ProcedureArea::query()->where('unit_number', 'C-05')->firstOrFail();
        $this->assertSame('Medicina interna', data_get($room->metadata, 'specialty'));
        $this->assertDatabaseHas('procedure_area_schedules', ['procedure_area_id' => $room->id, 'day_of_week' => 1]);
        $this->actingAs($user)->get(route('outpatient.dashboard', ['section' => 'rooms']))->assertOk()->assertSee('Consultorio 5')->assertSee('Editar');
        $this->actingAs($user)->get(route('outpatient.dashboard', ['section' => 'rooms', 'edit' => $room->id]))
            ->assertOk()
            ->assertSee('Calendario de disponibilidad')
            ->assertSee('data-room-calendar', false)
            ->assertSee('data-calendar-grid', false)
            ->assertSee('data-add-range', false)
            ->assertDontSee('&amp;quot;', false);

        $this->actingAs($user)->patch(route('outpatient.rooms.update', $room), [...$payload, 'name' => 'Consultorio Integral', 'status' => 'inactive'])
            ->assertRedirect(route('outpatient.dashboard', ['section' => 'rooms']));
        $this->assertSame('inactive', $room->fresh()->status);
        $this->assertSame('Consultorio Integral', data_get($room->fresh()->metadata, 'name'));
    }

    public function test_outpatient_room_can_be_deleted_only_when_it_has_no_appointments(): void
    {
        $user = User::query()->create(['name' => 'Consulta Consultorios', 'username' => 'op.consultorios.delete', 'role' => 'operational', 'module' => 'operational_outpatient', 'status' => 'active']);
        $unit = MedicalUnit::query()->create(['name' => 'Unidad Consulta', 'code' => 'UCE-DELETE', 'status' => 'active']);
        OperationalProfile::query()->create(['user_id' => $user->id, 'medical_unit_id' => $unit->id, 'status' => 'active']);

        $availableRoom = ProcedureArea::query()->create(['medical_unit_id' => $unit->id, 'type' => 'consulting', 'unit_number' => 'C-DELETE', 'status' => 'active']);
        $this->actingAs($user)->delete(route('outpatient.rooms.destroy', $availableRoom))
            ->assertRedirect(route('outpatient.dashboard', ['section' => 'rooms']));
        $this->assertDatabaseMissing('procedure_areas', ['id' => $availableRoom->id]);

        $occupiedRoom = ProcedureArea::query()->create(['medical_unit_id' => $unit->id, 'type' => 'consulting', 'unit_number' => 'C-HISTORY', 'status' => 'active']);
        $patient = Patient::query()->create(['full_name' => 'Paciente Historial', 'status' => 'active']);
        $doctor = Doctor::query()->create(['medical_unit_id' => $unit->id, 'full_name' => 'Dra. Historial', 'professional_license' => 'CED-HISTORY', 'specialty' => 'Medicina interna', 'status' => 'active']);
        Appointment::query()->create([
            'medical_unit_id' => $unit->id,
            'procedure_area_id' => $occupiedRoom->id,
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'specialty' => 'Medicina interna',
            'location' => 'C-HISTORY',
            'modality' => 'Presencial',
            'status' => 'scheduled',
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addMinutes(30),
        ]);

        $this->actingAs($user)->delete(route('outpatient.rooms.destroy', $occupiedRoom))
            ->assertRedirect(route('outpatient.dashboard', ['section' => 'rooms']))
            ->assertSessionHasErrors('room');
        $this->assertDatabaseHas('procedure_areas', ['id' => $occupiedRoom->id]);
    }

    public function test_outpatient_calendar_admin_updates_room_slots_used_by_agenda(): void
    {
        $user = User::query()->create(['name' => 'Agenda Admin', 'username' => 'op.calendar.test', 'role' => 'operational', 'module' => 'operational_outpatient', 'status' => 'active']);
        $unit = MedicalUnit::query()->create(['name' => 'Unidad Calendario', 'code' => 'CAL-01', 'status' => 'active']);
        OperationalProfile::query()->create(['user_id' => $user->id, 'medical_unit_id' => $unit->id, 'status' => 'active']);
        $room = ProcedureArea::query()->create(['medical_unit_id' => $unit->id, 'type' => 'consulting', 'unit_number' => 'C-10', 'status' => 'active']);

        $this->actingAs($user)->get(route('outpatient.dashboard', ['admin' => 1]))
            ->assertOk()->assertSee('Administrar Calendario')->assertSee('Guardar slots');
        $this->actingAs($user)->get(route('outpatient.dashboard', ['new' => 1]))
            ->assertOk()->assertSee('Número de seguridad social Federal');
        $this->actingAs($user)->patch(route('outpatient.calendar.update', $room), ['day_of_week' => 2, 'specialty' => 'Cardiología', 'starts_at' => '09:00', 'ends_at' => '13:00', 'duration' => 45])
            ->assertRedirect(route('outpatient.dashboard', ['admin' => 1]));

        $this->assertDatabaseHas('procedure_area_schedules', ['procedure_area_id' => $room->id, 'day_of_week' => 2, 'starts_at' => '09:00', 'ends_at' => '13:00']);
        $this->assertSame('Cardiología', data_get($room->fresh()->metadata, 'specialty'));
        $this->assertSame(45, data_get($room->fresh()->metadata, 'slot_durations.2'));
    }

    public function test_outpatient_prescriptions_support_create_view_dynamic_items_and_edit(): void
    {
        $user = User::query()->create(['name' => 'Recetas Consulta', 'username' => 'op.recetas.test', 'role' => 'operational', 'module' => 'operational_outpatient', 'status' => 'active']);
        $unit = MedicalUnit::query()->create(['name' => 'Unidad Recetas', 'code' => 'RX-UNIT', 'status' => 'active']);
        OperationalProfile::query()->create(['user_id' => $user->id, 'medical_unit_id' => $unit->id, 'status' => 'active']);
        $patient = Patient::query()->create(['full_name' => 'Paciente Receta', 'platform_number' => 'PAC-RX', 'status' => 'active']);
        $doctor = Doctor::query()->create(['medical_unit_id' => $unit->id, 'full_name' => 'Dra. Receta', 'professional_license' => 'CED-RX', 'specialty' => 'Medicina interna', 'status' => 'active']);
        $payload = ['patient_id' => $patient->id, 'doctor_id' => $doctor->id, 'issued_at' => '2026-07-21', 'status' => 'active', 'diagnosis' => 'DiagnÃ³stico inicial', 'notes' => 'Tomar con alimentos', 'items' => [
            ['medication_name' => 'Paracetamol', 'cnis' => '010.000.0104.00', 'dose' => '500 mg', 'presentation' => 'Tabletas', 'route' => 'Oral', 'frequency' => 'Cada 8 horas', 'duration' => '5 dÃ­as', 'quantity' => '15', 'instructions' => 'DespuÃ©s de alimentos'],
            ['medication_name' => 'Metamizol', 'dose' => '1 g', 'route' => 'Oral', 'frequency' => 'Cada 12 horas'],
        ]];

        $this->actingAs($user)->get(route('outpatient.dashboard', ['section' => 'prescriptions', 'new' => 1]))
            ->assertOk()->assertSee('Medicamentos indicados')->assertSee('data-add-prescription-item', false);
        $this->actingAs($user)->post(route('outpatient.prescriptions.store'), $payload)
            ->assertRedirect(route('outpatient.dashboard', ['section' => 'prescriptions']));

        $prescription = Prescription::query()->with('items')->firstOrFail();
        $this->assertCount(2, $prescription->items);
        $this->assertSame($unit->id, data_get($prescription->metadata, 'medical_unit_id'));
        $this->actingAs($user)->get(route('outpatient.dashboard', ['section' => 'prescriptions', 'view' => $prescription->id]))
            ->assertOk()->assertSee('Paracetamol')->assertSee('010.000.0104.00')->assertDontSee('>Editar</a>', false);

        $this->actingAs($user)->patch(route('outpatient.prescriptions.update', $prescription), [...$payload, 'diagnosis' => 'DiagnÃ³stico actualizado', 'items' => [$payload['items'][0]]])
            ->assertRedirect(route('outpatient.dashboard', ['section' => 'prescriptions']));
        $this->assertSame('DiagnÃ³stico actualizado', data_get($prescription->fresh()->metadata, 'diagnosis'));
        $this->assertSame(1, $prescription->items()->count());
    }
}
