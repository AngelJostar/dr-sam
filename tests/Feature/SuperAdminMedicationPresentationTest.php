<?php

namespace Tests\Feature;

use App\Models\MedicationCatalogItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminMedicationPresentationTest extends TestCase
{
    use RefreshDatabase;

    public function test_medication_catalog_displays_and_exports_distinctive_names_with_their_correct_columns(): void
    {
        $user = User::query()->create([
            'name' => 'Super Admin', 'username' => 'superadmin.medications', 'email' => 'medications@test.local',
            'role' => 'superadmin', 'module' => 'superadmin', 'status' => 'active',
        ]);
        MedicationCatalogItem::query()->create([
            'name' => 'Medicamento de prueba', 'cnis' => '010.000.0001.00',
            'external_id' => 'med-prueba-1', 'description' => 'Descripción de prueba', 'status' => 'active',
            'metadata' => ['distinctive_name' => 'Marca de prueba', 'mobile_units' => 'No', 'basic_units' => 'Si', 'cessa' => 'Si', 'catalog' => 'Institución de prueba'],
        ]);
        MedicationCatalogItem::query()->create([
            'name' => 'Medicamento sin marca', 'cnis' => '010.000.0002.00', 'status' => 'inactive',
        ]);

        $this->actingAs($user)->get(route('superadmin.catalog', ['section' => 'medications']))
            ->assertOk()->assertSee('Denominación distintiva')->assertSee('Marca de prueba')->assertSee('Por registrar')
            ->assertSee('Exportar CSV')->assertSee('superadmin-medication-code');

        $csv = $this->get(route('superadmin.catalog.csv', ['section' => 'medications', 'status' => 'active']))
            ->assertOk()->streamedContent();
        $lines = array_values(array_filter(explode("\n", trim($csv))));
        $columns = str_getcsv($lines[0]);
        $values = str_getcsv($lines[1]);
        $this->assertCount(10, $columns);
        $this->assertCount(10, $values);
        $row = array_combine($columns, $values);
        $this->assertSame('Marca de prueba', $row['Denominación distintiva']);
        $this->assertSame('No', $row['Unidades móviles']);
        $this->assertSame('Si', $row['Núcleos básicos']);
        $this->assertSame('Si', $row['CESSA']);
        $this->assertSame('Institución de prueba', $row['Catálogo institucional de']);
        $this->assertSame('active', $row['Estatus']);
        $this->assertStringNotContainsString('Medicamento sin marca', $csv);
    }
}
