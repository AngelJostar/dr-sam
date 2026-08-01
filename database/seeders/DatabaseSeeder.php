<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(DrSamDemoSeeder::class);
        $this->call(InsuranceHealthSeeder::class);
    }
}
