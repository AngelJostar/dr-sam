<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\PatientProfileRelationship;
use App\Models\PatientStory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PatientStoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_photo_is_shared_for_exactly_24_hours_and_never_replaces_the_avatar(): void
    {
        Storage::fake('local');
        $this->travelTo(now()->startOfSecond());
        [$author, $patient] = $this->patient('author');
        [$viewer, $viewerProfile] = $this->patient('viewer');
        $patient->update(['metadata' => ['photo_url' => '/original-avatar.jpg']]);
        $this->actingAs($author)->postJson(route('patient.stories.store'), [
            'profile_id' => $patient->platform_number,
            'photo' => UploadedFile::fake()->image('story.jpg'),
            'avatar' => UploadedFile::fake()->image('profile.png'),
            'expires_at' => now()->addYear(),
        ])->assertCreated();
        $story = PatientStory::query()->sole();
        $this->assertTrue($story->expires_at->equalTo(now()->addHours(24)));
        $this->assertSame('/original-avatar.jpg', $patient->fresh()->metadata['photo_url']);
        $this->assertNotEquals($story->photo_path, $story->avatar_path);
        Storage::disk('local')->assertExists([$story->photo_path, $story->avatar_path]);

        $this->actingAs($viewer)->getJson(route('patient.stories.index', ['profile_id' => $viewerProfile->platform_number]))
            ->assertOk()->assertJsonCount(1, 'groups')->assertJsonPath('groups.0.own', false)
            ->assertJsonPath('groups.0.avatar', route('patient.stories.avatar', $story));
        $this->travelTo($story->expires_at->copy()->subSecond());
        $this->get(route('patient.stories.photo', $story))->assertOk()->assertHeader('Content-Type', 'image/jpeg');
        $this->get(route('patient.stories.avatar', $story))->assertOk();
        $this->travelTo($story->expires_at);
        $this->getJson(route('patient.stories.index', ['profile_id' => $viewerProfile->platform_number]))->assertJsonCount(0, 'groups');
        $this->get(route('patient.stories.photo', $story))->assertNotFound();
        $this->get(route('patient.stories.avatar', $story))->assertNotFound();
        $this->travelBack();
    }

    public function test_seen_is_persistent_per_viewer_and_new_photos_are_unseen(): void
    {
        Storage::fake('local');
        [$author, $patient] = $this->patient('author');
        [$viewer, $viewerProfile] = $this->patient('viewer');
        [$other, $otherProfile] = $this->patient('other');
        $story = $this->publish($author, $patient);
        $payload = ['profile_id' => $viewerProfile->platform_number];
        $this->actingAs($viewer)->postJson(route('patient.stories.seen', $story), $payload)->assertOk();
        $this->postJson(route('patient.stories.seen', $story), $payload)->assertOk();
        $this->assertDatabaseCount('patient_story_views', 1);
        $this->getJson(route('patient.stories.index', $payload))->assertJsonPath('groups.0.stories.0.seen', true);
        $this->get(route('patient.stories.photo', $story))->assertOk();
        $this->actingAs($other)->getJson(route('patient.stories.index', ['profile_id' => $otherProfile->platform_number]))
            ->assertJsonPath('groups.0.stories.0.seen', false);
        $this->publish($author, $patient);
        $this->actingAs($viewer)->getJson(route('patient.stories.index', $payload))
            ->assertJsonCount(2, 'groups.0.stories')->assertJsonPath('groups.0.stories.0.seen', true)
            ->assertJsonPath('groups.0.stories.1.seen', false);
    }

    public function test_authorization_prevents_impersonation_and_deleting_other_stories(): void
    {
        Storage::fake('local');
        [$author, $patient] = $this->patient('author');
        [$viewer, $viewerProfile] = $this->patient('viewer');
        $story = $this->publish($author, $patient);
        $this->actingAs($viewer)->postJson(route('patient.stories.store'), [
            'profile_id' => $patient->platform_number, 'photo' => UploadedFile::fake()->image('test.png'),
        ])->assertForbidden();
        $this->postJson(route('patient.stories.seen', $story), ['profile_id' => $patient->platform_number])->assertForbidden();
        $this->deleteJson(route('patient.stories.destroy', $story), ['profile_id' => $viewerProfile->platform_number])->assertForbidden();
        $this->assertDatabaseCount('patient_stories', 1);
        $this->actingAs($author)->deleteJson(route('patient.stories.destroy', $story), ['profile_id' => $patient->platform_number])->assertOk();
        Storage::disk('local')->assertMissing($story->photo_path);
        $this->assertDatabaseCount('patient_stories', 0);
    }

    public function test_only_valid_photos_are_accepted_and_guests_cannot_view_them(): void
    {
        Storage::fake('local');
        [$user, $patient] = $this->patient('author');
        $this->getJson(route('patient.stories.index', ['profile_id' => $patient->platform_number]))->assertUnauthorized();
        $this->actingAs($user)->postJson(route('patient.stories.store'), [
            'profile_id' => $patient->platform_number, 'photo' => UploadedFile::fake()->create('file.pdf', 12),
        ])->assertUnprocessable()->assertJsonValidationErrors('photo');
        $this->postJson(route('patient.stories.store'), [
            'profile_id' => $patient->platform_number, 'photo' => UploadedFile::fake()->image('large.jpg')->size(10241),
        ])->assertUnprocessable()->assertJsonValidationErrors('photo');
        $this->postJson(route('patient.stories.store'), ['profile_id' => $patient->platform_number])->assertJsonValidationErrors('photo');
        $this->assertDatabaseCount('patient_stories', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
        $story = $this->publish($user, $patient);
        $response = $this->get(route('patient.stories.photo', $story));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        auth()->logout();
        $this->getJson(route('patient.stories.photo', $story))->assertUnauthorized();
    }

    public function test_managed_profile_access_must_be_current_and_allow_stories(): void
    {
        Storage::fake('local');
        [$user, $owner] = $this->patient('owner');
        [, $managed] = $this->patient('managed');
        $link = PatientProfileRelationship::query()->create([
            'administrator_patient_id' => $owner->id, 'managed_patient_id' => $managed->id,
            'access_user_id' => $user->id, 'relationship_type_id' => 4, 'status' => 'active',
            'starts_at' => now()->subDay(), 'ends_at' => now()->addDay(),
        ]);
        $this->publish($user, $managed);
        $link->update(['ends_at' => now()->subMinute()]);
        $this->getJson(route('patient.stories.index', ['profile_id' => $managed->platform_number]))->assertForbidden();
        $link->update(['ends_at' => now()->addDay(), 'permissions' => ['stories' => false]]);
        $this->getJson(route('patient.stories.index', ['profile_id' => $managed->platform_number]))->assertForbidden();
    }

    private function publish(User $user, Patient $patient): PatientStory
    {
        $response = $this->actingAs($user)->postJson(route('patient.stories.store'), [
            'profile_id' => $patient->platform_number, 'photo' => UploadedFile::fake()->image('story.jpg'),
        ])->assertCreated();

        return PatientStory::query()->findOrFail($response->json('story.id'));
    }

    private function patient(string $name): array
    {
        $user = User::query()->create(['name' => $name, 'username' => $name, 'email' => $name.'@test.local', 'role' => 'patient', 'module' => 'patient', 'status' => 'active']);
        $patient = Patient::query()->create(['user_id' => $user->id, 'platform_number' => 'STORY-'.$name, 'full_name' => $name, 'status' => 'active']);

        return [$user, $patient];
    }
}
