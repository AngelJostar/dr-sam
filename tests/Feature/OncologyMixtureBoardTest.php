<?php

namespace Tests\Feature;

use App\Models\MedicalUnit;
use App\Models\OperationalArea;
use App\Models\OperationalProfile;
use App\Models\ProviderRequest;
use App\Models\User;
use App\Support\OncologyMixtureBoard;
use Database\Seeders\OncologyMixtureBoardDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OncologyMixtureBoardTest extends TestCase
{
    use RefreshDatabase;

    private function operator(): array
    {
        $user = User::query()->create(['name' => 'Operador', 'username' => 'mixtures.operator', 'role' => 'operational', 'status' => 'active']);
        $unit = MedicalUnit::query()->create(['name' => 'Hospital Demo', 'external_id' => 'demo-hospital-general-dr-sam']);
        $area = OperationalArea::query()->create(['key' => 'oncology', 'label' => 'Centro Oncológico', 'role_label' => 'Operador']);
        OperationalProfile::query()->create(['user_id' => $user->id, 'medical_unit_id' => $unit->id, 'operational_area_id' => $area->id, 'role_label' => 'Operador', 'status' => 'active']);
        return [$user, $unit];
    }

    private function mixture(MedicalUnit $unit, string $folio, string $status = 'requested', string $type = 'chemo', bool $scheduled = true): ProviderRequest
    {
        return ProviderRequest::query()->create(['medical_unit_id' => $unit->id, 'external_id' => $folio, 'request_type' => $type, 'status' => $status, 'requested_at' => now(), 'required_at' => $scheduled ? now()->addDay() : null]);
    }

    public function test_all_three_sections_render_scoped_oncology_status_tables(): void
    {
        [$user, $unit] = $this->operator();
        $scheduled = $this->mixture($unit, 'ONC-SCHEDULED', 'ready');
        $this->mixture($unit, 'ONC-UNSCHEDULED', 'requested', 'chemotherapy', false);
        $this->mixture($unit, 'ONC-CLOSED', 'delivered');
        $this->mixture($unit, 'PRIVATE-NUTRITION', 'requested', 'npt');
        $other = MedicalUnit::query()->create(['name' => 'Otro hospital']);
        $this->mixture($other, 'PRIVATE-FOREIGN');
        $scheduled->interactions()->create(['type' => 'message', 'body' => 'Mensaje interno']);
        $scheduled->interactions()->create(['type' => 'adjustment', 'body' => 'Revisión', 'status' => 'open']);
        $this->actingAs($user);
        foreach (['calendar', 'mixes', 'mix-history'] as $section) {
            $response = $this->get(route('operational.dashboard', ['area' => 'oncology', 'section' => $section, 'oncology_track' => 'mixes', 'unit' => $unit->id]))
                ->assertOk()->assertSee('data-oncology-mixtures-board', false)->assertSee('Estado de proceso')
                ->assertSee('Mensajería')->assertSee('En ajuste')->assertSee('Aprobación')
                ->assertDontSee('data-operational-service-calendar=', false)->assertDontSee('PRIVATE-NUTRITION')->assertDontSee('PRIVATE-FOREIGN');
            if ($section === 'mix-history') {
                $response->assertSee('ONC-CLOSED')->assertDontSee('ONC-SCHEDULED')->assertDontSee('ONC-UNSCHEDULED');
            } else {
                $response->assertSee('ONC-SCHEDULED')->assertSee('data-message-count="1"', false)->assertSee('data-adjustment-count="1"', false);
                $section === 'mixes' ? $response->assertDontSee('ONC-UNSCHEDULED')->assertDontSee('ONC-CLOSED') : $response->assertSee('ONC-UNSCHEDULED')->assertSee('ONC-CLOSED');
            }
        }
        $this->get(route('operational.dashboard', ['area' => 'oncology', 'section' => 'calendar', 'oncology_track' => 'infusions', 'unit' => $unit->id]))
            ->assertOk()->assertSee('data-operational-service-calendar', false)->assertDontSee('data-oncology-mixtures-board', false);
        $this->get(route('operational.dashboard', ['area' => 'oncology', 'section' => 'mixes', 'unit' => $other->id]))->assertForbidden();
    }

    public function test_operational_interactions_are_internal_and_limited_to_own_oncology_requests(): void
    {
        [$user, $unit] = $this->operator();
        $record = $this->mixture($unit, 'ONC-CHAT');
        $other = $this->mixture(MedicalUnit::query()->create(['name' => 'Ajeno']), 'ONC-OTHER');
        $nutrition = $this->mixture($unit, 'NPT-OTHER', 'requested', 'npt');
        $this->actingAs($user);
        $url = route('operational.mixtures.interactions.store', $record);
        $this->postJson($url, ['type' => 'message', 'body' => 'Confirmar entrega'])->assertCreated()->assertJsonPath('message_count', 1);
        $response = $this->postJson($url, ['type' => 'adjustment', 'body' => 'Revisar horario'])->assertCreated()->assertJsonPath('open_adjustment_count', 1);
        $resolve = $response->json('interactions.1.resolve_url');
        $this->assertStringContainsString('/operational/mixtures/', $resolve);
        $this->patchJson($resolve, ['resolution' => 'Horario confirmado'])->assertOk()->assertJsonPath('open_adjustment_count', 0);
        $this->assertSame('requested', $record->fresh()->status);
        $this->getJson(route('operational.mixtures.interactions.index', $other))->assertForbidden();
        $this->postJson(route('operational.mixtures.interactions.store', $other), ['type' => 'message', 'body' => 'Fuera de ámbito'])->assertForbidden();
        $this->getJson(route('operational.mixtures.interactions.index', $nutrition))->assertNotFound();
        $this->getJson(route('unit.requests.interactions.index', $record))->assertForbidden();
        OperationalProfile::query()->where('user_id', $user->id)->update(['status' => 'inactive']);
        $this->getJson(route('operational.mixtures.interactions.index', $record))->assertForbidden();
    }

    public function test_mixture_status_navigation_has_one_upper_row_and_infusion_calendar_only_home(): void
    {
        [$user, $unit] = $this->operator();
        $this->actingAs($user);
        $base = ['area' => 'oncology', 'section' => 'calendar', 'unit' => $unit->id];
        $html = $this->get(route('operational.dashboard', $base + ['oncology_track' => 'mixes', 'mix_filter' => 'messages']))->assertOk()->assertSee('data-mixtures-filter="messages"', false)->assertDontSee('data-mixtures-status=', false)->getContent();
        preg_match('/<nav class="operational-section-tabs".*?<\/nav>/s', $html, $nav);
        foreach (['Inicio', 'Programadas', 'Pendientes', 'Mensajería', 'En ajuste', 'En preparación', 'En ruta', 'Entregadas', 'Historial'] as $label) {
            $this->assertSame(1, substr_count($nav[0], $label));
        }
        $html = $this->get(route('operational.dashboard', $base + ['oncology_track' => 'infusions']))->assertOk()->assertSee('data-operational-service-calendar', false)->getContent();
        preg_match('/<nav class="operational-section-tabs".*?<\/nav>/s', $html, $nav);
        $this->assertSame(1, substr_count($nav[0], '<a '));
        $this->assertStringContainsString('Inicio', $nav[0]);
    }

    public function test_demo_is_additive_idempotent_and_preserves_progress(): void
    {
        [, $unit] = $this->operator();
        $original = $this->mixture($unit, 'EXISTING', 'requested', 'npt');
        $this->seed(OncologyMixtureBoardDemoSeeder::class);
        $demo = ProviderRequest::query()->where('external_id', 'DEMO-CM-ONC-01')->firstOrFail();
        $demo->update(['status' => 'in_route']);
        $this->seed(OncologyMixtureBoardDemoSeeder::class);
        $rows = ProviderRequest::query()->where('payload->source', 'oncology_mixture_board_demo')->get();
        $this->assertCount(8, $rows);
        $this->assertSame(['chemo'], $rows->pluck('request_type')->unique()->values()->all());
        $this->assertSame('in_route', $demo->fresh()->status);
        $this->assertSame('requested', $original->fresh()->status);
        $this->assertDatabaseCount('provider_request_interactions', 3);
        $this->assertSame(8, $rows->pluck('patient_id')->unique()->count());
    }

    public function test_process_and_approval_are_independent(): void
    {
        $record = new ProviderRequest(['status' => 'ready', 'payload' => []]);
        $this->assertSame('preparing', OncologyMixtureBoard::category($record));
        $this->assertSame('Inspeccionada', OncologyMixtureBoard::statusLabel($record));
        $this->assertSame(['Pendiente', 'pending'], OncologyMixtureBoard::approval($record));
        $record->payload = ['authorizations' => ['oncology' => 'approved', 'pharmacy' => 'approved']];
        $this->assertSame(['Aprobada', 'delivered'], OncologyMixtureBoard::approval($record));
    }
}
