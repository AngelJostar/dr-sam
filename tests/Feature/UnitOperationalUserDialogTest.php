<?php

namespace Tests\Feature;

use App\Models\MedicalUnit;
use App\Models\OperationalArea;
use App\Models\OperationalProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UnitOperationalUserDialogTest extends TestCase
{
    use RefreshDatabase;

    private function setupUnit(): array
    {
        $owner = User::query()->create(['name' => 'Responsable Unidad', 'username' => 'unit.dialog', 'role' => 'unit', 'status' => 'active']);
        $unit = MedicalUnit::query()->create(['name' => 'Unidad de prueba', 'unit_username' => $owner->username]);
        $area = OperationalArea::query()->create(['key' => 'nursing', 'label' => 'Enfermeria']);
        $this->actingAs($owner);

        return [$unit, $area];
    }

    private function userData(OperationalArea $area): array
    {
        return ['_operational_user_form' => '1', 'name' => 'Operador de prueba', 'username' => 'operator.dialog', 'password' => 'Test-only-123', 'role_label' => 'Operador', 'authority' => 'Direccion General', 'service' => 'Nutricion parenteral', 'operational_area_id' => $area->id, 'permissions' => ['history', 'detail']];
    }

    public function test_save_redirects_to_the_selected_unit_and_shows_the_success_dialog_and_table_actions(): void
    {
        [$unit, $area] = $this->setupUnit();
        $pageUrl = route('unit.dashboard', ['section' => 'users', 'unit' => $unit->id]);
        $this->post(route('unit.operational-users.store', ['unit' => $unit->id]), $this->userData($area))
            ->assertRedirect($pageUrl)->assertSessionHasNoErrors()->assertSessionHas('status', 'Guardado correctamente');
        $this->get($pageUrl)->assertOk()
            ->assertSee('data-unit-feedback-dialog', false)->assertSee('Guardado correctamente')
            ->assertSee('aria-label="Cerrar mensaje"', false)->assertSee('data-operational-users-table', false)
            ->assertSee('data-open-operational-user', false)->assertSee('data-operational-user-dialog', false)
            ->assertSee('Guardar y cerrar')->assertSee('Operador de prueba')->assertSee('operator.dialog')
            ->assertSee('>Editar</button>', false)->assertSee('>Eliminar</button>', false)
            ->assertDontSee('unit-native-user-list')->assertDontSee('data-unit-scroll-target="unit-operational-form"', false);

        $profile = OperationalProfile::query()->firstOrFail();
        $password = $profile->user->password;
        $this->patch(route('unit.operational-users.update', ['profile' => $profile, 'unit' => $unit->id]), array_merge($this->userData($area), ['name' => 'Nombre actualizado', 'password' => '']))
            ->assertRedirect($pageUrl)->assertSessionHasNoErrors()->assertSessionHas('status', 'Guardado correctamente');
        $this->assertSame('Nombre actualizado', $profile->user->fresh()->name);
        $this->assertSame($password, $profile->user->fresh()->password);
        $this->assertTrue(Hash::check('Test-only-123', $password));
    }

    public function test_validation_errors_restore_the_form_without_a_success_message_or_password(): void
    {
        [$unit, $area] = $this->setupUnit();
        $pageUrl = route('unit.dashboard', ['section' => 'users', 'unit' => $unit->id]);
        $data = array_merge($this->userData($area), ['username' => 'unit.dialog', 'permissions' => ['reports']]);
        $this->from($pageUrl)->post(route('unit.operational-users.store', ['unit' => $unit->id]), $data)
            ->assertRedirect($pageUrl)->assertSessionHasErrors('username')->assertSessionMissing('status');
        $this->assertDatabaseCount('operational_profiles', 0);
        $this->get($pageUrl)->assertOk()->assertSee('No se pudo guardar el usuario.')
            ->assertSee('Operador de prueba')->assertSee('unitUserDialogConfig', false)
            ->assertDontSee('Test-only-123')->assertDontSee('data-unit-feedback-dialog', false);
    }
}
