<?php

namespace Tests\Feature\Api\Mobile;

use App\Models\Patient;
use App\Models\PharmacyPrescriptionUpload;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PharmacyPrescriptionUploadTest extends TestCase
{
    use RefreshDatabase;

    private function patientAccount(string $name): User
    {
        $user = User::query()->create([
            'name' => $name, 'username' => $name, 'email' => $name.'@example.test',
            'role' => 'patient', 'status' => 'active',
        ]);
        Patient::query()->create(['user_id' => $user->id, 'full_name' => $name, 'status' => 'active']);
        return $user;
    }

    public function test_upload_is_private_owned_pending_review_and_cannot_overpost_status(): void
    {
        Storage::fake('local');
        $owner = $this->patientAccount('rx-owner');
        $other = $this->patientAccount('rx-other');
        Sanctum::actingAs($owner, ['mobile:access']);
        $result = $this->postJson('/api/mobile/v1/pharmacy/prescription-uploads', [
            'file' => UploadedFile::fake()->create('receta.pdf', 20, 'application/pdf'),
            'user_id' => $other->id, 'patient_id' => $other->patient->id, 'status' => 'approved',
            'path' => 'public/forged.pdf',
        ])->assertCreated()->assertJsonPath('data.status', 'pending_review')
            ->assertJsonMissingPath('data.path');
        $record = PharmacyPrescriptionUpload::query()->findOrFail($result->json('data.id'));
        $this->assertSame($owner->id, $record->user_id);
        $this->assertSame($owner->patient->id, $record->patient_id);
        $this->assertSame('pending_review', $record->status);
        $this->assertStringStartsWith('pharmacy-prescriptions/'.$owner->id.'/', $record->path);
        Storage::disk('local')->assertExists($record->path);
        $this->get('/api/mobile/v1/pharmacy/prescription-uploads/'.$record->id.'/file')
            ->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Content-Type', 'application/octet-stream');

        Sanctum::actingAs($other, ['mobile:access']);
        $this->getJson('/api/mobile/v1/pharmacy/prescription-uploads/'.$record->id.'/file')
            ->assertNotFound();
    }

    public function test_upload_rejects_unsafe_files_and_excessive_size_without_records(): void
    {
        Storage::fake('local');
        Sanctum::actingAs($this->patientAccount('rx-format'), ['mobile:access']);
        $this->postJson('/api/mobile/v1/pharmacy/prescription-uploads', [
            'file' => UploadedFile::fake()->create('attack.pdf', 10, 'text/html'),
        ])->assertUnprocessable();
        $this->postJson('/api/mobile/v1/pharmacy/prescription-uploads', [
            'file' => UploadedFile::fake()->create('huge.pdf', 8193, 'application/pdf'),
        ])->assertUnprocessable();
        $this->assertSame(0, PharmacyPrescriptionUpload::query()->count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_guest_inactive_wrong_role_and_unscoped_token_cannot_upload(): void
    {
        Storage::fake('local');
        $this->postJson('/api/mobile/v1/pharmacy/prescription-uploads')->assertUnauthorized();
        $user = $this->patientAccount('rx-access');
        Sanctum::actingAs($user, []);
        $this->postJson('/api/mobile/v1/pharmacy/prescription-uploads')->assertForbidden();
        $user->update(['status' => 'inactive']);
        Sanctum::actingAs($user->fresh(), ['mobile:access']);
        $this->postJson('/api/mobile/v1/pharmacy/prescription-uploads')->assertForbidden();
        $user->update(['status' => 'active', 'role' => 'doctor']);
        Sanctum::actingAs($user->fresh(), ['mobile:access']);
        $this->postJson('/api/mobile/v1/pharmacy/prescription-uploads')->assertForbidden();
        $this->assertSame(0, PharmacyPrescriptionUpload::query()->count());
    }
}
