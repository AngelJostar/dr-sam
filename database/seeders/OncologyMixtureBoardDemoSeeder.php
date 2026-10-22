<?php

namespace Database\Seeders;

use App\Models\MedicalUnit;
use App\Models\Patient;
use App\Models\Provider;
use App\Models\ProviderRequest;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OncologyMixtureBoardDemoSeeder extends Seeder
{
    public function run(): void
    {
        // Explicit demo hospital only; never attach these fixtures to an arbitrary unit.
        $unit = MedicalUnit::query()->where('external_id', 'demo-hospital-general-dr-sam')->firstOrFail();
        DB::transaction(function () use ($unit): void {
            $provider = Provider::query()->firstOrCreate(['name' => 'Central de Mezclas Demo'], [
                'provider_type' => 'chemo', 'status' => 'active', 'metadata' => ['demo' => true],
            ]);
            foreach (['requested', 'accepted', 'preparing', 'ready', 'in_route', 'delivered', 'cancelled', 'requested'] as $index => $status) {
                $number = str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);
                $patient = Patient::query()->firstOrCreate(['platform_number' => 'DEMO-CM-ONC-'.$number], [
                    'first_name' => 'Paciente Demo', 'last_name' => $number, 'full_name' => 'Paciente Demo '.$number,
                    'status' => 'active', 'metadata' => ['demo' => true, 'medical_unit_id' => $unit->id],
                ]);
                $record = ProviderRequest::query()->firstOrCreate(['external_id' => 'DEMO-CM-ONC-'.$number], [
                    'provider_id' => $provider->id, 'patient_id' => $patient->id, 'medical_unit_id' => $unit->id,
                    'request_type' => 'chemo', 'status' => $status, 'requested_at' => now()->startOfDay()->subDays($index)->addHours(8),
                    'required_at' => $index === 7 ? null : now()->startOfDay()->addDays($index < 5 ? 1 : -1)->addHours(9 + $index),
                    'payload' => [
                        'source' => 'oncology_mixture_board_demo', 'demo' => true, 'service' => 'Quimioterapia',
                        'doctor' => 'Médico de demostración', 'notes' => 'Registro ficticio para demostrar el seguimiento de mezclas.',
                        'lot' => $index > 1 ? 'DEMO-ONC-'.now()->format('Ym').'-'.$number : null,
                        'authorizations' => ['oncology' => $index ? 'approved' : 'pending', 'pharmacy' => $index ? 'approved' : 'pending'],
                    ],
                ]);
                // Reruns preserve progress, messages and adjustments already edited in the demo.
                if (! $record->wasRecentlyCreated) {
                    continue;
                }
                $record->statusEvents()->create(['status' => $status, 'actor' => 'Demostración', 'occurred_at' => $record->requested_at, 'metadata' => ['demo' => true]]);
                if (in_array($index, [1, 4], true)) {
                    $record->interactions()->create(['type' => 'message', 'body' => 'Mensaje de demostración: seguimiento de la entrega programada.']);
                }
                if ($index === 1) {
                    $record->interactions()->create(['type' => 'adjustment', 'status' => 'open', 'body' => 'Ajuste de demostración: confirmar horario de entrega con la unidad.']);
                }
            }
        });
    }
}
