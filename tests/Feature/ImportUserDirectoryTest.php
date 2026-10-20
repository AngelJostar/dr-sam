<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ImportUserDirectoryTest extends TestCase
{
    use RefreshDatabase;

    private function account(string $username, string $role, ?string $area = null): User
    {
        return User::query()->create([
            'name' => $username, 'username' => $username, 'email' => $username.'@example.test',
            'role' => $role, 'module' => 'provider_import', 'status' => 'active',
            'metadata' => ['import_area' => $area],
        ]);
    }

    public function test_directory_uses_real_accounts_and_filters_their_assigned_areas(): void
    {
        $admin = $this->account('directory-admin', 'superadmin');
        $provider = $this->account('directory-provider', 'provider');
        $regulatory = $this->account('directory-regulatory', 'provider', 'regulatory');
        $logistics = $this->account('directory-logistics', 'messenger');
        $this->actingAs($admin)->get('/providers/import?section=7&action=0')->assertOk()
            ->assertSee('USR-'.str_pad($provider->id, 6, '0', STR_PAD_LEFT))
            ->assertSee($provider->email)->assertSee($regulatory->email)->assertSee($logistics->email)
            ->assertDontSee('ana.torres@empresa.com');

        foreach ([1 => $provider, 2 => $regulatory, 3 => $logistics, 4 => $admin] as $action => $expected) {
            $response = $this->actingAs($admin)->get('/providers/import?section=7&action='.$action)->assertOk()->assertSee($expected->email);
            foreach ([$provider, $regulatory, $logistics, $admin] as $other) {
                if ($other->id !== $expected->id) $response->assertDontSee($other->email);
            }
        }
    }

    public function test_provider_directory_keeps_its_account_scope(): void
    {
        $provider = $this->account('scoped-provider', 'provider');
        $other = $this->account('other-provider', 'provider');
        $this->actingAs($provider)->get('/providers/import?section=7&action=0')->assertOk()
            ->assertSee($provider->email)->assertDontSee($other->email)->assertDontSee('data-new-import-user', false);
        $this->actingAs($provider)->post(route('superadmin.providers.store'), [])->assertForbidden();
    }

    public function test_superadmin_can_register_an_import_account_in_an_area_with_hashed_password(): void
    {
        $admin = $this->account('creator-admin', 'superadmin');
        $form = [
            'import_user_form' => '1', 'name' => 'Registro QA', 'username' => 'registro-qa',
            'email' => 'registro-qa@example.test', 'password' => 'Only-for-automated-test-123',
            'provider_type' => 'import', 'import_area' => 'regulatory', 'status' => 'active',
            'role' => 'superadmin',
        ];
        $this->actingAs($admin)->from('/providers/import?section=7&action=2')
            ->post(route('superadmin.providers.store'), $form)->assertRedirect('/providers/import?section=7&action=2')->assertSessionHasNoErrors();
        $created = User::where('username', 'registro-qa')->sole();
        $this->assertSame('provider', $created->role);
        $this->assertSame('provider_import', $created->module);
        $this->assertSame('regulatory', data_get($created->metadata, 'import_area'));
        $this->assertTrue(Hash::check($form['password'], $created->password));
        $this->assertDatabaseHas('providers', ['user_id' => $created->id, 'provider_type' => 'import']);
        $this->get('/providers/import?section=7&action=2')->assertSee($created->email)
            ->assertDontSee($form['password'])->assertDontSee($created->password);
        $this->post(route('superadmin.providers.store'), $form)->assertSessionHasErrors(['username', 'email'], null, 'importUser');
        $form['username'] = 'invalid-area';
        $form['email'] = 'invalid-area@example.test';
        $form['import_area'] = 'global-admin';
        $this->post(route('superadmin.providers.store'), $form)->assertSessionHasErrors(['import_area'], null, 'importUser');
        $this->assertDatabaseMissing('users', ['username' => 'invalid-area']);
    }
}
