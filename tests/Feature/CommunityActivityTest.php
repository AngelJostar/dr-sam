<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CommunityActivityTest extends TestCase
{
    use RefreshDatabase;

    private function account(string $role): User
    {
        return User::query()->create(['name' => 'Actividad test', 'username' => uniqid('activity'), 'email' => uniqid('activity').'@example.test', 'role' => $role, 'module' => $role === 'patient' ? 'patient' : 'superadmin', 'status' => 'active']);
    }

    private function activity(array $extra = []): array
    {
        return array_merge(['community_key' => 'respira', 'community_name' => 'Respira y Avanza', 'kind' => 'class', 'title' => 'Yoga y respiración', 'description' => 'Sesión suave', 'starts_at' => now()->addDays(3)->format('Y-m-d\TH:i'), 'timezone' => 'America/Mexico_City', 'cost' => 250, 'capacity' => 1, 'published' => false], $extra);
    }

    public function test_drafts_are_private_and_only_the_creator_can_publish(): void
    {
        $admin = $this->account('admin');
        $other = $this->account('admin');
        $patient = $this->account('patient');
        $id = $this->actingAs($admin)->postJson('/communities/activities', $this->activity())->assertCreated()->json('id');
        $this->actingAs($patient)->getJson('/communities/activities')->assertJsonCount(0, 'activities');
        $this->postJson('/communities/activities', $this->activity())->assertForbidden();
        $this->getJson('/communities/activities?manage=1')->assertForbidden();
        $this->actingAs($other)->patchJson('/communities/activities/'.$id, ['published' => true])->assertForbidden();
        $this->actingAs($admin)->patchJson('/communities/activities/'.$id, ['published' => true])->assertOk();
        $this->actingAs($patient)->getJson('/communities/activities')->assertJsonPath('activities.0.title', 'Yoga y respiración')->assertJsonPath('activities.0.cost', '250.00');
        $this->actingAs($admin)->patchJson('/communities/activities/'.$id, ['published' => false])->assertOk();
        $this->actingAs($patient)->postJson('/communities/activities/'.$id.'/reservation')->assertNotFound();
    }

    public function test_reservations_are_idempotent_capacity_limited_and_owned(): void
    {
        $admin = $this->account('admin');
        $one = $this->account('patient');
        $two = $this->account('patient');
        $id = $this->actingAs($admin)->postJson('/communities/activities', $this->activity(['published' => true]))->assertCreated()->json('id');
        $url = '/communities/activities/'.$id.'/reservation';
        $this->actingAs($one)->postJson($url)->assertOk();
        $this->postJson($url)->assertOk();
        $this->assertDatabaseCount('community_activity_reservations', 1);
        $this->getJson('/communities/activities')->assertJsonPath('activities.0.reserved', true);
        $this->actingAs($two)->postJson($url)->assertStatus(409);
        $this->deleteJson($url)->assertOk();
        $this->assertDatabaseCount('community_activity_reservations', 1);
        $this->actingAs($one)->deleteJson($url)->assertOk();
        $this->actingAs($two)->postJson($url)->assertOk();
    }

    public function test_public_feed_orders_newest_publications_first_regardless_of_event_date(): void
    {
        $admin = $this->account('admin');
        $this->actingAs($admin);
        $old = $this->postJson('/communities/activities', $this->activity(['published' => true, 'starts_at' => now()->addDay()->format('Y-m-d\TH:i')]))->json('id');
        $new = $this->postJson('/communities/activities', $this->activity(['published' => true, 'starts_at' => now()->addMonth()->format('Y-m-d\TH:i')]))->json('id');
        $newest = $this->postJson('/communities/activities', $this->activity(['published' => true, 'starts_at' => now()->addYear()->format('Y-m-d\TH:i')]))->json('id');
        $draft = $this->postJson('/communities/activities', $this->activity())->json('id');
        DB::table('community_activities')->where('id', $old)->update(['created_at' => now()->subDay()]);
        DB::table('community_activities')->whereIn('id', [$new, $newest])->update(['created_at' => now()]);
        $this->actingAs($this->account('patient'))->getJson('/communities/activities')->assertOk()
            ->assertJsonPath('activities.0.id', $newest)
            ->assertJsonPath('activities.1.id', $new)
            ->assertJsonPath('activities.2.id', $old)
            ->assertJsonMissing(['id' => $draft]);
    }

    public function test_admin_sees_mobile_bookings_and_only_the_owner_can_read_attendees(): void
    {
        $admin = $this->account('admin');
        $other = $this->account('admin');
        $one = $this->account('patient');
        $two = $this->account('patient');
        $id = $this->actingAs($admin)->postJson('/communities/activities', $this->activity(['kind' => 'event', 'published' => true, 'capacity' => 20, 'cost' => 0]))->assertCreated()->json('id');
        $reservation = '/communities/activities/'.$id.'/reservation';
        $attendees = '/communities/activities/'.$id.'/attendees';
        $this->actingAs($one)->postJson($reservation)->assertOk();
        $this->postJson($reservation)->assertOk();
        $this->actingAs($two)->postJson($reservation)->assertOk();

        $this->getJson($attendees)->assertForbidden();
        $this->getJson('/communities/activities')->assertJsonMissingPath('activities.0.attendees');
        $this->actingAs($other)->getJson($attendees)->assertForbidden();
        $this->getJson('/communities/activities?manage=1')->assertJsonCount(0, 'activities');
        $this->actingAs($admin)->getJson('/communities/activities?manage=1')->assertJsonPath('activities.0.reservations', 2);
        $this->getJson($attendees)->assertOk()->assertJsonPath('total', 2)->assertJsonCount(2, 'attendees')
            ->assertJsonPath('attendees.0.username', $two->username)->assertJsonPath('attendees.1.name', $one->name)
            ->assertJsonMissingPath('attendees.0.email')->assertJsonMissingPath('attendees.0.password');

        $this->actingAs($one)->deleteJson($reservation)->assertOk();
        $this->actingAs($admin)->getJson('/communities/activities?manage=1')->assertJsonPath('activities.0.reservations', 1);
        $this->getJson($attendees)->assertJsonPath('total', 1)->assertJsonCount(1, 'attendees')->assertJsonMissing(['username' => $one->username]);
        $this->actingAs($two)->deleteJson($reservation)->assertOk();
        $this->actingAs($admin)->getJson($attendees)->assertJsonPath('total', 0)->assertJsonCount(0, 'attendees');
        auth()->logout();
        $this->getJson($attendees)->assertUnauthorized();
    }

    public function test_attendees_are_paginated_without_mixing_events(): void
    {
        $this->actingAs($this->account('admin'));
        $id = $this->postJson('/communities/activities', $this->activity(['capacity' => 100]))->assertCreated()->json('id');
        $other = $this->postJson('/communities/activities', $this->activity())->assertCreated()->json('id');
        for ($i = 0; $i < 51; $i++) {
            DB::table('community_activity_reservations')->insert(['activity_id' => $id, 'user_id' => $this->account('patient')->id, 'created_at' => now(), 'updated_at' => now()]);
        }
        DB::table('community_activity_reservations')->insert(['activity_id' => $other, 'user_id' => $this->account('patient')->id, 'created_at' => now(), 'updated_at' => now()]);
        $url = '/communities/activities/'.$id.'/attendees';
        $this->getJson($url)->assertJsonPath('total', 51)->assertJsonPath('last_page', 2)->assertJsonCount(50, 'attendees');
        $this->getJson($url.'?page=2')->assertJsonPath('page', 2)->assertJsonCount(1, 'attendees');
        $this->getJson('/communities/activities/'.$other.'/attendees')->assertJsonPath('total', 1);
    }

    public function test_all_activity_types_support_drafts_and_invalid_data_is_rejected(): void
    {
        $this->actingAs($this->account('admin'));
        foreach (['class', 'event', 'workshop', 'podcast', 'live'] as $kind) {
            $this->postJson('/communities/activities', $this->activity(['kind' => $kind]))->assertCreated();
        }
        $this->postJson('/communities/activities', $this->activity(['image' => 'javascript:alert(1)', 'cost' => -1, 'starts_at' => '2020-01-01T12:00']))->assertUnprocessable();
        $this->getJson('/communities/activities?manage=1')->assertJsonCount(5, 'activities');
    }

    public function test_past_activities_cannot_be_reserved_and_visitors_must_sign_in(): void
    {
        $admin = $this->account('admin');
        $id = $this->actingAs($admin)->postJson('/communities/activities', $this->activity(['published' => true]))->json('id');
        DB::table('community_activities')->where('id', $id)->update(['starts_at' => now()->subDay()]);
        $this->actingAs($this->account('patient'))->postJson('/communities/activities/'.$id.'/reservation')->assertUnprocessable();
        auth()->logout();
        $this->getJson('/communities/activities')->assertUnauthorized();
    }

    public function test_publishing_an_event_with_a_photo_makes_both_visible_in_the_public_feed(): void
    {
        Storage::fake('local');
        $this->actingAs($this->account('admin'));
        $created = $this->post('/communities/activities', $this->activity([
            'kind' => 'event', 'published' => '1',
            'image_file' => UploadedFile::fake()->image('yoga.png'),
        ]), ['Accept' => 'application/json'])->assertCreated()->json();
        $stored = DB::table('community_activities')->find($created['id']);
        Storage::disk('local')->assertExists($stored->image);
        $this->actingAs($this->account('patient'))->getJson('/communities/activities')
            ->assertJsonPath('activities.0.id', $created['id'])
            ->assertJsonPath('activities.0.image', $created['image'])
            ->assertJsonPath('activities.0.description', 'Sesión suave');
        $this->get($created['image'])->assertOk()->assertHeader('Content-Type', 'image/png');
    }

    public function test_draft_photos_remain_private_and_invalid_uploads_create_no_activity(): void
    {
        Storage::fake('local');
        $admin = $this->account('admin');
        $created = $this->actingAs($admin)->post('/communities/activities', $this->activity([
            'image_file' => UploadedFile::fake()->image('draft.png'),
        ]), ['Accept' => 'application/json'])->assertCreated()->json();
        $this->get($created['image'])->assertOk();
        $this->actingAs($this->account('patient'))->get($created['image'])->assertNotFound();
        $this->actingAs($admin)->post('/communities/activities', $this->activity([
            'published' => '1', 'image_file' => UploadedFile::fake()->create('script.svg', 1, 'image/svg+xml'),
        ]), ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors('image_file');
        $this->assertDatabaseCount('community_activities', 1);
    }

    public function test_legacy_event_is_imported_once_and_published_in_patient_feed(): void
    {
        $this->actingAs($this->account('admin'));
        $data = $this->activity(['title' => 'Taller de Barro', 'community_key' => 'community-yoga-mente', 'source_key' => 'event-1792770000000-abc123', 'published' => true]);
        $id = $this->postJson('/communities/activities', $data)->assertCreated()->json('id');
        $this->postJson('/communities/activities', $data)->assertOk()->assertJsonPath('id', $id);
        $this->assertDatabaseCount('community_activities', 1);
        $this->actingAs($this->account('patient'))->getJson('/communities/activities')->assertOk()->assertJsonPath('activities.0.title', 'Taller de Barro')->assertJsonPath('activities.0.published', true);
    }
}
