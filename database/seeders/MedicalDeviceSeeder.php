<?php

namespace Database\Seeders;

use App\Models\MedicalDevice;
use Illuminate\Database\Seeder;

class MedicalDeviceSeeder extends Seeder
{
    public function run(): void
    {
        $devices = [
            ['name' => 'Bascula inteligente', 'code' => 'dm-004', 'category' => 'Antropometria', 'manufacturer' => 'Leoncio Gonzalez Martinez', 'model' => 'BodyScan Plus', 'connectivity' => 'WiFi / Bluetooth', 'linked_module' => 'Paciente - Cuidado Familiar', 'recorded_data' => 'Peso, IMC y composicion corporal', 'compatibility' => 'iOS / Android', 'status' => 'active'],
            ['name' => 'Baumanometro digital', 'code' => 'dm-002', 'category' => 'Signos vitales', 'manufacturer' => 'HED Distribuidora Farmaceutica S.A. de C.V.', 'model' => 'PressureCare 200', 'connectivity' => 'Bluetooth', 'linked_module' => 'Paciente - Historial clinico', 'recorded_data' => 'Presion arterial y pulso', 'compatibility' => 'iOS / Android', 'status' => 'active'],
            ['name' => 'Concentrador de oxigeno con sensor', 'code' => 'dm-008', 'category' => 'Respiratorio', 'manufacturer' => 'Industrias Novaceramic, S.A. de C.V.', 'model' => 'O2 Home Connect', 'connectivity' => 'WiFi', 'linked_module' => 'Paciente - Seguimiento remoto', 'recorded_data' => 'Horas de uso y flujo de oxigeno', 'compatibility' => 'iOS / Android', 'status' => 'evaluation'],
            ['name' => 'Electrocardiografo portatil', 'code' => 'dm-006', 'category' => 'Cardiologia', 'manufacturer' => 'Grupo Unimmedical Soluciones S.A. de C.V.', 'model' => 'ECG Pocket 6L', 'connectivity' => 'Bluetooth / USB', 'linked_module' => 'Medico - Consulta Privada', 'recorded_data' => 'ECG de 6 derivaciones', 'compatibility' => 'Windows / Android', 'status' => 'active'],
            ['name' => 'Glucometro conectado', 'code' => 'dm-001', 'category' => 'Monitoreo metabolico', 'manufacturer' => 'Grupo Unimmedical Soluciones S.A. de C.V.', 'model' => 'GlucoLink BT', 'connectivity' => 'Bluetooth', 'linked_module' => 'Paciente - Salud Inteligente', 'recorded_data' => 'Glucosa capilar', 'compatibility' => 'iOS / Android', 'status' => 'active'],
            ['name' => 'Monitor Holter', 'code' => 'dm-007', 'category' => 'Cardiologia', 'manufacturer' => 'HED Distribuidora Farmaceutica S.A. de C.V.', 'model' => 'Holter 24 Pro', 'connectivity' => 'USB / Nube', 'linked_module' => 'Medico - Reportes clinicos', 'recorded_data' => 'Ritmo cardiaco continuo', 'compatibility' => 'Windows', 'status' => 'active'],
            ['name' => 'Oximetro de pulso', 'code' => 'dm-003', 'category' => 'Signos vitales', 'manufacturer' => 'Industrias Novaceramic, S.A. de C.V.', 'model' => 'OxiTrack Mini', 'connectivity' => 'Bluetooth', 'linked_module' => 'Paciente - Seguimiento remoto', 'recorded_data' => 'SpO2 y frecuencia cardiaca', 'compatibility' => 'Android', 'status' => 'active'],
            ['name' => 'Termometro digital infrarrojo', 'code' => 'dm-005', 'category' => 'Signos vitales', 'manufacturer' => 'Plomecsa (Plomeria Mexicana)', 'model' => 'ThermoFast IR', 'connectivity' => 'Bluetooth', 'linked_module' => 'Paciente - Registro de sintomas', 'recorded_data' => 'Temperatura corporal', 'compatibility' => 'iOS / Android', 'status' => 'active'],
        ];

        foreach ($devices as $device) {
            MedicalDevice::query()->updateOrCreate(['code' => $device['code']], $device);
        }
    }
}
