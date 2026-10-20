<?php

namespace Tests\Feature;

use App\Models\ContractedService;
use App\Models\Institution;
use App\Models\MedicalUnit;
use App\Models\ProviderRequest;
use App\Models\ProviderRequestInteraction;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnitRequestInteractionsTest extends TestCase
{
    use RefreshDatabase;

    private function unitUser(): array
    {
        $user = User::query()->create(['name' => 'Unidad de prueba', 'username' => 'interaction.unit', 'role' => 'unit', 'status' => 'active']);
        $unit = MedicalUnit::query()->create(['name' => 'Hospital de prueba', 'unit_username' => $user->username]);

        return [$user, $unit];
    }

    private function requestFor(MedicalUnit $unit, string $type = 'npt'): ProviderRequest
    {
        return ProviderRequest::query()->create([
            'medical_unit_id' => $unit->id, 'request_type' => $type, 'status' => 'requested',
            'payload' => ['authorizations' => ['nursing' => 'pending'], 'clinical_format' => ['volume' => 1200]],
        ]);
    }

    public function test_nutrition_and_chemotherapy_have_message_and_adjustment_columns(): void
    {
        [$user, $unit] = $this->unitUser();
        foreach (['npt' => 'Nutricion parenteral', 'chemo' => 'Quimioterapias'] as $type => $name) {
            $service = Service::query()->create(['name' => $name]);
            ContractedService::query()->create(['medical_unit_id' => $unit->id, 'service_id' => $service->id]);
            $record = $this->requestFor($unit, $type);
            $record->interactions()->create(['type' => 'message', 'body' => 'Mensaje interno', 'user_id' => $user->id]);
            $record->interactions()->create(['type' => 'adjustment', 'body' => 'Revisar entrega', 'status' => 'open', 'user_id' => $user->id]);
        }
        $response = $this->actingAs($user)->get(route('unit.dashboard', ['section' => 'services']))->assertOk();
        $document = new \DOMDocument;
        @$document->loadHTML('<?xml encoding="utf-8" ?>'.$response->getContent());
        $xpath = new \DOMXPath($document);
        foreach (['nutrition', 'chemotherapy'] as $board) {
            $table = $xpath->query('//*[@data-request-service="'.$board.'"]//table')->item(0);
            $headers = $xpath->query('.//thead/tr/th', $table);
            $this->assertCount(13, $headers);
            $this->assertSame(['Ver', 'Mensajes', 'Aprobación', 'Ajustes'], array_slice(array_map(fn ($node) => trim($node->textContent), iterator_to_array($headers)), -4));
            $row = $xpath->query('.//tr[@data-interaction-request]', $table)->item(0);
            $this->assertSame('1', $row->getAttribute('data-message-count'));
            $this->assertSame('1', $row->getAttribute('data-adjustment-count'));
            $this->assertStringContainsString('En ajuste (1)', $row->textContent);
        }
    }

    public function test_messages_persist_per_request_with_the_authenticated_author(): void
    {
        [$user, $unit] = $this->unitUser();
        $record = $this->requestFor($unit);
        $other = $this->requestFor($unit, 'chemo');
        $this->actingAs($user)->postJson(route('unit.requests.interactions.store', $record), [
            'type' => 'message', 'body' => 'Confirmar recepción.', 'user_id' => 999,
        ])->assertCreated()->assertJsonPath('message_count', 1)
            ->assertJsonPath('interactions.0.author', $user->name)
            ->assertJsonPath('interactions.0.body', 'Confirmar recepción.');
        $this->assertDatabaseHas('provider_request_interactions', ['provider_request_id' => $record->id, 'user_id' => $user->id, 'type' => 'message']);
        $this->getJson(route('unit.requests.interactions.index', $record))->assertOk()->assertJsonPath('message_count', 1);
        $this->getJson(route('unit.requests.interactions.index', $other))->assertOk()->assertJsonCount(0, 'interactions');
        $this->assertDatabaseHas('audit_logs', ['event' => 'unit.request.message.created']);
    }

    public function test_adjustment_can_be_requested_and_resolved_without_changing_clinical_data(): void
    {
        [$user, $unit] = $this->unitUser();
        foreach (['npt', 'nutrition', 'chemo', 'chemotherapy'] as $type) {
            $record = $this->requestFor($unit, $type);
            $original = $record->payload;
            $response = $this->actingAs($user)->postJson(route('unit.requests.interactions.store', $record), ['type' => 'adjustment', 'body' => 'Confirmar horario de entrega.'])
                ->assertCreated()->assertJsonPath('open_adjustment_count', 1)->assertJsonPath('interactions.0.status', 'open');
            $interaction = ProviderRequestInteraction::findOrFail($response->json('interactions.0.id'));
            $resolveUrl = route('unit.requests.interactions.resolve', [$record, $interaction]);
            $this->patchJson($resolveUrl, ['resolution' => 'Horario confirmado con el área.'])->assertOk()
                ->assertJsonPath('open_adjustment_count', 0)
                ->assertJsonPath('interactions.0.status', 'resolved')
                ->assertJsonPath('interactions.0.resolved_by', $user->name);
            $this->assertSame($original, $record->fresh()->payload);
            $this->assertSame('requested', $record->fresh()->status);
            $this->patchJson($resolveUrl, ['resolution' => 'Sobrescribir'])->assertUnprocessable();
            $this->assertSame('Horario confirmado con el área.', $interaction->fresh()->resolution);
        }
        $this->assertDatabaseHas('audit_logs', ['event' => 'unit.request.adjustment.resolved']);
    }

    public function test_requests_from_another_unit_and_cross_request_adjustments_are_protected(): void
    {
        [$user, $unit] = $this->unitUser();
        $foreignUnit = MedicalUnit::query()->create(['name' => 'Otro hospital', 'unit_username' => 'another.unit']);
        $foreign = $this->requestFor($foreignUnit);
        $record = $this->requestFor($unit);
        $adjustment = $foreign->interactions()->create(['type' => 'adjustment', 'body' => 'Privado', 'status' => 'open']);
        $this->actingAs($user);
        $this->getJson(route('unit.requests.interactions.index', $foreign))->assertForbidden();
        $this->postJson(route('unit.requests.interactions.store', $foreign), ['type' => 'message', 'body' => 'Intento', 'unit' => $unit->id])->assertForbidden();
        $this->patchJson(route('unit.requests.interactions.resolve', [$foreign, $adjustment]), ['resolution' => 'Intento'])->assertForbidden();
        $this->patchJson(route('unit.requests.interactions.resolve', [$record, $adjustment]), ['resolution' => 'Intento'])->assertNotFound();
        $this->assertSame('open', $adjustment->fresh()->status);
    }

    public function test_institution_can_only_access_its_own_units_and_patient_access_is_denied(): void
    {
        $owner = User::query()->create(['name' => 'Institución', 'username' => 'interaction.institution', 'role' => 'institution', 'status' => 'active']);
        $institution = Institution::query()->create(['name' => 'Institución', 'owner_user_id' => $owner->id]);
        $unit = MedicalUnit::query()->create(['name' => 'Propio', 'institution_id' => $institution->id]);
        $record = $this->requestFor($unit, 'chemo');
        $other = $this->requestFor(MedicalUnit::query()->create(['name' => 'Ajeno']));
        $this->actingAs($owner)->getJson(route('unit.requests.interactions.index', $record))->assertOk();
        $this->getJson(route('unit.requests.interactions.index', $other))->assertForbidden();
        $patient = User::query()->create(['name' => 'Paciente', 'username' => 'interaction.patient', 'role' => 'patient', 'status' => 'active']);
        $this->actingAs($patient)->getJson(route('unit.requests.interactions.index', $record))->assertForbidden();
        $this->postJson(route('unit.requests.interactions.store', $record), ['type' => 'message', 'body' => 'Intento'])->assertForbidden();
    }

    public function test_closed_requests_preserve_history_and_reject_new_adjustments(): void
    {
        [$user, $unit] = $this->unitUser();
        $this->actingAs($user);
        foreach (['delivered', 'completed', 'cancelled', 'rejected'] as $status) {
            $record = $this->requestFor($unit);
            $record->update(['status' => $status]);
            $this->getJson(route('unit.requests.interactions.index', $record))->assertOk()->assertJsonPath('can_request_adjustment', false);
            $this->postJson(route('unit.requests.interactions.store', $record), ['type' => 'adjustment', 'body' => 'Fuera de plazo'])->assertUnprocessable();
            $this->postJson(route('unit.requests.interactions.store', $record), ['type' => 'message', 'body' => 'Nota de seguimiento'])->assertCreated();
            $this->assertSame(0, $record->interactions()->where('type', 'adjustment')->count());
        }
    }

    public function test_validation_rejects_empty_or_oversized_notes_and_unknown_types(): void
    {
        [$user, $unit] = $this->unitUser();
        $record = $this->requestFor($unit);
        $this->actingAs($user);
        foreach ([
            ['type' => 'unknown', 'body' => 'Prueba'],
            ['type' => 'message', 'body' => '   '],
            ['type' => 'adjustment', 'body' => str_repeat('x', 3001)],
        ] as $payload) {
            $this->postJson(route('unit.requests.interactions.store', $record), $payload)->assertUnprocessable();
        }
        $adjustment = $record->interactions()->create(['type' => 'adjustment', 'body' => 'Prueba', 'status' => 'open']);
        $this->patchJson(route('unit.requests.interactions.resolve', [$record, $adjustment]), ['resolution' => ''])->assertUnprocessable();
        $unrelated = $this->requestFor($unit, 'lab');
        $this->getJson(route('unit.requests.interactions.index', $unrelated))->assertNotFound();
    }
}
