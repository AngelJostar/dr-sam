<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Patient;
use App\Models\PlatformModule;
use App\Models\MobileDeviceRegistration;
use App\Services\Platform\MobileAppConfiguration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminMobileAppTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_management_has_all_reviewed_sections_and_no_phone_preview(): void
    {
        $admin = User::query()->create([
            'name' => 'Super Admin', 'username' => 'mobile.preview.admin',
            'role' => 'superadmin', 'module' => 'superadmin', 'status' => 'active',
        ]);

        $this->actingAs($admin)->get(route('superadmin.mobile-app'))
            ->assertOk()
            ->assertSee('superadmin-catalog-body', false)
            ->assertSee('Menú del Superadministrador')
            ->assertSee(route('superadmin.catalog', 'doctors'))
            ->assertSee('Menús y navegación')->assertSee('Botones y textos')
            ->assertSee('Parámetros corporales y Apple Salud')->assertSee('Local')->assertSee('Parcial')
            ->assertDontSee('<iframe', false)->assertDontSee('Vista amplia');

        foreach (['navigation', 'texts', 'forms', 'users', 'catalogs', 'communities', 'subscriptions', 'advertising', 'notifications', 'health', 'native'] as $section) {
            $this->get(route('superadmin.mobile-app', ['section' => $section]))->assertOk()->assertDontSee('<iframe', false);
        }
        $this->get(route('superadmin.mobile-app', ['section' => 'unknown']))->assertNotFound();
    }

    public function test_mobile_admin_workspace_requires_superadmin_access(): void
    {
        $this->get(route('superadmin.mobile-app'))->assertRedirect(route('login'));

        $patient = User::query()->create([
            'name' => 'Patient', 'username' => 'mobile.preview.patient',
            'role' => 'patient', 'module' => 'patient', 'status' => 'active',
        ]);
        $this->actingAs($patient)->get(route('superadmin.mobile-app'))->assertForbidden();
        $this->put(route('superadmin.mobile-app.update', 'texts'), [])->assertForbidden();
    }

    public function test_saved_labels_and_forms_are_rendered_by_the_actual_mobile_portal(): void
    {
        $admin = $this->admin();
        PlatformModule::query()->create(['key' => 'patient', 'label' => 'Paciente', 'target' => 'patient.dashboard', 'enabled' => true, 'roles' => ['patient'], 'settings' => ['unrelated' => 'preserved']]);
        $values = app(MobileAppConfiguration::class)->read();
        $values['texts']['wallet_title'] = 'Mi billetera Klini';
        $values['texts']['confirm_booking'] = 'Reservar mi cita';
        $values['texts']['view_slots'] = 'Consultar disponibilidad';
        $this->actingAs($admin)->put(route('superadmin.mobile-app.update', 'texts'), ['revision' => 0, 'values' => $values['texts']])
            ->assertRedirect(route('superadmin.mobile-app', ['section' => 'texts']))->assertSessionHasNoErrors();
        $values['forms']['booking_success_title'] = 'Tu cita está lista';
        $this->put(route('superadmin.mobile-app.update', 'forms'), ['revision' => 1, 'values' => $values['forms']])->assertSessionHasNoErrors();

        $patientUser = $this->patient();
        $this->actingAs($patientUser)->get(route('patient.dashboard'))->assertOk()
            ->assertSee('Mi billetera Klini')->assertSee('Reservar mi cita')->assertSee('Tu cita está lista')
            ->assertSee('data-booking-schedule-label="Consultar disponibilidad"', false);
        $this->assertSame('preserved', PlatformModule::query()->where('key', 'patient')->first()->settings['unrelated']);
        $this->assertDatabaseHas('audit_logs', ['event' => 'superadmin.mobile.configuration.updated', 'user_id' => $admin->id]);
    }

    public function test_navigation_saves_visibility_names_and_order_without_changing_destinations(): void
    {
        $values = $this->navigationValues();
        $values['calendar']['label'] = 'Mi agenda';
        $values['calendar']['order'] = 1;
        $values['home']['order'] = 2;
        $values['devices']['visible'] = false;
        $values['wellness']['visible'] = false;
        $this->actingAs($this->admin())->put(route('superadmin.mobile-app.update', 'navigation'), ['revision' => 0, 'values' => $values])->assertSessionHasNoErrors();
        $html = $this->actingAs($this->patient())->get(route('patient.dashboard'))->assertOk()->getContent();
        $dom = new \DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        $xpath = new \DOMXPath($dom);
        $buttons = $xpath->query('//nav[contains(@class,"patient-portal-bottom-nav")]/button');
        $this->assertCount(4, $buttons);
        $this->assertSame('calendar', $buttons->item(0)->getAttribute('data-open-view'));
        $this->assertSame('Mi agenda', $buttons->item(0)->getAttribute('aria-label'));
        $this->assertSame(0, $xpath->query('//aside[@id="patient-support-menu"]//button[@data-open-view="devices"]')->length);
    }

    public function test_invalid_settings_unknown_fields_and_hiding_home_are_rejected(): void
    {
        $this->actingAs($this->admin());
        $values = app(MobileAppConfiguration::class)->read()['texts'];
        foreach (['', str_repeat('x', 161), '<script>alert(1)</script>'] as $invalid) {
            $this->put(route('superadmin.mobile-app.update', 'texts'), ['revision' => 0, 'values' => array_replace($values, ['wallet_title' => $invalid])])
                ->assertSessionHasErrors('values.wallet_title');
        }
        $this->put(route('superadmin.mobile-app.update', 'texts'), ['revision' => 0, 'values' => $values + ['unknown' => 'value']])->assertSessionHasErrors('values');
        $navigation = $this->navigationValues();
        $navigation['home']['visible'] = false;
        $this->put(route('superadmin.mobile-app.update', 'navigation'), ['revision' => 0, 'values' => $navigation])->assertSessionHasErrors('values.home.visible');
        $navigation['home']['visible'] = true;
        $navigation['home']['view'] = 'arbitrary';
        $this->put(route('superadmin.mobile-app.update', 'navigation'), ['revision' => 0, 'values' => $navigation])->assertSessionHasErrors('values.home');
        $this->put(route('superadmin.mobile-app.update', 'unknown'), [])->assertNotFound();
        $this->assertSame(0, app(MobileAppConfiguration::class)->read()['revision']);
    }

    public function test_stale_editor_cannot_overwrite_newer_changes(): void
    {
        $this->actingAs($this->admin());
        $values = app(MobileAppConfiguration::class)->read()['texts'];
        $values['wallet_title'] = 'Cambio guardado';
        $this->put(route('superadmin.mobile-app.update', 'texts'), ['revision' => 0, 'values' => $values])->assertSessionHasNoErrors();
        $values['wallet_title'] = 'Cambio desactualizado';
        $this->put(route('superadmin.mobile-app.update', 'texts'), ['revision' => 0, 'values' => $values])->assertSessionHasErrors('revision');
        $this->assertSame('Cambio guardado', app(MobileAppConfiguration::class)->read()['texts']['wallet_title']);
    }

    public function test_user_search_is_scoped_to_klini_and_devices_do_not_expose_push_tokens(): void
    {
        $admin = $this->admin();
        $patient = $this->patient();
        MobileDeviceRegistration::query()->create(['user_id' => $patient->id, 'token_hash' => str_repeat('a', 64), 'push_token' => 'private-push-value', 'platform' => 'android', 'device_name' => 'Teléfono de prueba', 'enabled' => true, 'last_seen_at' => now()]);
        $this->actingAs($admin)->get(route('superadmin.mobile-app', ['section' => 'users', 'q' => 'Móvil']))
            ->assertOk()->assertSee('Paciente Móvil')
            ->assertViewHas('users', fn ($users) => $users->count() === 1 && $users->first()->id === $patient->id);
        $this->get(route('superadmin.mobile-app', ['section' => 'users', 'q' => 'No existe']))->assertSee('No se encontraron usuarios Klini.');
        $this->get(route('superadmin.mobile-app', ['section' => 'notifications']))->assertOk()->assertSee('Teléfono de prueba')->assertDontSee('private-push-value')->assertDontSee(str_repeat('a', 64));
    }

    private function admin(): User
    {
        return User::query()->create(['name' => 'Usuario Operativo', 'username' => 'mobile.admin', 'role' => 'superadmin', 'module' => 'superadmin', 'status' => 'active', 'account_category' => User::CATEGORY_OPERATIONAL]);
    }

    private function patient(): User
    {
        $user = User::query()->create(['name' => 'Paciente Móvil', 'username' => 'mobile.patient', 'role' => 'patient', 'module' => 'patient', 'status' => 'active', 'account_category' => User::CATEGORY_KLINI]);
        Patient::query()->create(['user_id' => $user->id, 'platform_number' => 'MOBILE-001', 'full_name' => $user->name, 'status' => 'active']);
        return $user;
    }

    private function navigationValues(): array
    {
        return collect(app(MobileAppConfiguration::class)->read()['navigation'])->map(fn ($item) => array_intersect_key($item, array_flip(['label', 'visible', 'order'])))->all();
    }
}
