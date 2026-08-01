<?php

namespace Database\Seeders;

use App\Models\InsuranceCarrier;
use Illuminate\Database\Seeder;

class InsuranceCarrierSeeder extends Seeder
{
    public function run(): void
    {
        $carriers = [
            ['name' => 'AXA Seguros', 'slug' => 'aseg-axa', 'scope' => 'Gastos medicos mayores y atencion hospitalaria'],
            ['name' => 'Bupa Mexico', 'slug' => 'aseg-bupa', 'scope' => 'Cobertura medica privada nacional e internacional'],
            ['name' => 'GNP Seguros', 'slug' => 'aseg-gnp', 'scope' => 'Gastos medicos mayores y red hospitalaria privada'],
            ['name' => 'MetLife Mexico', 'slug' => 'aseg-metlife', 'scope' => 'Gastos medicos mayores y servicios de salud'],
            ['name' => 'Seguros Monterrey New York Life', 'slug' => 'aseg-monterrey', 'scope' => 'Gastos medicos mayores y red de prestadores'],
        ];

        foreach ($carriers as $carrier) {
            InsuranceCarrier::query()->updateOrCreate(
                ['slug' => $carrier['slug']],
                array_merge($carrier, [
                    'type' => 'Aseguradora privada',
                    'contact' => 'Convenios medicos',
                    'status' => 'active',
                ]),
            );
        }
    }
}
