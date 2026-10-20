<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Doctor;
use App\Models\Institution;
use App\Models\MedicalUnit;
use App\Models\Patient;
use App\Models\Provider;
use App\Models\ProviderRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class ImportExpedientTest extends TestCase
{
    use RefreshDatabase;

    private function providerUser(string $key = 'one'): array
    {
        $user = User::create(['name' => 'Importador '.$key, 'username' => 'expedient-'.$key, 'role' => 'provider', 'status' => 'active']);
        $provider = Provider::create(['user_id' => $user->id, 'name' => 'Importador '.$key, 'provider_type' => 'import', 'status' => 'active']);
        return [$user, $provider];
    }

    private function data(Provider $provider, array $overrides = []): array
    {
        return array_replace([
            'intent' => 'create', 'submission_token' => (string) Str::uuid(), 'step' => 5,
            'provider_id' => $provider->id, 'first_name' => 'Andrea', 'first_surname' => 'Navarro', 'second_surname' => 'López',
            'birth_date' => '1988-03-14', 'sex' => 'Femenino', 'expedient_type' => 'Importación de medicamento', 'priority' => 'Estándar',
        ], $overrides);
    }

    public function test_full_wizard_creates_a_patient_expedient_and_private_documents(): void
    {
        Storage::fake('local');
        [$user, $provider] = $this->providerUser();
        $institution = Institution::create(['name' => 'Institución prueba', 'status' => 'active']);
        $unit = MedicalUnit::create(['institution_id' => $institution->id, 'name' => 'Hospital prueba', 'status' => 'active']);
        $doctor = Doctor::create(['medical_unit_id' => $unit->id, 'full_name' => 'Médico prueba', 'status' => 'active']);
        $url = route('provider.import.dashboard', ['section' => 1, 'action' => 0]);
        $this->actingAs($user)->get($url)->assertOk()->assertSee('Nuevo Expediente')->assertSee('Revisa antes de crear');
        $response = $this->postJson(route('provider.import.expedients.store'), $this->data($provider, [
            'phone' => '5500000000', 'email' => 'wizard@example.test', 'address' => 'Domicilio de prueba',
            'institution_id' => $institution->id, 'medical_unit_id' => $unit->id, 'doctor_id' => $doctor->id,
            'specialty' => 'Medicina interna', 'service' => 'Consulta externa', 'medication_name' => 'Medicamento prueba',
            'files' => [UploadedFile::fake()->create('receta.pdf', 20, 'application/pdf')], 'document_types' => ['Receta médica'],
        ]))->assertOk()->assertJsonPath('status', 'documentation');
        $record = ProviderRequest::findOrFail($response->json('id'));
        $this->assertSame($provider->id, $record->provider_id);
        $this->assertSame('Andrea Navarro López', $record->patient->full_name);
        $this->assertSame('wizard@example.test', $record->patient->email);
        $this->assertSame($unit->id, $record->medical_unit_id);
        $this->assertSame('Consulta externa', data_get($record->payload, 'expedient.service'));
        $this->assertDatabaseHas('audit_logs', ['event' => 'provider.expedient.created', 'auditable_id' => $record->id]);
        $document = Document::sole();
        $this->assertSame($record->patient_id, $document->patient_id);
        Storage::disk('local')->assertExists($document->file_path);
        $this->get(route('provider.import.documents.view', $document))->assertOk();
        $this->get($url)->assertOk()->assertSee($record->external_id)->assertSee('Andrea Navarro López')->assertSee('1 de 5');
        [$other] = $this->providerUser('other');
        $this->actingAs($other)->get($url)->assertOk()->assertDontSee($record->external_id);
        $this->get(route('provider.import.documents.view', $document))->assertForbidden();
    }

    public function test_draft_can_resume_remove_files_and_finish_without_duplicate_records(): void
    {
        Storage::fake('local');
        [$user, $provider] = $this->providerUser();
        $data = $this->data($provider, ['intent' => 'draft', 'step' => 1, 'first_surname' => '', 'birth_date' => '']);
        $saved = $this->actingAs($user)->postJson(route('provider.import.expedients.store'), [...$data,
            'files' => [UploadedFile::fake()->create('identificacion.pdf', 10, 'application/pdf'), UploadedFile::fake()->create('otro.pdf', 10, 'application/pdf')],
            'document_types' => ['INE', 'Otro documento'],
        ])->assertOk()->assertJsonPath('status', 'draft');
        $id = $saved->json('id');
        $documents = Document::orderBy('id')->get();
        $this->assertDatabaseCount('patients', 0);
        $this->get(route('provider.import.expedients.draft', $id))->assertOk()->assertJsonPath('fields.step', 1)->assertJsonCount(2, 'documents');
        $this->get(route('provider.import.dashboard', ['section' => 1, 'action' => 0]))->assertSee('Continuar borrador');
        [$other] = $this->providerUser('other');
        $this->actingAs($other)->get(route('provider.import.expedients.draft', $id))->assertNotFound();
        $this->get(route('provider.import.documents.view', $documents[0]))->assertForbidden();
        $institution = Institution::create(['name' => 'Institución nueva', 'status' => 'active']);
        $final = $this->data($provider, ['draft_id' => $id, 'submission_token' => $data['submission_token'], 'institution_id' => $institution->id,
            'new_hospital_name' => 'Hospital nuevo', 'removed_documents' => [$documents[1]->id]]);
        $this->actingAs($user)->postJson(route('provider.import.expedients.store'), $final)->assertOk()->assertJsonPath('id', $id)->assertJsonPath('folio', $saved->json('folio'));
        $this->assertDatabaseCount('provider_requests', 1);
        $this->assertDatabaseCount('patients', 1);
        $this->assertSame('Hospital nuevo', ProviderRequest::find($id)->medicalUnit->name);
        $this->assertSame(ProviderRequest::find($id)->patient_id, $documents[0]->fresh()->patient_id);
        $this->get(route('provider.import.documents.view', $documents[1]))->assertNotFound();
        $this->get(route('provider.import.expedients.draft', $id))->assertNotFound();
    }

    public function test_matches_are_scoped_and_existing_patient_requires_a_choice_without_overwriting_identity(): void
    {
        [$user, $provider] = $this->providerUser();
        [$other, $otherProvider] = $this->providerUser('other');
        $patient = Patient::create(['full_name' => 'Andrea Navarro López', 'birth_date' => '1988-03-14', 'email' => 'original@example.test', 'status' => 'active']);
        $hidden = Patient::create(['full_name' => 'Andrea Navarro López', 'birth_date' => '1988-03-14', 'status' => 'active']);
        ProviderRequest::create(['provider_id' => $provider->id, 'patient_id' => $patient->id, 'request_type' => 'import']);
        ProviderRequest::create(['provider_id' => $otherProvider->id, 'patient_id' => $hidden->id, 'request_type' => 'import']);
        $data = $this->data($provider, ['email' => 'new@example.test']);
        $this->actingAs($user)->postJson(route('provider.import.expedients.matches'), $data)
            ->assertOk()->assertJsonCount(1, 'matches')->assertJsonPath('matches.0.id', $patient->id);
        $this->postJson(route('provider.import.expedients.store'), $data)->assertUnprocessable()->assertJsonValidationErrors('patient_id');
        $this->postJson(route('provider.import.expedients.store'), [...$data, 'patient_id' => $hidden->id])->assertForbidden();
        $response = $this->postJson(route('provider.import.expedients.store'), [...$data, 'patient_id' => $patient->id])->assertOk();
        $this->assertSame($patient->id, ProviderRequest::find($response->json('id'))->patient_id);
        $this->assertSame('original@example.test', $patient->fresh()->email);
        $this->assertDatabaseCount('patients', 2);
    }

    public function test_optional_sections_can_be_pending_but_patient_identity_is_required(): void
    {
        [$user, $provider] = $this->providerUser();
        $data = $this->data($provider, ['birth_date' => now()->addDay()->toDateString(), 'first_name' => '']);
        $this->actingAs($user)->postJson(route('provider.import.expedients.store'), $data)
            ->assertUnprocessable()->assertJsonValidationErrors(['first_name', 'birth_date']);
        $this->postJson(route('provider.import.expedients.store'), $this->data($provider))->assertOk()->assertJsonPath('status', 'documentation');
        $this->assertNull(ProviderRequest::sole()->medical_unit_id);
        $this->assertSame([], data_get(ProviderRequest::sole()->payload, 'prescription_items'));
    }

    public function test_provider_permissions_and_hospital_doctor_consistency_are_enforced(): void
    {
        [$user, $provider] = $this->providerUser();
        [$other, $otherProvider] = $this->providerUser('other');
        $unit = MedicalUnit::create(['name' => 'Hospital uno', 'status' => 'active']);
        $otherUnit = MedicalUnit::create(['name' => 'Hospital dos', 'status' => 'active']);
        $doctor = Doctor::create(['medical_unit_id' => $otherUnit->id, 'full_name' => 'Médico de otro hospital', 'status' => 'active']);
        $this->actingAs($user)->postJson(route('provider.import.expedients.store'), $this->data($otherProvider))->assertForbidden();
        $this->postJson(route('provider.import.expedients.store'), $this->data($provider, ['medical_unit_id' => $unit->id, 'doctor_id' => $doctor->id]))
            ->assertUnprocessable()->assertJsonValidationErrors('doctor_id');
        $unlinked = User::create(['name' => 'Sin proveedor', 'username' => 'unlinked', 'role' => 'provider', 'status' => 'active']);
        $this->actingAs($unlinked)->postJson(route('provider.import.expedients.store'), $this->data($provider))->assertForbidden();
        $this->assertDatabaseCount('provider_requests', 0);
    }

    public function test_invalid_files_are_rejected_without_creating_a_patient_or_record(): void
    {
        Storage::fake('local');
        [$user, $provider] = $this->providerUser();
        $this->actingAs($user)->postJson(route('provider.import.expedients.store'), $this->data($provider, [
            'files' => [UploadedFile::fake()->create('script.svg', 5, 'image/svg+xml'), UploadedFile::fake()->create('grande.pdf', 10241, 'application/pdf')],
        ]))->assertUnprocessable()->assertJsonValidationErrors(['files.0', 'files.1']);
        $this->assertDatabaseCount('patients', 0);
        $this->assertDatabaseCount('provider_requests', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_admin_can_save_an_empty_draft_and_assign_a_provider_later(): void
    {
        [$user, $provider] = $this->providerUser();
        $admin = User::create(['name' => 'Admin', 'username' => 'expedient-admin', 'role' => 'admin', 'status' => 'active']);
        $data = $this->data($provider, ['intent' => 'draft', 'provider_id' => null, 'first_name' => null, 'first_surname' => null, 'birth_date' => null]);
        $saved = $this->actingAs($admin)->postJson(route('provider.import.expedients.store'), $data)->assertOk();
        $this->assertNull(ProviderRequest::sole()->provider_id);
        $this->postJson(route('provider.import.expedients.store'), $this->data($provider, ['draft_id' => $saved->json('id'), 'submission_token' => $data['submission_token']]))
            ->assertOk()->assertJsonPath('status', 'documentation');
        $this->assertSame($provider->id, ProviderRequest::sole()->provider_id);
    }
}
