<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_patient_user_can_open_native_blade_portal(): void
    {
        [$user] = $this->makePatient();

        $this->actingAs($user)
            ->get(route('patient.dashboard'))
            ->assertOk()
            ->assertSee('Portal del Paciente')
            ->assertSee('patient-assistant-native-screen')
            ->assertSee('patient-assistant-native-menu')
            ->assertSee('Soporte médico')
            ->assertSee('data-support-toggle', false)
            ->assertSee('aria-hidden="true"', false)
            ->assertSee('¿Cómo amaneciste hoy?')
            ->assertSee('Soy Dr. Sam, hazme una pregunta')
            ->assertSee('Mi Seguro')
            ->assertSee('Resumen de información')
            ->assertSee('Aseguradora no registrada')
            ->assertSee('data-insurance-tab="summary"', false)
            ->assertSee('data-insurance-form', false)
            ->assertSee('Consultas, diagnósticos y estudios recientes')
            ->assertSee('data-history-filter="consultation"', false)
            ->assertSee('Receta / Indicaciones')
            ->assertSee('Médicos y terapeutas')
            ->assertSee('Indicaciones activas y recetas emitidas')
            ->assertSee('Hospital donde se recetó')
            ->assertSee('Institución donde se recetó')
            ->assertSee('Citas y estudios programados')
            ->assertSee('data-calendar-filter="upcoming"', false)
            ->assertSee('Historial de consultas')
            ->assertSee('Dispositivos')
            ->assertSee('Registro')
            ->assertDontSee('>Comprar<', false)
            ->assertDontSee('Farmacia Digital')
            ->assertDontSee('Comunidades')
            ->assertSee('Paciente Acciones')
            ->assertDontSee('<iframe');
    }

    public function test_patient_can_update_own_profile(): void
    {
        [$user, $patient] = $this->makePatient();

        $this->actingAs($user)
            ->patch(route('patient.profile.update'), [
                'first_name' => 'Claudia',
                'last_name' => 'Salinas Vega',
                'birth_date' => '1988-07-20',
                'sex' => 'female',
                'curp' => 'SAVC880720MDFXXX01',
                'phone' => '5555550101',
                'email' => 'claudia@example.test',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('patients', [
            'id' => $patient->id,
            'full_name' => 'Claudia Salinas Vega',
            'phone' => '5555550101',
        ]);
    }

    public function test_patient_can_register_insurance_policy(): void
    {
        [$user, $patient] = $this->makePatient();

        $this->actingAs($user)
            ->post(route('patient.insurance.save'), [
                'policy_number' => 'POL-2026-001',
                'insurer_name' => 'Aseguradora Demo',
                'plan_name' => 'Plan Integral',
                'starts_at' => '2026-01-01',
                'ends_at' => '2026-12-31',
                'status' => 'active',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('insurance_policies', [
            'patient_id' => $patient->id,
            'policy_number' => 'POL-2026-001',
            'insurer_name' => 'Aseguradora Demo',
        ]);
    }

    private function makePatient(): array
    {
        $user = User::query()->create([
            'name' => 'Paciente Acciones',
            'username' => 'paciente.acciones',
            'email' => 'paciente.acciones@test.local',
            'role' => 'patient',
            'module' => 'patient',
            'status' => 'active',
        ]);

        $patient = Patient::query()->create([
            'user_id' => $user->id,
            'platform_number' => 'PAC-ACTIONS-001',
            'full_name' => 'Paciente Acciones',
            'status' => 'active',
        ]);

        return [$user, $patient];
    }
}
