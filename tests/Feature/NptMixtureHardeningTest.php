<?php

namespace Tests\Feature;

use App\Models\Doctor;
use App\Models\MedicalUnit;
use App\Models\Patient;
use App\Models\ProviderRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NptMixtureHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_npt_rejects_invalid_clinical_and_integration_payloads(): void
    {
        [$user, $doctor, $patient] = $this->nptContext();
        $cases = [
            [fn (array $payload): array => data_set($payload, 'npt.weight', 0), 'npt.weight'],
            [fn (array $payload): array => data_set($payload, 'npt.delivery_at', now()->subHour()->format('Y-m-d H:i:s')), 'npt.delivery_at'],
            [fn (array $payload): array => data_set($payload, 'npt.delivery_at', now()->addHour()->format('Y-m-d H:i:s')), 'npt.delivery_at'],
            [function (array $payload): array {
                data_set($payload, 'npt.infusion_hours', 24);

                return data_set($payload, 'npt.infusion_rate', 50);
            }, 'npt.infusion_rate'],
            [function (array $payload): array {
                data_forget($payload, 'npt.infusion_hours');
                data_forget($payload, 'npt.infusion_rate');

                return $payload;
            }, 'npt.infusion_hours'],
            [fn (array $payload): array => data_set($payload, 'npt.infusion_hours', 0), 'npt.infusion_hours'],
            [function (array $payload): array {
                unset($payload['integration_items']);

                return $payload;
            }, 'integration_items'],
        ];

        foreach ($cases as [$mutate, $errorKey]) {
            $this->actingAs($user)
                ->from(route('doctor.dashboard'))
                ->post(route('doctor.service_requests.store'), $mutate($this->validPayload($patient, $doctor)))
                ->assertRedirect(route('doctor.dashboard'))
                ->assertSessionHasErrors([$errorKey]);

            $this->assertDatabaseCount('provider_requests', 0);
        }
    }

    public function test_npt_rejected_stock_does_not_leave_partial_records(): void
    {
        [$user, $doctor, $patient] = $this->nptContext(prevalidationValid: false);

        $this->actingAs($user)
            ->from(route('doctor.dashboard'))
            ->post(route('doctor.service_requests.store'), $this->validPayload($patient, $doctor))
            ->assertRedirect(route('doctor.dashboard'))
            ->assertSessionHasErrors(['integration_items']);

        $this->assertDatabaseCount('provider_requests', 0);
        $this->assertDatabaseCount('mixture_integrations', 0);
    }

    public function test_npt_valid_boundary_creates_only_an_authorization_pending_request(): void
    {
        [$user, $doctor, $patient] = $this->nptContext();
        $payload = $this->validPayload($patient, $doctor);
        data_set($payload, 'npt.delivery_at', now()->addMinutes(211)->format('Y-m-d H:i:s'));
        data_set($payload, 'npt.infusion_hours', 0.5);

        $this->actingAs($user)
            ->post(route('doctor.service_requests.store'), $payload)
            ->assertSessionHasNoErrors();

        $request = ProviderRequest::query()->sole();
        $this->assertSame('requested', $request->status);
        $this->assertSame(['nursing' => 'pending', 'pharmacy' => 'pending'], $request->payload['authorizations']);
        $this->assertSame('awaiting_authorizations', $request->mixtureIntegration?->sync_status);
    }

    private function nptContext(bool $prevalidationValid = true): array
    {
        config(['cbta.base_url' => 'http://cbta.test', 'cbta.token' => 'test-token']);
        $unit = MedicalUnit::query()->create([
            'name' => 'Hospital NPT Hardening', 'cbta_external_code' => 'CBTA-NPT-HARDENING', 'status' => 'active',
        ]);
        $user = User::query()->create([
            'name' => 'Doctor NPT Hardening', 'username' => 'doctor.npt.hardening',
            'email' => 'doctor.npt.hardening@example.test', 'role' => 'doctor', 'module' => 'doctor', 'status' => 'active',
        ]);
        $doctor = Doctor::query()->create([
            'user_id' => $user->id, 'medical_unit_id' => $unit->id,
            'full_name' => 'Doctor NPT Hardening', 'professional_license' => 'CED-NPT-HARDENING', 'status' => 'active',
        ]);
        $patient = Patient::query()->create([
            'platform_number' => 'PAC-NPT-HARDENING', 'full_name' => 'Paciente NPT Hardening',
            'primary_doctor_id' => $doctor->id, 'status' => 'active',
        ]);

        Http::fake([
            'http://cbta.test/api/internal/v1/mixture-requests/prevalidate' => Http::response(['data' => [
                'valid' => $prevalidationValid,
                'catalog_type' => 'npt', 'catalog_version' => 'npt-hardening-v1',
                'medical_unit' => ['external_code' => 'CBTA-NPT-HARDENING'],
                'items' => $prevalidationValid ? [['valid' => true]] : [],
                'errors' => $prevalidationValid ? [] : [[
                    'code' => 'insufficient_stock', 'message' => 'Inventario insuficiente para la prueba.',
                ]],
            ]]),
        ]);

        return [$user, $doctor, $patient];
    }

    private function validPayload(Patient $patient, Doctor $doctor): array
    {
        return [
            'request_type' => 'npt', 'patient_id' => $patient->id,
            'service' => 'Nutricion parenteral', 'diagnosis' => 'Prueba controlada', 'priority' => 'routine',
            'npt' => [
                'clinical_service' => 'Nutricion clinica', 'registration' => 'REG-NPT-HARDENING',
                'weight' => 70, 'sex' => 'Femenino', 'birth_date' => '1986-04-12',
                'route' => 'Central', 'infusion_hours' => 24, 'total_volume' => 1200,
                'npt_type' => 'Individualizada',
                'delivery_at' => now()->addHours(4)->format('Y-m-d H:i:s'),
                'destination_hospital' => 'Hospital NPT Hardening',
                'doctor_name' => $doctor->full_name, 'professional_license' => $doctor->professional_license,
            ],
            'integration_catalog_version' => 'npt-hardening-v1',
            'integration_items' => [[
                'catalog_item' => 'GLUCOSE-50|GLUCOSE-50-500ML', 'quantity' => 100, 'unit' => 'ml',
            ]],
        ];
    }
}
