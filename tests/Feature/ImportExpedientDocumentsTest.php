<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Patient;
use App\Models\Provider;
use App\Models\ProviderRequest;
use App\Models\User;
use App\Services\Platform\ImportExpedientDocuments;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImportExpedientDocumentsTest extends TestCase
{
    use RefreshDatabase;

    private function account(string $key): array
    {
        $user = User::create(['name' => $key, 'username' => $key, 'role' => 'provider', 'status' => 'active']);
        $provider = Provider::create(['user_id' => $user->id, 'name' => $key, 'provider_type' => 'import', 'status' => 'active']);
        return [$user, $provider];
    }

    private function record(Provider $provider, string $folio, ?Patient $patient = null): ProviderRequest
    {
        return ProviderRequest::create(['provider_id' => $provider->id, 'patient_id' => $patient?->id,
            'external_id' => $folio, 'request_type' => 'import', 'status' => 'documentation', 'payload' => []]);
    }

    public function test_all_document_groups_can_be_saved_viewed_and_downloaded_from_their_expedient(): void
    {
        Storage::fake('local');
        [$user, $provider] = $this->account('documents-owner');
        $record = $this->record($provider, 'DOC-001');
        foreach (['patient' => 'ine', 'regulatory' => 'power', 'logistics' => 'entrustment'] as $group => $key) {
            $returnSection = $group === 'regulatory' ? 1 : 6;
            $this->actingAs($user)->post(route('provider.import.documents.store'), [
                'folio' => $record->external_id, 'document_group' => $group,
                'return_section' => $returnSection,
                'files' => [$key => UploadedFile::fake()->create($group.'.pdf', 25, 'application/pdf')],
            ])->assertRedirect(route('provider.import.dashboard', ['section' => $returnSection, 'action' => 0]))->assertSessionHasNoErrors();
            $document = Document::where('metadata->document_group', $group)->sole();
            $this->assertEquals($record->id, data_get($document->metadata, 'provider_request_id'));
            $this->assertSame($key, data_get($document->metadata, 'document_key'));
            Storage::disk('local')->assertExists($document->file_path);
            $this->get(route('provider.import.documents.view', $document))->assertOk();
            $this->get(route('provider.import.documents.view', [$document, 'download' => 1]))->assertOk()
                ->assertHeader('content-disposition', 'attachment; filename='.$group.'.pdf');
        }
        $groups = app(ImportExpedientDocuments::class)->listing($record, $record->external_id, $user);
        foreach ($groups as $group => $documents) {
            $this->assertCount(1, collect($documents)->whereNotNull('url'));
            $this->assertSame($group.'.pdf', $documents[0]['filename']);
        }
        foreach ([1, 6] as $section) {
            $this->get(route('provider.import.dashboard', ['section' => $section, 'action' => 0]))->assertOk()
                ->assertSee('Ver 1 de 5')->assertSee('Ver 1 de 13')->assertSee('Ver 1 de 2');
        }
    }

    public function test_complete_regulatory_checklist_can_be_uploaded_together_and_shared_by_both_menus(): void
    {
        Storage::fake('local');
        [$user, $provider] = $this->account('regulatory-checklist-owner');
        $record = $this->record($provider, 'REG-011');
        $types = ImportExpedientDocuments::uploadTypes('regulatory');
        $this->assertCount(13, $types);
        $files = collect($types)->map(fn ($name, $key) => UploadedFile::fake()->create($key.'.pdf', 5, 'application/pdf'))->all();

        $this->actingAs($user)->post(route('provider.import.documents.store'), [
            'folio' => $record->external_id, 'document_group' => 'regulatory',
            'return_section' => 1, 'files' => $files,
        ])->assertRedirect(route('provider.import.dashboard', ['section' => 1, 'action' => 0]))->assertSessionHasNoErrors();

        $this->assertDatabaseCount('documents', 13);
        $groups = app(ImportExpedientDocuments::class)->listing($record, $record->external_id, $user);
        $this->assertCount(13, collect($groups['regulatory'])->whereNotNull('url'));
        $this->assertEmpty(collect($groups['patient'])->whereNotNull('url'));
        $this->assertEmpty(collect($groups['logistics'])->whereNotNull('url'));
        foreach ($groups['regulatory'] as $document) {
            $this->assertSame($document['key'].'.pdf', $document['filename']);
            $this->get($document['url'])->assertOk();
        }
        foreach ([1, 6] as $section) {
            $this->get(route('provider.import.dashboard', ['section' => $section, 'action' => 0]))->assertOk()
                ->assertSee('Ver 13 de 13');
        }
    }

    public function test_provider_cannot_upload_or_read_files_of_another_provider_even_for_the_same_patient(): void
    {
        Storage::fake('local');
        [$owner, $provider] = $this->account('document-owner');
        [$other, $otherProvider] = $this->account('document-other');
        $patient = Patient::create(['full_name' => 'Paciente de prueba', 'status' => 'active']);
        $record = $this->record($provider, 'PRIVATE-001', $patient);
        $otherRecord = $this->record($otherProvider, 'PRIVATE-002', $patient);
        $this->actingAs($owner)->post(route('provider.import.documents.store'), [
            'folio' => $record->external_id, 'document_group' => 'logistics',
            'files' => ['entrustment' => UploadedFile::fake()->create('restricted.pdf', 5, 'application/pdf')],
        ])->assertSessionHasNoErrors();
        $document = Document::sole();
        $this->actingAs($other)->get(route('provider.import.documents.view', $document))->assertForbidden();
        $this->post(route('provider.import.documents.store'), [
            'folio' => $record->external_id, 'document_group' => 'regulatory',
            'files' => ['power' => UploadedFile::fake()->create('blocked.pdf', 5, 'application/pdf')],
        ])->assertNotFound();
        $groups = app(ImportExpedientDocuments::class)->listing($otherRecord, $otherRecord->external_id, $other);
        $this->assertEmpty(collect($groups)->flatten(1)->whereNotNull('url'));
        $this->assertDatabaseCount('documents', 1);
        $unlinked = User::create(['name' => 'Sin proveedor', 'username' => 'without-provider', 'role' => 'provider', 'status' => 'active']);
        $this->actingAs($unlinked)->get(route('provider.import.documents.view', $document))->assertForbidden();
    }

    public function test_specialist_tables_share_administration_documents_with_section_specific_actions(): void
    {
        Storage::fake('local');
        [$user, $provider] = $this->account('specialist-document-owner');
        $record = $this->record($provider, 'SPECIALIST-001');
        foreach (['patient' => 'ine', 'regulatory' => 'power', 'logistics' => 'entrustment'] as $group => $key) {
            $this->actingAs($user)->post(route('provider.import.documents.store'), [
                'folio' => $record->external_id, 'document_group' => $group, 'return_section' => 6,
                'files' => [$key => UploadedFile::fake()->create($group.'.pdf', 5, 'application/pdf')],
            ])->assertSessionHasNoErrors();
        }
        $groups = app(ImportExpedientDocuments::class)->listing($record, $record->external_id, $user);
        foreach ([4, 5] as $section) {
            $response = $this->get(route('provider.import.dashboard', ['section' => $section, 'action' => 0]))->assertOk();
            $dom = new \DOMDocument();
            @$dom->loadHTML('<?xml encoding="UTF-8">'.$response->getContent());
            $xpath = new \DOMXPath($dom);
            $headers = $xpath->query('//table[contains(@class,"import-detail-table")]//thead//span[@class="import-column-title"]');
            $labels = array_map(fn ($node) => preg_replace('/\s+/u', ' ', trim($node->textContent)), iterator_to_array($headers));
            $this->assertNotContains('Institución', $labels);
            $this->assertNotContains('Hospital', $labels);
            $this->assertContains('Documentos Logística', $labels);
            $buttons = $xpath->query('//button[@data-expedient-documents and @data-folio="SPECIALIST-001"]');
            $this->assertCount($section === 4 ? 2 : 3, $buttons);
            if ($section === 4) {
                $this->assertNotContains('Documentos Regulatorio', $labels);
                $this->assertSame(0, $xpath->query('//button[@data-expedient-documents and @data-document-group="regulatory"]')->length);
            } else {
                $this->assertContains('Documentos Regulatorio', $labels);
            }
            foreach ($buttons as $button) {
                $group = $button->getAttribute('data-document-group');
                $this->assertSame($group === 'logistics' ? 'true' : 'false', $button->getAttribute('data-can-edit'));
                $this->assertSame($groups[$group], json_decode($button->getAttribute('data-documents'), true));
                $this->get($groups[$group][0]['url'])->assertOk();
            }
        }
    }

    public function test_specialist_sections_reject_patient_and_regulatory_uploads_but_allow_logistics(): void
    {
        Storage::fake('local');
        [$user, $provider] = $this->account('specialist-upload-owner');
        $record = $this->record($provider, 'SPECIALIST-002');
        foreach ([4, 5] as $section) {
            foreach (['patient' => 'ine', 'regulatory' => 'power'] as $group => $key) {
                $this->actingAs($user)->post(route('provider.import.documents.store'), [
                    'folio' => $record->external_id, 'document_group' => $group, 'return_section' => $section,
                    'files' => [$key => UploadedFile::fake()->create('blocked.pdf', 5, 'application/pdf')],
                ])->assertForbidden();
            }
        }
        $this->assertDatabaseCount('documents', 0);
        $this->assertEmpty(Storage::disk('local')->allFiles());
        foreach ([4, 5] as $section) {
            $this->post(route('provider.import.documents.store'), [
                'folio' => $record->external_id, 'document_group' => 'logistics', 'return_section' => $section,
                'files' => ['entrustment' => UploadedFile::fake()->create('logistics-'.$section.'.pdf', 5, 'application/pdf')],
            ])->assertRedirect(route('provider.import.dashboard', ['section' => $section, 'action' => 0]))->assertSessionHasNoErrors();
        }
        $this->assertDatabaseCount('documents', 2);
    }

    public function test_reference_documents_are_private_and_replacement_keeps_only_the_latest_file_visible(): void
    {
        Storage::fake('local');
        [$user] = $this->account('reference-owner');
        foreach (['initial.pdf', 'replacement.pdf'] as $filename) {
            $this->actingAs($user)->post(route('provider.import.documents.store'), [
                'folio' => 'IMP-2026-001', 'document_group' => 'regulatory',
                'files' => ['permit' => UploadedFile::fake()->create($filename, 5, 'application/pdf')],
            ])->assertSessionHasNoErrors();
        }
        $groups = app(ImportExpedientDocuments::class)->listing(null, 'IMP-2026-001', $user);
        $this->assertCount(1, collect($groups['regulatory'])->whereNotNull('url'));
        $this->assertSame('replacement.pdf', $groups['regulatory'][2]['filename']);
        $this->assertDatabaseCount('documents', 2);
        [$other] = $this->account('reference-other');
        $this->actingAs($other)->get(route('provider.import.documents.view', Document::first()))->assertForbidden();
        $this->assertEmpty(collect(app(ImportExpedientDocuments::class)->listing(null, 'IMP-2026-001', $other))->flatten(1)->whereNotNull('url'));
    }

    public function test_upload_validates_category_file_type_size_and_document_slot(): void
    {
        Storage::fake('local');
        [$user] = $this->account('validation-owner');
        $this->actingAs($user);
        $base = ['folio' => 'IMP-2026-001', 'document_group' => 'regulatory'];
        foreach ([
            ['document_group' => 'unknown', 'files' => ['power' => UploadedFile::fake()->create('valid.pdf', 1, 'application/pdf')]],
            ['files' => ['power' => UploadedFile::fake()->create('bad.txt', 1, 'text/plain')]],
            ['files' => ['power' => UploadedFile::fake()->create('large.pdf', 10241, 'application/pdf')]],
            ['files' => []],
        ] as $invalid) {
            $this->post(route('provider.import.documents.store'), array_replace($base, $invalid))->assertSessionHasErrors([], null, 'importDocuments');
        }
        $this->post(route('provider.import.documents.store'), $base + ['files' => ['ine' => UploadedFile::fake()->create('wrong-slot.pdf', 1, 'application/pdf')]])->assertUnprocessable();
        $this->assertDatabaseCount('documents', 0);
        $this->assertEmpty(Storage::disk('local')->allFiles());
    }
}
