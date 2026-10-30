<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Wallet\TokenWallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CommunityEventRewardTest extends TestCase
{
    use RefreshDatabase;

    private function account(string $role = 'admin'): User
    {
        return User::query()->create(['name' => 'Reward test', 'username' => uniqid('reward'), 'email' => uniqid('reward').'@example.test', 'role' => $role, 'module' => $role === 'patient' ? 'patient' : 'superadmin', 'status' => 'active']);
    }

    private function event(array $extra = []): array
    {
        return array_merge(['community_key' => 'respira', 'community_name' => 'Respira y Avanza', 'kind' => 'event', 'title' => 'Respiración', 'starts_at' => now()->addDays(5)->format('Y-m-d\TH:i'), 'timezone' => 'America/Mexico_City', 'cost' => 0, 'published' => true], $extra);
    }

    public function test_each_new_event_credits_exactly_one_token_to_its_creator(): void
    {
        $user = $this->account();
        $this->actingAs($user)->postJson('/communities/activities', $this->event(['reward' => 999, 'amount' => 500]))
            ->assertCreated()->assertJsonPath('reward.tokens', 1)->assertJsonPath('reward.balance', 1);
        $this->postJson('/communities/activities', $this->event(['title' => 'Otro evento', 'published' => false]))
            ->assertCreated()->assertJsonPath('reward.balance', 2);
        $this->assertDatabaseCount('wallet_token_entries', 2);
        $this->getJson('/wallet/tokens')->assertOk()->assertJsonPath('balance', 2)->assertJsonCount(2, 'movements');
    }

    public function test_retries_and_publication_changes_do_not_credit_again(): void
    {
        $this->actingAs($this->account());
        $payload = $this->event(['source_key' => 'event-1790000000000-retry']);
        $id = $this->postJson('/communities/activities', $payload)->assertCreated()->json('id');
        $this->postJson('/communities/activities', $payload)->assertOk()->assertJsonPath('id', $id)
            ->assertJsonPath('reward.tokens', 1)->assertJsonPath('reward.credited', false)->assertJsonPath('reward.balance', 1);
        $this->patchJson('/communities/activities/'.$id, ['published' => false])->assertOk();
        $this->patchJson('/communities/activities/'.$id, ['published' => true])->assertOk();
        $this->assertDatabaseCount('community_activities', 1);
        $this->assertDatabaseCount('wallet_token_entries', 1);
    }

    public function test_balances_are_private_and_recipient_cannot_be_overridden(): void
    {
        $creator = $this->account();
        $other = $this->account('patient');
        $this->actingAs($creator)->postJson('/communities/activities', $this->event(['user_id' => $other->id]))->assertCreated();
        $this->actingAs($other)->getJson('/wallet/tokens?user_id='.$creator->id)->assertOk()->assertJsonPath('balance', 0)->assertJsonCount(0, 'movements');
        $this->postJson('/communities/activities', $this->event())->assertForbidden();
        $this->actingAs($creator)->getJson('/wallet/tokens')->assertJsonPath('balance', 1);
        auth()->logout();
        $this->getJson('/wallet/tokens')->assertUnauthorized();
    }

    public function test_invalid_events_and_other_activity_types_do_not_award_tokens(): void
    {
        $this->actingAs($this->account());
        $this->postJson('/communities/activities', $this->event(['title' => '', 'cost' => -1]))->assertUnprocessable();
        foreach (['class', 'workshop', 'podcast', 'live'] as $kind) {
            $this->postJson('/communities/activities', $this->event(['kind' => $kind]))->assertCreated()->assertJsonPath('reward.tokens', 0);
        }
        $this->assertDatabaseCount('wallet_token_entries', 0);
    }

    public function test_event_and_uploaded_image_are_rolled_back_if_reward_cannot_be_saved(): void
    {
        Storage::fake('local');
        $this->mock(TokenWallet::class, function ($mock): void {
            $mock->shouldReceive('creditEvent')->once()->andThrow(new \RuntimeException('Reward write failed'));
        });
        $this->actingAs($this->account())->post('/communities/activities', $this->event([
            'image_file' => UploadedFile::fake()->image('reward.png'),
        ]), ['Accept' => 'application/json'])->assertStatus(500);
        $this->assertDatabaseCount('community_activities', 0);
        $this->assertDatabaseCount('wallet_token_entries', 0);
        $this->assertCount(0, Storage::disk('local')->allFiles('community-activities'));
    }
}
