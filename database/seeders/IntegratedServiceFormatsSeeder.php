<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class IntegratedServiceFormatsSeeder extends Seeder
{
    public function run(): void
    {
        // Associate only forms already implemented in this application.
        $formats = [
            'npt-request' => ['name' => 'Solicitud de nutrición parenteral', 'purpose' => 'request', 'description' => 'Datos del paciente, composición de la mezcla, administración y entrega.', 'source_view' => 'superadmin._mix_request_format'],
            'oncology-request' => ['name' => 'Solicitud de oncológicos', 'purpose' => 'request', 'description' => 'Datos del paciente, medicamentos, dosis, diluyentes y programación de entrega.', 'source_view' => 'superadmin._mix_request_format'],
            'consultation' => ['name' => 'Registro de consulta externa', 'purpose' => 'operation', 'description' => 'Registro y programación de consultas en la unidad.', 'source_view' => 'operational.outpatient'],
            'prescription' => ['name' => 'Receta médica', 'purpose' => 'execution', 'description' => 'Medicamentos, dosis, vía, frecuencia, duración e indicaciones de la atención.', 'source_view' => 'operational.outpatient'],
            'laboratory-request' => ['name' => 'Solicitud de estudios de laboratorio', 'purpose' => 'request', 'description' => 'Paciente, diagnóstico, estudios solicitados e indicaciones clínicas.', 'source_view' => 'doctor.dashboard'],
        ];
        $associations = [
            'central-de-mezclas' => ['npt-request', 'oncology-request'],
            'nutricion-parenteral' => ['npt-request'],
            'quimioterapia' => ['oncology-request'],
            'quimioterapias' => ['oncology-request'],
            'consulta-externa' => ['consultation', 'prescription'],
            'farmacia-externa' => ['prescription'],
            'laboratorio' => ['laboratory-request'],
            'laboratorio-clinico' => ['laboratory-request'],
        ];

        DB::transaction(function () use ($formats, $associations): void {
            foreach (Service::query()->get() as $service) {
                $key = Str::slug($service->external_id ?: $service->name);
                $keys = $associations[$key] ?? $associations[Str::slug($service->name)] ?? [];
                foreach ($keys as $formatKey) {
                    $service->formats()->firstOrCreate(['key' => $formatKey], $formats[$formatKey]);
                }
            }
        });
    }
}
