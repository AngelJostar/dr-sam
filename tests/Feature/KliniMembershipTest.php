<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use App\Services\Platform\PlatformAuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class KliniMembershipTest extends TestCase
{
    use RefreshDatabase;

    private function registration(array $overrides = []): array
    {
        return array_merge([
            'registration_type' => 'patient', 'first_name' => 'Ana', 'last_name' => 'Prueba',
            'email' => 'ana.klini@example.test', 'username' => 'ana.klini',
            'password' => 'CuentaKlini2026', 'password_confirmation' => 'CuentaKlini2026',
        ], $overrides);
    }

    private function admin(): User
    {
        return User::query()->create(['name' => 'Admin', 'username' => 'membership.admin', 'role' => 'superadmin', 'module' => 'superadmin', 'status' => 'active']);
    }

    public function test_home_registration_creates_patient_membership_without_operational_privileges(): void
    {
        $this->get('/')->assertOk()->assertSee(route('klini.register'))->assertSee('Crear mi cuenta Klini');
        $response = $this->post(route('klini.register'), $this->registration([
            'role' => 'superadmin', 'module' => 'superadmin', 'account_category' => 'operational',
            'passwordless_review' => true, 'is_demo' => true, 'klini_member_id' => 'FORGED',
        ]))->assertRedirect('/')->assertSessionHasNoErrors();
        $user = User::query()->where('username', 'ana.klini')->firstOrFail();
        $this->assertSame('klini', $user->account_category);
        $this->assertMatchesRegularExpression('/^\d{8,}$/', $user->klini_member_id);
        $this->assertNull($user->operational_user_number);
        $this->assertSame('patient', $user->role);
        $this->assertSame('patient', $user->module);
        $this->assertFalse($user->passwordless_review);
        $this->assertFalse($user->is_demo);
        $this->assertNull($user->email_verified_at);
        $this->assertTrue(Hash::check('CuentaKlini2026', $user->password));
        $this->assertSame('Ana Prueba', $user->patient->full_name);
        $this->assertNull($user->patient->platform_number);
        $response->assertSessionHas('klini_registration.member_id', $user->klini_member_id);
        $this->get('/')->assertSee($user->klini_member_id)->assertSee('Tu cuenta está lista.');
        $this->assertStringNotContainsString('CuentaKlini2026', AuditLog::query()->firstOrFail()->toJson());
        $this->assertGuest();
        $this->post('/demo-login', ['username' => $user->email, 'password' => 'CuentaKlini2026'])->assertRedirect(route('patient.dashboard'));
        $this->get(route('patient.dashboard'))->assertOk();
        $this->get(route('superadmin.catalog', 'users'))->assertForbidden();
    }

    public function test_public_doctor_registration_requires_credentials_and_admin_activation(): void
    {
        $data = $this->registration(['registration_type' => 'doctor']);
        $this->post(route('klini.register'), $data)->assertSessionHasErrorsIn('registration', ['professional_license', 'specialty']);
        $this->assertDatabaseCount('users', 0);
        $this->post(route('klini.register'), array_merge($data, ['professional_license' => 'TEST-123', 'specialty' => 'Medicina general']))
            ->assertRedirect('/')->assertSessionHasNoErrors();
        $user = User::query()->firstOrFail();
        $this->assertSame('klini', $user->account_category);
        $this->assertSame('inactive', $user->status);
        $this->assertSame('inactive', $user->doctor->status);
        $this->assertNull($user->doctor->verified_at);
        $this->assertNull($user->doctor->medical_unit_id);
        $this->from('/')->post('/demo-login', ['username' => $user->username, 'password' => 'CuentaKlini2026'])->assertSessionHasErrors('username');
        $this->assertGuest();
        $memberId = $user->klini_member_id;
        $this->actingAs($this->admin())->patch(route('superadmin.users.update', $user), ['status' => 'active', 'email_validation' => 'validated'])->assertRedirect();
        $this->assertSame($memberId, $user->fresh()->klini_member_id);
        $this->assertSame('active', $user->doctor->fresh()->status);
        $this->post(route('logout'));
        $this->post('/demo-login', ['username' => $user->username, 'password' => 'CuentaKlini2026'])->assertRedirect(route('doctor.dashboard'));
        $this->get(route('doctor.dashboard'))->assertOk();
    }

    public function test_registration_rejects_invalid_roles_passwords_and_duplicate_credentials(): void
    {
        $this->post(route('klini.register'), $this->registration(['registration_type' => 'superadmin']))->assertSessionHasErrorsIn('registration', ['registration_type']);
        $this->post(route('klini.register'), $this->registration(['password_confirmation' => 'Different2026']))->assertSessionHasErrorsIn('registration', ['password']);
        $this->post(route('klini.register'), $this->registration(['password' => 'short', 'password_confirmation' => 'short']))->assertSessionHasErrorsIn('registration', ['password']);
        $this->assertDatabaseCount('users', 0);
        $this->post(route('klini.register'), $this->registration())->assertSessionHasNoErrors();
        $this->post(route('klini.register'), $this->registration(['username' => 'ANA.KLINI', 'email' => 'ANA.KLINI@EXAMPLE.TEST']))
            ->assertSessionHasErrorsIn('registration', ['email', 'username'])
            ->assertSessionMissing('_old_input.password')->assertSessionMissing('_old_input.password_confirmation');
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('patients', 1);
        $this->get('/')->assertSee('Este correo electrónico ya está registrado.');
    }

    public function test_real_members_cannot_bypass_passwords_in_review_mode(): void
    {
        config(['drsam.review_passwordless' => true]);
        $this->post(route('klini.register'), $this->registration())->assertSessionHasNoErrors();
        foreach ([null, 'incorrect'] as $password) {
            $this->from('/')->post('/demo-login', ['username' => 'ana.klini', 'password' => $password])
                ->assertSessionHasErrors('password')->assertSessionMissing('_old_input.password');
            $this->assertGuest();
        }
        $this->get('/acceso')->assertDontSee('ana.klini')
            ->assertSee(route('home', ['login' => 1]))
            ->assertSee(route('home', ['register' => 'doctor']));
    }

    public function test_registration_is_atomic_when_profile_audit_fails(): void
    {
        $this->mock(PlatformAuditService::class)->shouldReceive('record')->once()->andThrow(new \RuntimeException('Audit unavailable'));
        $this->post(route('klini.register'), $this->registration())->assertStatus(500);
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('patients', 0);
    }

    public function test_registration_is_rate_limited_and_not_available_to_authenticated_users(): void
    {
        foreach (range(1, 6) as $attempt) {
            $this->post(route('klini.register'), [])->assertRedirect('/');
        }
        $this->post(route('klini.register'), $this->registration())->assertStatus(429);
        $this->actingAs($this->admin())->post(route('klini.register'), $this->registration())->assertRedirect();
        $this->assertDatabaseMissing('users', ['username' => 'ana.klini']);
    }

    public function test_existing_accounts_receive_stable_ids_without_changing_clinical_identifiers(): void
    {
        $patientUser = User::query()->create(['name' => 'Patient', 'username' => 'old.patient', 'role' => 'patient', 'status' => 'active']);
        $doctorUser = User::query()->create(['name' => 'Doctor', 'username' => 'old.doctor', 'role' => 'doctor', 'status' => 'inactive']);
        $operational = $this->admin();
        Patient::query()->create(['user_id' => $patientUser->id, 'full_name' => 'Patient', 'platform_number' => 'PAT-EXISTING']);
        Doctor::query()->create(['user_id' => $doctorUser->id, 'full_name' => 'Doctor', 'external_id' => 'DOC-EXISTING']);
        $migration = require database_path('migrations/2026_10_13_000002_assign_existing_klini_members.php');
        $migration->up();
        $numericMigration = require database_path('migrations/2026_10_13_000003_use_numeric_klini_member_ids.php');
        $numericMigration->up();
        $ids = User::query()->where('account_category', 'klini')->pluck('klini_member_id')->all();
        $this->assertCount(2, array_unique($ids));
        foreach ($ids as $id) {
            $this->assertMatchesRegularExpression('/^\d{8,}$/', $id);
        }
        $migration->up();
        $numericMigration->up();
        $this->assertSame($ids, User::query()->where('account_category', 'klini')->pluck('klini_member_id')->all());
        $this->assertSame('PAT-EXISTING', $patientUser->patient->platform_number);
        $this->assertSame('DOC-EXISTING', $doctorUser->doctor->external_id);
        $this->assertSame('inactive', $doctorUser->fresh()->status);
        $this->assertSame('operational', $operational->fresh()->account_category);
        $this->assertNull($operational->fresh()->klini_member_id);
        $this->assertMatchesRegularExpression('/^OP\d{8,}$/', $operational->fresh()->operational_user_number);
    }

    public function test_filters_pagination_csv_and_edits_keep_members_separate_from_operational_accounts(): void
    {
        $admin = $this->admin();
        foreach (range(1, 21) as $i) {
            $member = User::query()->create(['name' => 'Member '.$i, 'username' => 'member.'.$i, 'role' => 'patient', 'status' => 'active', 'account_category' => 'klini']);
            $member->assignKliniMemberId();
        }
        $operational = User::query()->create(['name' => 'Operational Doctor', 'username' => 'operational.doctor', 'role' => 'doctor', 'status' => 'active']);
        $url = route('superadmin.catalog', ['section' => 'users', 'category' => 'klini', 'page' => 2]);
        $response = $this->actingAs($admin)->get($url)->assertOk()->assertSee('Usuario Klini')->assertSee('No. usuario operativo')
            ->assertDontSee('Operational Doctor')->assertViewHas('records', fn ($records) => $records->total() === 21 && $records->count() === 1);
        $dom = new \DOMDocument;
        @$dom->loadHTML($response->getContent());
        $xpath = new \DOMXPath($dom);
        $tile = $xpath->query('//a[@data-admin-filter="klini"]')->item(0);
        $this->assertSame('true', $tile->getAttribute('aria-current'));
        parse_str(parse_url($tile->getAttribute('href'), PHP_URL_QUERY), $query);
        $this->assertSame('klini', $query['category']);
        $this->assertArrayNotHasKey('page', $query);
        $all = $xpath->query('//a[@data-admin-filter="all"]')->item(0);
        $this->assertStringNotContainsString('category=', $all->getAttribute('href'));
        $csv = $this->get(route('superadmin.catalog.csv', ['section' => 'users', 'category' => 'klini']))->assertOk()->streamedContent();
        $this->assertStringContainsString($member->klini_member_id, $csv);
        $this->assertStringNotContainsString('operational.doctor', $csv);
        $this->get(route('superadmin.catalog', ['section' => 'users', 'category' => 'operational']))
            ->assertOk()->assertSee('Operational Doctor')->assertSee($operational->operational_user_number)->assertDontSee('Member 21');
        $operationalCsv = $this->get(route('superadmin.catalog.csv', ['section' => 'users', 'category' => 'operational']))->assertOk()->streamedContent();
        $this->assertStringContainsString($operational->operational_user_number, $operationalCsv);
        $this->get(route('superadmin.catalog', ['section' => 'users', 'q' => strtolower($operational->operational_user_number)]))
            ->assertViewHas('records', fn ($records) => $records->total() === 1 && $records->first()->id === $operational->id);
        $this->get(route('superadmin.catalog', ['section' => 'users', 'q' => $member->klini_member_id]))
            ->assertViewHas('records', fn ($records) => $records->total() === 1 && $records->first()->id === $member->id);
        $id = $member->klini_member_id;
        $this->patch(route('superadmin.users.update', $member), ['status' => 'suspended', 'name' => 'Renamed member', 'klini_member_id' => 'FORGED', 'account_category' => 'operational'])->assertRedirect();
        $this->assertSame($id, $member->fresh()->klini_member_id);
        $this->assertSame('klini', $member->fresh()->account_category);
        $this->assertNull($operational->fresh()->klini_member_id);
    }

    public function test_operational_number_is_stable_when_account_and_clinical_details_change(): void
    {
        $user = User::query()->create(['name' => 'Operational', 'username' => 'stable.operator', 'role' => 'doctor']);
        $doctor = Doctor::query()->create(['user_id' => $user->id, 'full_name' => 'Operational', 'external_id' => 'MED-OLD']);
        $number = $user->operational_user_number;
        $this->assertMatchesRegularExpression('/^OP\d{8,}$/', $number);
        $user->update(['name' => 'Renamed', 'username' => 'renamed.operator', 'status' => 'inactive']);
        $doctor->update(['external_id' => 'MED-NEW']);
        $this->assertSame($number, $user->fresh()->operational_user_number);
        $this->assertNull($user->fresh()->klini_member_id);
    }
}
