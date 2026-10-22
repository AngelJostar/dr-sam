<?php

namespace Database\Seeders;

use App\Models\Hospitalization;
use App\Models\MedicalUnit;
use App\Models\Patient;
use App\Support\UnitAreaCatalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OperationalAreaEntryDemoSeeder extends Seeder
{
    public function run(): void
    {
        // Only this explicitly identified demo hospital receives these fixtures.
        $unit = MedicalUnit::query()->where('external_id', 'demo-hospital-general-dr-sam')->firstOrFail();
        $scenarios = [
            'uci' => ['UCI', 'Urgencias', 'Vigilancia respiratoria', 'UTI', [48, 62, 35]],
            'pediatric-icu' => ['UCIP', 'Urgencias pediátricas', 'Vigilancia respiratoria pediátrica', 'Hospitalización pediátrica', [3, 1, 7]],
            'uti' => ['UTI', 'UCI', 'Vigilancia posterior a cuidados intensivos', 'Hospitalización', [56, 40, 67]],
            'recovery' => ['REC', 'Quirófanos', 'Vigilancia posoperatoria', 'Hospitalización', [29, 52, 44]],
        ];

        DB::transaction(function () use ($unit, $scenarios): void {
            foreach ($scenarios as $type => [$code, $origin, $diagnosis, $destination, $ages]) {
                foreach ($ages as $index => $age) {
                    $number = str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);
                    $folio = 'DEMO-ING-'.$code.'-'.$number;
                    $patient = Patient::query()->firstOrCreate(['platform_number' => $folio], [
                        'first_name' => 'Paciente Demo', 'last_name' => $code.' '.$number,
                        'full_name' => 'Paciente Demo '.$code.' '.$number,
                        'birth_date' => today()->subYears($age)->subMonths($index + 1),
                        'sex' => $index % 2 === 0 ? 'female' : 'male', 'status' => 'active',
                        'metadata' => ['demo' => true, 'source' => 'operational_area_entry_demo',
                            'medical_unit_id' => $unit->id, 'record_number' => 'EXP-DEMO-'.$code.'-'.$number],
                    ]);
                    if (data_get($patient->metadata, 'source') !== 'operational_area_entry_demo'
                        || (int) data_get($patient->metadata, 'medical_unit_id') !== (int) $unit->id) {
                        throw new \RuntimeException('El identificador de paciente demo ya pertenece a otro registro.');
                    }

                    $discharged = $index === 2;
                    $admittedAt = today()->subDays([1, 2, 5][$index])->setTime(8 + $index, 30);
                    $dischargedAt = $discharged ? today()->subDays(2)->setTime(13, 0) : null;
                    // Re-running the seeder preserves existing records and any later edits.
                    Hospitalization::query()->firstOrCreate([
                        'patient_id' => $patient->id, 'event_type' => 'operational_demo_entry',
                    ], [
                        'hospital_name' => $unit->name, 'area' => UnitAreaCatalog::definitions()[$type]['label'],
                        'admitted_at' => $admittedAt, 'discharged_at' => $dischargedAt,
                        'reason' => $diagnosis.' (caso ficticio para demostración).',
                        'admission_diagnosis' => $diagnosis,
                        'discharge_diagnosis' => $discharged ? 'Evolución favorable (demostración)' : null,
                        'status' => $discharged ? 'discharged' : 'active',
                        'stay_days' => $discharged ? 3 : 0,
                        'metadata' => [
                            'demo' => true, 'source' => 'operational_area_entry_demo',
                            'medical_unit_id' => $unit->id, 'area_type' => $type, 'folio' => $folio,
                            'guardian' => ($type === 'pediatric-icu' ? 'Tutora' : 'Contacto responsable').' Demo '.$code.' '.$number,
                            'guardian_relationship' => $type === 'pediatric-icu' ? 'Madre' : 'Familiar',
                            'origin' => $origin, 'responsible_doctor' => 'Médico Demo '.$code,
                            'discharge_indication' => $discharged ? 'Egreso del área y traslado a '.$destination.'. Registro ficticio de demostración.' : null,
                            'discharge_doctor' => $discharged ? 'Médico Demo de egreso '.$code : null,
                            'transfer_area' => $discharged ? $destination : null,
                        ],
                    ]);
                }
            }
        });
    }
}
