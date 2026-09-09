<?php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\MedicalUnit;
use App\Models\Patient;
use App\Models\ProcedureArea;
use App\Models\Provider;
use App\Models\ProviderRequest;
use App\Models\ProviderRequestStatusEvent;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class OncologyInfusionDemoSeeder extends Seeder
{
    public function run(): void
    {
        $unit = MedicalUnit::query()
            ->where('external_id', 'demo-hospital-general-dr-sam')
            ->firstOrFail();
        $doctor = Doctor::query()->where('external_id', 'doc-carter')->firstOrFail();
        $patient = Patient::query()->where('platform_number', '100000001')->firstOrFail();
        $provider = Provider::query()->firstOrCreate(
            ['name' => 'Central de Mezclas Demo'],
            [
                'provider_type' => 'chemo',
                'status' => 'active',
                'metadata' => ['source' => 'oncology_infusion_demo'],
            ],
        );

        $room = ProcedureArea::query()->updateOrCreate(
            [
                'medical_unit_id' => $unit->id,
                'type' => 'infusion',
                'unit_number' => 'SI-DEMO-01',
            ],
            [
                'location' => 'Centro Oncologico - Primer piso',
                'floor' => '1',
                'simultaneous_capacity' => 4,
                'responsible_name' => 'Enf. Monica Reyes',
                'status' => 'active',
                'metadata' => ['source' => 'oncology_infusion_demo'],
            ],
        );

        foreach (range(1, 6) as $dayOfWeek) {
            $room->schedules()->updateOrCreate(
                [
                    'day_of_week' => $dayOfWeek,
                    'starts_at' => '07:00',
                    'ends_at' => '19:00',
                ],
                ['active' => true],
            );
        }

        $providerRequest = ProviderRequest::query()->firstOrCreate(
            ['external_id' => 'ONC-DEMO-0001'],
            [
                'provider_id' => $provider->id,
                'patient_id' => $patient->id,
                'medical_unit_id' => $unit->id,
                'request_type' => 'chemo',
                'status' => 'requested',
                'requested_at' => Carbon::parse('2026-09-02 08:30:00'),
                'required_at' => Carbon::parse('2026-09-08 09:00:00'),
                'payload' => [
                    'source' => 'doctor_module',
                    'doctor_id' => $doctor->id,
                    'doctor' => $doctor->full_name,
                    'service' => 'Quimioterapia',
                    'diagnosis' => 'Cancer de mama',
                    'notes' => 'Programar infusion y validar signos vitales antes de iniciar.',
                    'medication' => 'Paclitaxel',
                    'dose' => '300 mg',
                    'volume' => 500,
                    'priority' => 'routine',
                    'infusion_room_status' => 'pending',
                    'clinical_format' => [
                        'request_date' => '2026-09-02',
                        'facility' => $unit->name,
                        'floor' => '2',
                        'bed' => '204',
                        'patient_identifier' => $patient->platform_number,
                        'sex' => 'Femenino',
                        'age' => 40,
                        'weight' => 68,
                        'height' => 162,
                        'birth_date' => '1986-04-12',
                        'body_surface' => 1.72,
                        'delivery_method' => 'Entrega en la unidad',
                        'doctor_name' => $doctor->full_name,
                        'professional_license' => $doctor->professional_license,
                        'medications' => [[
                            'medication' => 'Paclitaxel',
                            'dose' => '300',
                            'diluents' => ['CS'],
                            'dilution_volume' => 500,
                            'boluses_per_day' => 1,
                            'infusion_minutes' => 180,
                            'delivery_dates' => ['2026-09-08'],
                        ]],
                    ],
                    'attachment' => null,
                    'authorizations' => [
                        'operational' => 'pending',
                        'pharmacy' => 'pending',
                    ],
                ],
            ],
        );

        ProviderRequestStatusEvent::query()->firstOrCreate(
            [
                'provider_request_id' => $providerRequest->id,
                'status' => 'requested',
                'actor' => $doctor->full_name,
            ],
            [
                'occurred_at' => Carbon::parse('2026-09-02 08:30:00'),
                'metadata' => ['source' => 'doctor_module', 'demo' => true],
            ],
        );
    }
}
