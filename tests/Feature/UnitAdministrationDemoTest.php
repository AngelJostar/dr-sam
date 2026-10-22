<?php

namespace Tests\Feature;

use App\Models\MedicalUnit;
use App\Models\User;
use App\Support\UnitAdministrationDemo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class UnitAdministrationDemoTest extends TestCase
{
    use RefreshDatabase;

    private function unit(): MedicalUnit
    {
        $user = User::query()->create(['name' => 'Unidad demo', 'username' => 'admin.demo.unit', 'role' => 'unit', 'status' => 'active']);
        $this->actingAs($user);
        return MedicalUnit::query()->create(['name' => 'Unidad Demo', 'unit_username' => $user->username, 'metadata' => ['profile' => ['public_name' => 'Conservado']]]);
    }

    private function save(MedicalUnit $unit, string $action, array $data)
    {
        return $this->postJson(route('unit.administration-demo.store', ['unit' => $unit->id]), $data + ['action' => $action, 'version' => UnitAdministrationDemo::state($unit->fresh())['version'], 'operation_id' => (string) Str::uuid()]);
    }

    public function test_demo_covers_all_tabs_and_payments_update_balances_without_touching_other_metadata(): void
    {
        $unit = $this->unit();
        foreach (['accounts', 'billing', 'insurers', 'authorizations'] as $tab) {
            $this->get(route('unit.dashboard', ['unit' => $unit->id, 'section' => 'administration', 'tab' => $tab]))->assertOk()->assertSee('Datos de demostración')->assertSee('data-administration-workspace', false);
        }
        $operation = (string) Str::uuid();
        $data = ['account_id' => 'CTA-084', 'amount' => '100.25', 'date' => now()->toDateString(), 'method' => 'Efectivo', 'reference' => 'DEMO', 'operation_id' => $operation];
        $response = $this->save($unit, 'payment', $data)->assertOk()->assertJsonPath('state.version', 2)->assertJsonPath('message', 'Guardado correctamente · Demo');
        $this->assertSame(249975, UnitAdministrationDemo::balances($response->json('state'), 'CTA-084')['balance']);
        $this->save($unit, 'payment', $data + ['version' => 1])->assertOk()->assertJsonPath('state.version', 2);
        $this->assertDatabaseCount('audit_logs', 1);
        $this->assertSame('Conservado', $unit->fresh()->metadata['profile']['public_name']);
        $this->save($unit, 'payment', array_merge($data, ['amount' => '5000', 'operation_id' => (string) Str::uuid()]))->assertUnprocessable()->assertJsonValidationErrors('amount');
        $this->save($unit, 'charge', ['account_id' => 'CTA-084', 'amount' => '20', 'description' => 'Cargo demo', 'date' => now()->toDateString(), 'version' => 1])->assertStatus(409);
        $this->save($unit, 'charge', ['account_id' => 'CTA-084', 'amount' => '20.005', 'description' => 'Cargo demo', 'date' => now()->toDateString()])->assertUnprocessable();
        $this->assertSame(2, UnitAdministrationDemo::state($unit->fresh())['version']);
    }

    public function test_accounts_invoices_insurers_and_authorization_responses_are_linked(): void
    {
        $unit = $this->unit();
        $this->save($unit, 'insurer', ['name' => 'Convenio Demo', 'agreement' => 'CONV-DEMO', 'contact' => 'test@example.test', 'scope' => 'Hospitalización', 'valid_until' => now()->addYear()->toDateString(), 'status' => 'active'])->assertOk();
        $this->save($unit, 'account', ['patient' => 'Paciente Demo', 'episode' => 'DEMO-01', 'coverage' => 'insurer', 'insurer_id' => 'INS-004', 'amount' => '1000', 'description' => 'Cargo de ingreso'])->assertOk();
        $this->save($unit, 'authorization', ['account_id' => 'CTA-087', 'insurer_id' => 'INS-001', 'service' => 'Atención', 'amount' => '1000', 'responsible' => 'Admisión'])->assertUnprocessable();
        $this->save($unit, 'authorization', ['account_id' => 'CTA-087', 'insurer_id' => 'INS-004', 'service' => 'Atención', 'amount' => '1000', 'responsible' => 'Admisión'])->assertOk();
        $this->save($unit, 'response', ['id' => 'AUT-014', 'status' => 'authorized', 'amount' => '1001', 'notes' => 'Respuesta demo'])->assertUnprocessable();
        $this->save($unit, 'response', ['id' => 'AUT-014', 'status' => 'authorized', 'amount' => '900', 'notes' => 'Respuesta demo'])->assertOk();
        $this->save($unit, 'followup', ['id' => 'AUT-014', 'notes' => 'Confirmación registrada'])->assertOk();
        $this->save($unit, 'invoice', ['account_id' => 'CTA-087', 'receiver' => 'Receptor Demo', 'status' => 'draft'])->assertOk();
        $this->save($unit, 'invoice', ['id' => 'BOR-030', 'account_id' => 'CTA-087', 'receiver' => 'Receptor Demo', 'status' => 'issued', 'tax_id' => 'XAXX010101000', 'postal_code' => '01000', 'use' => 'G03'])->assertOk();
        $this->get(route('unit.administration-demo.invoice', ['invoice' => 'BOR-030', 'format' => 'xml', 'unit' => $unit->id]))->assertOk()->assertSee('DocumentoDemo')->assertSee('1000.00');
        $this->get(route('unit.administration-demo.invoice', ['invoice' => 'BOR-030', 'format' => 'print', 'unit' => $unit->id]))->assertOk()->assertSee('Imprimir / Guardar PDF')->assertSee('Sin validez fiscal');
        $this->assertDatabaseCount('invoices', 0);
        $this->assertDatabaseCount('patients', 0);
        $state = UnitAdministrationDemo::state($unit->fresh());
        $authorization = collect($state['authorizations'])->firstWhere('id', 'AUT-014');
        $this->assertSame(90000, $authorization['authorized']);
        $this->assertCount(3, $authorization['history']);
    }

    public function test_private_documents_and_demo_changes_are_scoped_to_the_unit(): void
    {
        Storage::fake('local');
        $unit = $this->unit();
        $this->save($unit, 'document', ['kind' => 'authorizations', 'id' => 'AUT-011', 'name' => 'Documento demo', 'file' => UploadedFile::fake()->create('documento.pdf', 5, 'application/pdf')])->assertOk()->assertJsonMissingPath('state.documents.0.path');
        $this->get(route('unit.administration-demo.document', ['document' => 'DOC-001', 'unit' => $unit->id]))->assertOk();
        $other = MedicalUnit::query()->create(['name' => 'Otra unidad']);
        $this->get(route('unit.administration-demo.show', ['unit' => $other->id]))->assertForbidden();
        $this->get(route('unit.administration-demo.document', ['document' => 'DOC-001', 'unit' => $other->id]))->assertForbidden();
        $this->postJson(route('unit.administration-demo.store', ['unit' => $other->id]), ['action' => 'payment'])->assertForbidden();
        $operator = User::query()->create(['name' => 'Operador', 'username' => 'demo.operator', 'role' => 'operational', 'status' => 'active']);
        $this->actingAs($operator)->get(route('unit.administration-demo.show', ['unit' => $unit->id]))->assertForbidden();
    }
}
