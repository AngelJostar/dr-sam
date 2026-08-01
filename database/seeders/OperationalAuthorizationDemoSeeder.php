<?php

namespace Database\Seeders;

use App\Models\MedicalUnit;
use App\Models\Patient;
use App\Models\Provider;
use App\Models\ProviderRequest;
use App\Models\ProviderRequestStatusEvent;
use Illuminate\Database\Seeder;

class OperationalAuthorizationDemoSeeder extends Seeder
{
    public function run(): void
    {
        $unit = MedicalUnit::query()
            ->where('code', 'DRSAM-DEMO')
            ->orWhere('clues', 'DRSAM-DEMO')
            ->firstOrFail();

        $provider = Provider::query()->updateOrCreate(
            ['name' => 'Proveedor operativo demo'],
            ['provider_type' => 'npt', 'status' => 'active'],
        );

        $rows = [
            ['OP-NPT-1001', 'Claudia Beatriz Salinas Vega', 'OP-PAC-1001', 'npt', 'Medicina interna', 1200, 'pending', 'pending'],
            ['OP-NPT-1002', 'Guillermo Guerrero', 'OP-PAC-1002', 'npt', 'Cirugia general', 1000, 'approved', 'pending'],
            ['OP-NPT-1003', 'Arturo Hernandez', 'OP-PAC-1003', 'nutrition', 'Terapia intensiva', 1500, 'approved', 'approved'],
            ['OP-NPT-1004', 'Javier Alejandro Hernandez', 'OP-PAC-1004', 'import', 'Medicina interna', 900, 'rejected', 'pending'],
            ['OP-NPT-1005', 'Maria Fernanda Lopez', 'OP-PAC-1005', 'npt', 'Pediatria', 1100, 'pending', 'approved'],
        ];

        foreach ($rows as $index => [$folio, $name, $platform, $type, $service, $volume, $operational, $pharmacy]) {
            $patient = Patient::query()->updateOrCreate(
                ['platform_number' => $platform],
                ['full_name' => $name, 'status' => 'active'],
            );

            $providerRequest = ProviderRequest::query()->updateOrCreate(
                ['external_id' => $folio],
                [
                    'provider_id' => $provider->id,
                    'patient_id' => $patient->id,
                    'medical_unit_id' => $unit->id,
                    'request_type' => $type,
                    'status' => 'requested',
                    'requested_at' => now()->subDays($index),
                    'required_at' => now()->addDays(2 + $index),
                    'payload' => [
                        'doctor' => 'Carter Jimmy',
                        'service' => $service,
                        'volume' => $volume,
                        'mix_status' => 'No Estable',
                        'authorizations' => [
                            'operational' => $operational,
                            'pharmacy' => $pharmacy,
                        ],
                        'authorization_requirements' => ['operational', 'pharmacy'],
                    ],
                ],
            );

            ProviderRequestStatusEvent::query()->updateOrCreate(
                ['provider_request_id' => $providerRequest->id, 'status' => 'requested'],
                [
                    'actor' => 'Datos demo',
                    'notes' => 'Solicitud de demostracion para area operativa.',
                    'occurred_at' => $providerRequest->requested_at,
                    'metadata' => ['source' => 'demo_seed'],
                ],
            );
        }
    }
}
