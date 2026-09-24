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

class OncologyMixtureHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_oncology_rejects_incomplete_or_clinically_invalid_payloads(): void
    {
        [$user, $doctor, $patient] = $this->oncologyContext();

        $cases = [
            'missing height' => [
                function (array $payload): array {
                    unset($payload['oncology']['height']);

                    return $payload;
                },
                'oncology.height',
            ],
            'mixture count mismatch' => [
                fn (array $payload): array => data_set($payload, 'oncology.mixture_count', 2),
                'oncology.mixture_count',
            ],
            'duplicate delivery date' => [
                fn (array $payload): array => data_set($payload, 'oncology.mixtures.0.delivery_dates', ['2026-10-20 10:00', '2026-10-20 10:00']),
                'oncology.mixtures.0.delivery_dates',
            ],
            'past delivery date' => [
                fn (array $payload): array => data_set($payload, 'oncology.mixtures.0.delivery_dates', ['2020-01-01 10:00']),
                'oncology.mixtures.0.delivery_dates.0',
            ],
            'unknown catalog item' => [
                fn (array $payload): array => data_set($payload, 'oncology.mixtures.0.medications.0.catalog_item', 'UNKNOWN|UNKNOWN-P'),
                'oncology.medications.0.catalog_item',
            ],
            'incompatible diluent' => [
                fn (array $payload): array => data_set($payload, 'oncology.mixtures.0.medications.0.diluent_id', 999),
                'oncology.medications.0.diluent_id',
            ],
            'concentration below minimum' => [
                fn (array $payload): array => data_set($payload, 'oncology.mixtures.0.medications.0.dose', 1),
                'oncology.medications.0.dose',
            ],
            'set and infusor simultaneously' => [
                function (array $payload): array {
                    data_set($payload, 'oncology.mixtures.0.set_infusion', true);

                    return data_set($payload, 'oncology.mixtures.0.infusor_id', 9);
                },
                'oncology.medications.0.infusor_id',
            ],
        ];

        foreach ($cases as $name => [$mutate, $errorKey]) {
            $this->actingAs($user)
                ->from(route('doctor.dashboard'))
                ->post(route('doctor.service_requests.store'), $mutate($this->validPayload($patient, $doctor)))
                ->assertRedirect(route('doctor.dashboard'))
                ->assertSessionHasErrors([$errorKey]);

            $this->assertDatabaseCount('provider_requests', 0);
        }
    }

    public function test_oncology_accepts_concentration_boundaries_without_duplicate_creation(): void
    {
        [$user, $doctor, $patient] = $this->oncologyContext();
        $payload = $this->validPayload($patient, $doctor);

        $this->actingAs($user)
            ->post(route('doctor.service_requests.store'), $payload)
            ->assertSessionHasNoErrors();

        $request = ProviderRequest::query()->sole();
        $this->assertSame('requested', $request->status);
        $this->assertSame(['oncology' => 'pending', 'pharmacy' => 'pending'], $request->payload['authorizations']);
    }

    private function oncologyContext(): array
    {
        config(['cbta.base_url' => 'http://cbta.test', 'cbta.token' => 'test-token']);
        $unit = MedicalUnit::query()->create([
            'name' => 'Hospital Oncologia Hardening',
            'cbta_external_code' => 'CBTA-ONC-HARDENING',
            'status' => 'active',
        ]);
        $user = User::query()->create([
            'name' => 'Doctor Hardening', 'username' => 'doctor.hardening',
            'email' => 'doctor.hardening@example.test', 'role' => 'doctor', 'module' => 'doctor', 'status' => 'active',
        ]);
        $doctor = Doctor::query()->create([
            'user_id' => $user->id, 'medical_unit_id' => $unit->id,
            'full_name' => 'Doctor Hardening', 'professional_license' => 'CED-HARDENING', 'status' => 'active',
        ]);
        $patient = Patient::query()->create([
            'platform_number' => 'PAC-HARDENING', 'full_name' => 'Paciente Hardening',
            'primary_doctor_id' => $doctor->id, 'status' => 'active',
        ]);

        Http::fake([
            'http://cbta.test/api/internal/v1/medical-units/CBTA-ONC-HARDENING/catalogs/oncology' => Http::response(['data' => [
                'catalog_version' => 'onc-hardening-v1',
                'items' => [[
                    'product_code' => 'MED-A', 'presentation_code' => 'MED-A-P',
                    'generic_name' => 'Medicamento A',
                    'diluents' => [['id' => 1]], 'administration_routes' => [['id' => 1]],
                    'concentration' => ['min' => 1, 'max' => 40], 'requires_infusor' => true,
                ]],
                'infusors' => [['id' => 9]],
            ]]),
            'http://cbta.test/api/internal/v1/mixture-requests/prevalidate' => Http::response(['data' => [
                'valid' => true, 'catalog_type' => 'oncology', 'catalog_version' => 'onc-hardening-v1',
                'medical_unit' => ['external_code' => 'CBTA-ONC-HARDENING'], 'items' => [['valid' => true]], 'errors' => [],
            ]]),
        ]);

        return [$user, $doctor, $patient];
    }

    private function validPayload(Patient $patient, Doctor $doctor): array
    {
        return [
            'request_type' => 'chemo', 'patient_id' => $patient->id,
            'service' => 'Quimioterapia', 'diagnosis' => 'Prueba controlada', 'priority' => 'routine',
            'oncology' => [
                'request_date' => '2026-10-19', 'facility' => 'Hospital Oncologia Hardening',
                'floor' => '2', 'bed' => '201', 'patient_identifier' => 'REG-HARDENING',
                'sex' => 'Femenino', 'age' => 40, 'weight' => 65, 'height' => 165,
                'birth_date' => '1986-04-12', 'doctor_name' => $doctor->full_name,
                'professional_license' => $doctor->professional_license, 'mixture_count' => 1,
                'mixtures' => [[
                    'medications' => [[
                        'catalog_item' => 'MED-A|MED-A-P', 'medication' => 'Medicamento A',
                        'dose' => 100, 'diluent_id' => 1, 'route_id' => 1,
                    ]],
                    'dilution_volume' => 100, 'infusion_minutes' => 60,
                    'delivery_dates' => ['2026-10-20 10:00'], 'set_infusion' => false,
                ]],
            ],
        ];
    }
}
