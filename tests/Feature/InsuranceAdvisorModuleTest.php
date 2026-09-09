<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Hospitalization;
use App\Models\InsurancePolicy;
use App\Models\MedicationDelivery;
use App\Models\Patient;
use App\Models\ProviderRequest;
use App\Models\Treatment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InsuranceAdvisorModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_insurance_advisor_can_open_native_dashboard(): void
    {
        $user = User::query()->create([
            'name' => 'Asesor Seguros',
            'username' => 'asesor.test',
            'email' => 'asesor@test.local',
            'role' => 'insurance_advisor',
            'module' => 'insurance_advisor',
            'status' => 'active',
        ]);

        $patient = Patient::query()->create([
            'full_name' => 'Paciente Asegurado',
            'status' => 'active',
            'risk_level' => 'high',
        ]);

        InsurancePolicy::query()->create([
            'patient_id' => $patient->id,
            'policy_number' => 'POL-ADV-001',
            'insurer_name' => 'Aseguradora Test',
            'plan_name' => 'GMM Test',
            'status' => 'active',
            'starts_at' => now()->subMonth(),
            'ends_at' => now()->addYear(),
        ]);

        InsurancePolicy::query()->create([
            'patient_id' => $patient->id,
            'policy_number' => 'POL-ADV-DUE',
            'insurer_name' => 'GNP',
            'plan_name' => 'Gastos medicos',
            'status' => 'active',
            'starts_at' => now()->subYear(),
            'ends_at' => now()->addDays(45),
        ]);

        InsurancePolicy::query()->create([
            'patient_id' => $patient->id,
            'policy_number' => 'POL-ADV-EXP',
            'insurer_name' => 'AXA',
            'plan_name' => 'GMM Nacional',
            'status' => 'expired',
            'starts_at' => now()->subYears(2),
            'ends_at' => now()->subDays(20),
        ]);

        Treatment::query()->create([
            'patient_id' => $patient->id,
            'medication_name' => 'Tratamiento Test',
            'status' => 'active',
        ]);

        MedicationDelivery::query()->create([
            'patient_id' => $patient->id,
            'scheduled_delivery_date' => now()->addDay(),
            'status' => 'pending',
        ]);

        $this->actingAs($user)
            ->get(route('insurance-advisor.dashboard'))
            ->assertOk()
            ->assertSee('Seguros GMM')
            ->assertSee('insurance-advisor-native-screen')
            ->assertSee('insurance-advisor-native-sidebar')
            ->assertSee('Cartera de polizas y siniestros')
            ->assertSee('POL-ADV-001')
            ->assertSee('Paciente Asegurado')
            ->assertSee('Subir pago')
            ->assertSee('name="search"', false)
            ->assertSee('name="status"', false)
            ->assertSee('name="insurer"', false)
            ->assertDontSee('<iframe');

        $this->actingAs($user)
            ->get(route('insurance-advisor.dashboard', ['search' => 'POL-ADV-DUE', 'status' => 'due']))
            ->assertOk()
            ->assertSee('POL-ADV-DUE')
            ->assertDontSee('POL-ADV-EXP');

        foreach ([
            'due' => 'POL-ADV-DUE',
            'expired' => 'POL-ADV-EXP',
            'messages' => 'Mensajes a asegurados',
            'alerts' => 'Alertas del asesor',
            'claims' => 'Listado de siniestros',
        ] as $section => $expectedText) {
            $this->actingAs($user)
                ->get(route('insurance-advisor.dashboard', ['section' => $section]))
                ->assertOk()
                ->assertSee($expectedText);
        }

        $this->actingAs($user)
            ->get(route('insurance-advisor.dashboard', ['section' => 'expired']))
            ->assertOk()
            ->assertSee('Vencidas')
            ->assertSee('POL-ADV-EXP')
            ->assertSee('data-open-renewal', false)
            ->assertSee(route('insurance-advisor.policies.renew', InsurancePolicy::query()->where('policy_number', 'POL-ADV-EXP')->firstOrFail()), false)
            ->assertSee('Subir pago y renovar')
            ->assertSee('Registrar pago y generar periodo');

        $this->actingAs($user)
            ->post(route('insurance-advisor.alerts.sync'))
            ->assertRedirect();
        $this->assertDatabaseCount('insurance_advisor_notifications', 4);

        $duePolicy = InsurancePolicy::query()->where('policy_number', 'POL-ADV-DUE')->firstOrFail();
        $this->actingAs($user)
            ->post(route('insurance-advisor.policies.messages.store', $duePolicy), [
                'subject' => 'Recordatorio personalizado',
                'body' => 'Favor de enviar su comprobante de renovacion.',
            ])
            ->assertRedirect(route('insurance-advisor.dashboard', ['section' => 'messages']));
        $this->assertDatabaseHas('insurance_advisor_notifications', [
            'insurance_policy_id' => $duePolicy->id,
            'type' => 'message',
            'subject' => 'Recordatorio personalizado',
        ]);

        $this->actingAs($user)
            ->post(route('insurance-advisor.alerts.sync'))
            ->assertRedirect();
        $this->assertDatabaseCount('insurance_advisor_notifications', 5);

        $this->actingAs($user)
            ->get(route('insurance-advisor.dashboard', ['section' => 'messages']))
            ->assertOk()
            ->assertSee('Renovación próxima')
            ->assertSee('Póliza vencida')
            ->assertSee('POL-ADV-DUE')
            ->assertSee('POL-ADV-EXP')
            ->assertSee('Plataforma Dr. Sam');

        $this->actingAs($user)
            ->get(route('insurance-advisor.dashboard', ['section' => 'alerts']))
            ->assertOk()
            ->assertSee('Alertas del asesor')
            ->assertSee('Póliza por vencer')
            ->assertSee('Póliza vencida')
            ->assertSee('POL-ADV-DUE')
            ->assertSee('POL-ADV-EXP')
            ->assertSee('Asesor');

        $this->actingAs($user)
            ->get(route('insurance-advisor.dashboard', ['section' => 'claims']))
            ->assertOk()
            ->assertSee('Listado de siniestros')
            ->assertSee('+ Nuevo siniestro')
            ->assertSee('data-new-claim-toggle', false)
            ->assertSee(route('insurance-advisor.claims.store'), false)
            ->assertSee('Crear siniestro')
            ->assertSee('Documentos pendientes')
            ->assertSee('Ver todos los siniestros');
    }

    public function test_insurance_advisor_can_create_claim_from_inline_form(): void
    {
        $user = User::query()->create([
            'name' => 'Asesor Seguros',
            'username' => 'asesor.claim',
            'email' => 'asesor.claim@test.local',
            'role' => 'insurance_advisor',
            'module' => 'insurance_advisor',
            'status' => 'active',
        ]);
        $patient = Patient::query()->create([
            'full_name' => 'Paciente Siniestro',
            'status' => 'active',
        ]);
        $policy = InsurancePolicy::query()->create([
            'patient_id' => $patient->id,
            'policy_number' => 'POL-CLAIM-001',
            'insurer_name' => 'GNP',
            'status' => 'active',
            'ends_at' => now()->addMonths(8),
        ]);

        $this->actingAs($user)
            ->post(route('insurance-advisor.claims.store'), [
                'policy_id' => $policy->id,
                'type' => 'reimbursement',
                'hospital' => 'Hospital de Prueba',
                'event_date' => now()->toDateString(),
                'estimated_amount' => 36200,
                'diagnosis' => 'Colecistitis aguda',
            ])
            ->assertRedirect(route('insurance-advisor.dashboard', ['section' => 'claims']));

        $claim = data_get($policy->fresh()->metadata, 'insurance_claims.0');
        $this->assertSame('Reembolso', $claim['type']);
        $this->assertSame('Hospital de Prueba', $claim['hospital']);
        $this->assertEquals(36200, $claim['estimated_amount']);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'insurance.claim.created',
            'auditable_id' => $policy->id,
        ]);

        $this->actingAs($user)
            ->get(route('insurance-advisor.dashboard', ['section' => 'claims']))
            ->assertOk()
            ->assertSee($claim['folio'])
            ->assertSee('Hospital de Prueba')
            ->assertSee('Colecistitis aguda');
    }

    public function test_insurance_advisor_can_update_policy_status(): void
    {
        $user = User::query()->create([
            'name' => 'Asesor Seguros',
            'username' => 'asesor.status',
            'email' => 'asesor.status@test.local',
            'role' => 'insurance_advisor',
            'module' => 'insurance_advisor',
            'status' => 'active',
        ]);

        $policy = InsurancePolicy::query()->create([
            'policy_number' => 'POL-ADV-002',
            'insurer_name' => 'Aseguradora Test',
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->patch(route('insurance-advisor.policies.status', $policy), [
                'status' => 'inactive',
                'notes' => 'Pendiente renovacion',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('insurance_policies', [
            'id' => $policy->id,
            'status' => 'inactive',
        ]);

        $this->assertSame(
            'Pendiente renovacion',
            $policy->fresh()->metadata['advisor_note'] ?? null,
        );

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'insurance.policy.updated',
            'auditable_id' => $policy->id,
        ]);
    }

    public function test_insurance_advisor_can_upload_payment_and_renew_policy(): void
    {
        Storage::fake();
        $user = User::query()->create([
            'name' => 'Asesor Seguros',
            'username' => 'asesor.renew',
            'email' => 'asesor.renew@test.local',
            'role' => 'insurance_advisor',
            'module' => 'insurance_advisor',
            'status' => 'active',
        ]);
        $policy = InsurancePolicy::query()->create([
            'policy_number' => 'POL-RENEW-001',
            'insurer_name' => 'GNP',
            'status' => 'expired',
            'starts_at' => now()->subYear(),
            'ends_at' => now()->subDay(),
        ]);

        $this->actingAs($user)
            ->post(route('insurance-advisor.policies.renew', $policy), [
                'payment_file' => UploadedFile::fake()->create('pago.pdf', 100, 'application/pdf'),
                'amount' => 31750,
                'paid_at' => now()->toDateString(),
                'notes' => 'Referencia bancaria demo',
            ])
            ->assertRedirect(route('insurance-advisor.dashboard', ['policy' => $policy->id]));

        $policy->refresh();
        $this->assertSame('active', $policy->status);
        $this->assertSame('Pagada', data_get($policy->metadata, 'payment_status'));
        $this->assertEquals(31750.0, data_get($policy->metadata, 'renewal_history.0.amount'));
        Storage::assertExists(data_get($policy->metadata, 'renewal_history.0.file_path'));
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'insurance.policy.renewed',
            'auditable_id' => $policy->id,
        ]);
    }

    public function test_insurance_advisor_can_export_and_request_policy_synchronization(): void
    {
        $user = User::query()->create([
            'name' => 'Asesor Sincronizacion',
            'username' => 'asesor.sync',
            'email' => 'asesor.sync@test.local',
            'role' => 'insurance_advisor',
            'module' => 'insurance_advisor',
            'status' => 'active',
        ]);
        $patient = Patient::query()->create([
            'full_name' => 'Paciente Sincronizado',
            'platform_number' => '100009999',
            'status' => 'active',
        ]);
        $policy = InsurancePolicy::query()->create([
            'patient_id' => $patient->id,
            'policy_number' => 'POL-SYNC-001',
            'insurer_name' => 'GNP',
            'plan_name' => 'GMM Nacional',
            'status' => 'active',
            'starts_at' => now()->subMonth(),
            'ends_at' => now()->addYear(),
            'metadata' => ['premium' => 27500],
        ]);
        InsurancePolicy::query()->create([
            'patient_id' => $patient->id,
            'policy_number' => 'POL-OTHER-001',
            'insurer_name' => 'AXA',
            'status' => 'active',
        ]);

        $export = $this->actingAs($user)->get(route('insurance-advisor.policies.export', [
            'search' => 'POL-SYNC-001',
            'insurer' => 'GNP',
        ]));

        $export
            ->assertOk()
            ->assertDownload('polizas-gmm-'.now()->format('Y-m-d').'.csv');
        $csv = $export->streamedContent();
        $this->assertStringContainsString('POL-SYNC-001', $csv);
        $this->assertStringContainsString('Paciente Sincronizado', $csv);
        $this->assertStringContainsString('100009999', $csv);
        $this->assertStringNotContainsString('POL-OTHER-001', $csv);

        $this->actingAs($user)
            ->post(route('insurance-advisor.policies.sync', $policy))
            ->assertRedirect();

        $policy->refresh();
        $this->assertSame('pending', data_get($policy->metadata, 'doctor_sync.status'));
        $this->assertSame(1, data_get($policy->metadata, 'doctor_sync.attempts'));
        $this->assertDatabaseHas('insurance_advisor_notifications', [
            'insurance_policy_id' => $policy->id,
            'type' => 'sync',
            'audience' => 'doctor',
        ]);

        $this->actingAs($user)
            ->post(route('insurance-advisor.policies.sync.bulk'), [
                'target' => 'pending',
                'search' => 'POL-SYNC-001',
            ])
            ->assertRedirect();

        $policy->refresh();
        $this->assertSame(2, data_get($policy->metadata, 'doctor_sync.attempts'));
        $this->assertCount(2, data_get($policy->metadata, 'doctor_sync.history'));
        $this->assertDatabaseCount('insurance_advisor_notifications', 2);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'insurance.policy.sync_requested',
            'auditable_id' => $policy->id,
        ]);
    }

    public function test_insurance_advisor_can_manage_claim_documents_discharge_and_quotations(): void
    {
        Storage::fake();
        $user = User::query()->create([
            'name' => 'Asesor Siniestros',
            'username' => 'asesor.claims.flow',
            'email' => 'asesor.claims.flow@test.local',
            'role' => 'insurance_advisor',
            'module' => 'insurance_advisor',
            'status' => 'active',
        ]);
        $patient = Patient::query()->create([
            'full_name' => 'Paciente Flujo Completo',
            'status' => 'active',
        ]);
        $policy = InsurancePolicy::query()->create([
            'patient_id' => $patient->id,
            'policy_number' => 'POL-CLAIM-FLOW-001',
            'insurer_name' => 'Seguros Atlas',
            'plan_name' => 'GMM Integral',
            'status' => 'active',
            'ends_at' => now()->addYear(),
        ]);

        $this->actingAs($user)
            ->post(route('insurance-advisor.claims.store'), [
                'policy_id' => $policy->id,
                'type' => 'hospital_discharge',
                'hospital' => 'Hospital Central',
                'event_date' => now()->toDateString(),
                'estimated_amount' => 98500,
                'diagnosis' => 'Atencion hospitalaria de prueba',
            ])
            ->assertRedirect(route('insurance-advisor.dashboard', ['section' => 'claims']));

        $claim = data_get($policy->fresh()->metadata, 'insurance_claims.0');
        $folio = $claim['folio'];
        $documentKey = $claim['documents'][0]['key'];
        $this->assertSame('hospital_discharge', $claim['type_key']);
        $this->assertSame('Alta hospitalaria', $claim['type']);
        $this->assertSame(6, $claim['pending_documents']);

        $this->actingAs($user)
            ->post(route('insurance-advisor.claims.documents.request', [$policy, $folio]))
            ->assertRedirect(route('insurance-advisor.dashboard', ['section' => 'claims', 'claim' => $folio]));
        $this->assertDatabaseHas('insurance_advisor_notifications', [
            'insurance_policy_id' => $policy->id,
            'type' => 'message',
            'subject' => 'Documentos pendientes para siniestro',
        ]);

        $this->actingAs($user)
            ->post(route('insurance-advisor.claims.documents.store', [$policy, $folio]), [
                'document_key' => $documentKey,
                'document_file' => UploadedFile::fake()->create('identificacion.pdf', 80, 'application/pdf'),
            ])
            ->assertRedirect(route('insurance-advisor.dashboard', ['section' => 'claims', 'claim' => $folio]));

        $document = Document::query()->firstOrFail();
        Storage::assertExists($document->file_path);
        $this->assertSame($folio, data_get($document->metadata, 'claim_folio'));
        $claim = data_get($policy->fresh()->metadata, 'insurance_claims.0');
        $this->assertSame(5, $claim['pending_documents']);
        $this->assertSame('received', $claim['documents'][0]['status']);

        $this->actingAs($user)
            ->post(route('insurance-advisor.claims.follow-up', [$policy, $folio]), [
                'note' => 'Aseguradora confirma recepcion parcial del expediente.',
            ])
            ->assertRedirect(route('insurance-advisor.dashboard', ['section' => 'claims', 'claim' => $folio]));

        $this->actingAs($user)
            ->post(route('insurance-advisor.claims.discharge', [$policy, $folio]))
            ->assertRedirect(route('insurance-advisor.dashboard', ['section' => 'claims', 'claim' => $folio]));

        $this->actingAs($user)
            ->post(route('insurance-advisor.claims.send-insurer', [$policy, $folio]))
            ->assertRedirect(route('insurance-advisor.dashboard', ['section' => 'claims', 'claim' => $folio]));

        $hospitalization = Hospitalization::query()->firstOrFail();
        $this->assertSame('in_review', $hospitalization->status);
        $this->assertSame($folio, data_get($hospitalization->metadata, 'insurance_claim_folio'));

        $this->actingAs($user)
            ->post(route('insurance-advisor.claims.quotations.store', [$policy, $folio]), [
                'service' => 'chemotherapy',
                'institution' => 'Institucion Demo',
                'unit' => 'Centro Oncologico Norte',
                'prescription_file' => UploadedFile::fake()->create('receta.pdf', 90, 'application/pdf'),
                'clinical_summary_file' => UploadedFile::fake()->create('resumen-clinico.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect(route('insurance-advisor.dashboard', ['section' => 'claims', 'claim' => $folio]));

        $providerRequest = ProviderRequest::query()->firstOrFail();
        $this->assertSame('chemotherapy', $providerRequest->request_type);
        $this->assertSame('requested', $providerRequest->status);
        $this->assertSame($folio, data_get($providerRequest->payload, 'claim_folio'));
        Storage::assertExists(data_get($providerRequest->payload, 'prescription_file_path'));
        Storage::assertExists(data_get($providerRequest->payload, 'clinical_summary_file_path'));

        $claim = data_get($policy->fresh()->metadata, 'insurance_claims.0');
        $this->assertSame('Enviado a aseguradora', $claim['status']);
        $this->assertSame($hospitalization->id, $claim['hospitalization_id']);
        $this->assertSame('Quimioterapia', $claim['quotations'][0]['service_label']);
        $this->assertSame('Solicitada', $claim['quotations'][0]['status']);
        $this->assertSame('Aseguradora confirma recepcion parcial del expediente.', collect($claim['notes'])->pluck('text')->first(
            fn (string $note): bool => str_contains($note, 'confirma recepcion parcial')
        ));

        $this->actingAs($user)
            ->get(route('insurance-advisor.dashboard', ['section' => 'claims']))
            ->assertOk()
            ->assertSee($folio)
            ->assertSee('identificacion.pdf')
            ->assertSee('Quimioterapia')
            ->assertSee('Centro Oncologico Norte')
            ->assertSee('Aseguradora confirma recepcion parcial del expediente.');

        foreach ([
            'insurance.claim.document.uploaded',
            'insurance.claim.documents.requested',
            'insurance.claim.discharge.started',
            'insurance.claim.follow_up.created',
            'insurance.claim.sent_to_insurer',
            'insurance.claim.quotation.requested',
        ] as $event) {
            $this->assertDatabaseHas('audit_logs', ['event' => $event]);
        }
    }
}
