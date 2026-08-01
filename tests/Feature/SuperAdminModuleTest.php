<?php

namespace Tests\Feature;

use App\Models\PlatformModule;
use App\Models\Hospital;
use App\Models\Institution;
use App\Models\InsuranceCarrier;
use App\Models\MedicalDevice;
use App\Models\User;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Provider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_user_can_open_native_dashboard(): void
    {
        $user = User::query()->create([
            'name' => 'Super Admin',
            'username' => 'superadmin.test',
            'email' => 'superadmin@test.local',
            'role' => 'superadmin',
            'module' => 'superadmin',
            'status' => 'active',
        ]);

        PlatformModule::query()->create([
            'key' => 'superadmin',
            'label' => 'Modulo superadministrador',
            'target' => 'superadmin.dashboard',
            'enabled' => true,
            'roles' => ['superadmin'],
            'settings' => ['runtime' => 'native'],
        ]);

        $this->actingAs($user)
            ->get(route('superadmin.dashboard'))
            ->assertOk()
            ->assertSee('Gobierno de modulos')
            ->assertSee('superadmin-native-screen')
            ->assertSee('superadmin-native-sidebar')
            ->assertSee('superadmin-native-module-card')
            ->assertSee(route('superadmin.catalog', 'users'))
            ->assertSee(route('superadmin.catalog', 'institutions'))
            ->assertSee(route('superadmin.catalog', 'modules'))
            ->assertSee('Modulo superadministrador')
            ->assertDontSee('<iframe');
    }

    public function test_superadmin_catalog_menu_sections_open_native_views(): void
    {
        $user = User::query()->create([
            'name' => 'Super Admin',
            'username' => 'superadmin.catalogs',
            'email' => 'superadmin.catalogs@test.local',
            'role' => 'superadmin',
            'module' => 'superadmin',
            'status' => 'active',
        ]);

        PlatformModule::query()->create([
            'key' => 'institution',
            'label' => 'Modulo Institucion',
            'target' => 'institution.dashboard',
            'enabled' => true,
            'roles' => ['superadmin', 'admin', 'institution'],
            'settings' => [
                'owner' => 'Direccion General',
                'description' => 'Catalogo institucional, unidades, servicios, contratos y cobertura.',
            ],
        ]);

        foreach (['users', 'institutions', 'doctors', 'providers', 'hospitals', 'specialties', 'medications', 'patients', 'prescription-format', 'mix-request-format', 'advertising', 'modules', 'access'] as $section) {
            $response = $this->actingAs($user)
                ->get(route('superadmin.catalog', $section))
                ->assertOk()
                ->assertDontSee('<iframe');

            if ($section === 'prescription-format') {
                $response
                    ->assertSee('superadmin-native-prescription-paper')
                    ->assertSee('Formato de Receta Medica')
                    ->assertSee('Medicamentos indicados')
                    ->assertDontSee('Configuracion documental');

                continue;
            }

            if ($section === 'mix-request-format') {
                $response
                    ->assertSee('data-mix-selector', false)
                    ->assertSee('Mezcla oncologica')
                    ->assertSee('Nutricion parenteral')
                    ->assertSee('SOLICITUD DE ONCOLOGICOS')
                    ->assertSee('Ciclofosfamida')
                    ->assertSee('PACIENTE Y UBICACION')
                    ->assertSee('ADMINISTRACION Y MEZCLA')
                    ->assertSee('PRODUCTOS ACTIVOS DEL CATALOGO')
                    ->assertSee('Aminoacidos estandar al 10% g/kg')
                    ->assertSee('Fecha y hora de entrega')
                    ->assertSee('Guardar formato');

                continue;
            }

            if ($section === 'advertising') {
                $response
                    ->assertSee('superadmin-native-advertising-card')
                    ->assertSee('Gestion de banners publicitarios')
                    ->assertSee('Vista previa clon')
                    ->assertDontSee('Publicidad editable');

                continue;
            }

            $response
                ->assertSee('superadmin-native-catalog-card')
                ->assertSee('superadmin-native-catalog-toolbar')
                ->assertSee('superadmin-native-catalog-table');

            if ($section === 'modules') {
                $response
                    ->assertSee('superadmin-native-module-config')
                    ->assertSee('Permisos administrativos')
                    ->assertSee('Guardar configuracion');
            }
        }
    }

    public function test_superadmin_can_disable_module_and_dashboard_registry_hides_it(): void
    {
        $user = User::query()->create([
            'name' => 'Super Admin',
            'username' => 'superadmin.modules',
            'email' => 'superadmin.modules@test.local',
            'role' => 'superadmin',
            'module' => 'superadmin',
            'status' => 'active',
        ]);

        $module = PlatformModule::query()->create([
            'key' => 'doctor',
            'label' => 'Modulo medico',
            'target' => 'doctor.dashboard',
            'enabled' => true,
            'roles' => ['superadmin', 'admin', 'doctor'],
            'settings' => ['runtime' => 'native'],
        ]);

        $this->actingAs($user)
            ->patch(route('superadmin.modules.update', $module), [
                'enabled' => '0',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('platform_modules', [
            'id' => $module->id,
            'enabled' => false,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Modulo medico');

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'platform.module.updated',
            'auditable_id' => $module->id,
        ]);
    }

    public function test_superadmin_can_update_user_status(): void
    {
        $superadmin = User::query()->create([
            'name' => 'Super Admin',
            'username' => 'superadmin.users',
            'email' => 'superadmin.users@test.local',
            'role' => 'superadmin',
            'module' => 'superadmin',
            'status' => 'active',
        ]);

        $user = User::query()->create([
            'name' => 'Usuario Controlado',
            'username' => 'usuario.controlado',
            'email' => 'usuario.controlado@test.local',
            'role' => 'patient',
            'module' => 'patient',
            'status' => 'active',
        ]);

        $this->actingAs($superadmin)
            ->patch(route('superadmin.users.update', $user), [
                'status' => 'suspended',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'status' => 'suspended',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'platform.user.updated',
            'auditable_id' => $user->id,
        ]);
    }

    public function test_superadmin_catalog_filters_and_csv_export_work(): void
    {
        $superadmin = User::query()->create([
            'name' => 'Super Admin',
            'username' => 'superadmin.exports',
            'email' => 'superadmin.exports@test.local',
            'role' => 'superadmin',
            'module' => 'superadmin',
            'status' => 'active',
        ]);

        User::query()->create([
            'name' => 'Paciente Visible',
            'username' => 'paciente.visible',
            'email' => 'paciente.visible@test.local',
            'role' => 'patient',
            'module' => 'patient',
            'status' => 'active',
        ]);

        User::query()->create([
            'name' => 'Medico Oculto',
            'username' => 'medico.oculto',
            'email' => 'medico.oculto@test.local',
            'role' => 'doctor',
            'module' => 'doctor',
            'status' => 'inactive',
        ]);

        $this->actingAs($superadmin)
            ->get(route('superadmin.catalog', ['section' => 'users', 'q' => 'Paciente Visible', 'status' => 'active']))
            ->assertOk()
            ->assertSee('Paciente Visible')
            ->assertDontSee('Medico Oculto');

        $response = $this->actingAs($superadmin)
            ->get(route('superadmin.catalog.csv', ['section' => 'users', 'q' => 'Paciente Visible']))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $csv = $response->streamedContent();
        $this->assertStringContainsString('Paciente Visible', $csv);
        $this->assertStringNotContainsString('Medico Oculto', $csv);
    }

    public function test_superadmin_can_create_institutions_and_private_hospitals(): void
    {
        $superadmin = User::query()->create([
            'name' => 'Super Admin',
            'username' => 'superadmin.create',
            'email' => 'superadmin.create@test.local',
            'role' => 'superadmin',
            'module' => 'superadmin',
            'status' => 'active',
        ]);

        $this->actingAs($superadmin)
            ->post(route('superadmin.institutions.store'), [
                'name' => 'Institucion Nueva',
                'legal_name' => 'Institucion Nueva Legal',
                'external_id' => 'inst-nueva',
                'type' => 'publica',
                'status' => 'active',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('institutions', [
            'name' => 'Institucion Nueva',
            'external_id' => 'inst-nueva',
            'status' => 'active',
        ]);

        $this->actingAs($superadmin)
            ->post(route('superadmin.hospitals.store'), [
                'name' => 'Hospital Privado Nuevo',
                'network_type' => 'Grupo Test',
                'address' => 'Direccion de prueba',
                'contact_phone' => '5555010101',
                'status' => 'active',
                'state' => 'Nuevo Leon',
                'unit_type' => 'Centro de medicina ambulatoria',
                'scope' => 'Cadena privada',
                'source' => 'Alta manual',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('hospitals', [
            'name' => 'Hospital Privado Nuevo',
            'network_type' => 'Grupo Test',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'superadmin.institution.created',
            'auditable_type' => Institution::class,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'superadmin.hospital.created',
            'auditable_type' => Hospital::class,
        ]);
    }

    public function test_superadmin_can_update_module_details_and_section_settings(): void
    {
        $superadmin = User::query()->create([
            'name' => 'Super Admin',
            'username' => 'superadmin.settings',
            'email' => 'superadmin.settings@test.local',
            'role' => 'superadmin',
            'module' => 'superadmin',
            'status' => 'active',
        ]);

        $module = PlatformModule::query()->create([
            'key' => 'doctor',
            'label' => 'Modulo Medico',
            'target' => 'doctor.dashboard',
            'enabled' => true,
            'roles' => ['superadmin', 'doctor'],
            'settings' => [],
        ]);

        $this->actingAs($superadmin)
            ->patch(route('superadmin.modules.details', $module), [
                'label' => 'Modulo Medico Editado',
                'target' => 'doctor.dashboard',
                'status' => 'inactive',
                'owner' => 'Direccion Medica',
                'description' => 'Consulta, agenda y recetas.',
                'permissions' => ['Consultar', 'Reportes'],
                'note' => 'Nota administrativa de prueba.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('platform_modules', [
            'id' => $module->id,
            'label' => 'Modulo Medico Editado',
            'enabled' => false,
        ]);

        $this->actingAs($superadmin)
            ->post(route('superadmin.settings.update', 'advertising'), [
                'module' => 'doctor',
                'primary_title' => 'Consulta Privada',
                'primary_copy' => 'Agenda y paciente.',
                'primary_cta' => 'Ver servicio',
                'secondary_title' => 'Receta digital',
                'secondary_cta' => 'Abrir receta',
            ])
            ->assertRedirect();

        $this->actingAs($superadmin)
            ->post(route('superadmin.settings.update', 'prescription-format'), [
                'folio_prefix' => 'RX-SA',
                'default_service' => 'Consulta externa',
                'diagnosis_required' => '1',
                'medication_required' => '1',
                'footer_note' => 'Formato base.',
            ])
            ->assertRedirect();

        $module->refresh();
        $this->assertSame('Direccion Medica', $module->settings['owner']);
        $this->assertSame(['Consultar', 'Reportes'], $module->settings['permissions']);
        $this->assertSame('Nota administrativa de prueba.', $module->settings['note']);
        $this->assertSame('RX-SA', $module->settings['prescription_format']['folio_prefix']);

        $advertising = PlatformModule::query()->where('key', 'advertising')->first();
        $this->assertSame('Consulta Privada', $advertising->settings['advertising_config']['primary_title']);
    }

    public function test_superadmin_can_create_doctor_patient_and_provider_users(): void
    {
        $superadmin = User::query()->create([
            'name' => 'Super Admin',
            'username' => 'superadmin.people',
            'email' => 'superadmin.people@test.local',
            'role' => 'superadmin',
            'module' => 'superadmin',
            'status' => 'active',
        ]);

        $this->actingAs($superadmin)
            ->post(route('superadmin.doctors.store'), [
                'full_name' => 'Dra. Nueva Demo',
                'username' => 'dra.nueva.demo',
                'email' => 'dra.nueva@test.local',
                'password' => 'Demo2026',
                'professional_license' => 'CED-NEW-001',
                'specialty' => 'Medicina interna',
                'subspecialty' => 'Atencion clinica',
                'service_name' => 'Consulta externa',
                'status' => 'active',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'username' => 'dra.nueva.demo',
            'role' => 'doctor',
            'module' => 'doctor',
        ]);
        $this->assertDatabaseHas('doctors', [
            'full_name' => 'Dra. Nueva Demo',
            'professional_license' => 'CED-NEW-001',
        ]);

        $this->actingAs($superadmin)
            ->post(route('superadmin.patients.store'), [
                'full_name' => 'Paciente Nuevo Demo',
                'first_name' => 'Paciente',
                'last_name' => 'Nuevo Demo',
                'platform_number' => '900000001',
                'username' => 'paciente.nuevo.demo',
                'email' => 'paciente.nuevo@test.local',
                'phone' => '5555000001',
                'status' => 'active',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'username' => 'paciente.nuevo.demo',
            'role' => 'patient',
            'module' => 'patient',
        ]);
        $this->assertDatabaseHas('patients', [
            'full_name' => 'Paciente Nuevo Demo',
            'platform_number' => '900000001',
        ]);

        $this->actingAs($superadmin)
            ->post(route('superadmin.providers.store'), [
                'name' => 'Proveedor Quimio Nuevo',
                'username' => 'proveedor.quimio.nuevo',
                'email' => 'proveedor.quimio@test.local',
                'provider_type' => 'chemotherapy',
                'status' => 'active',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'username' => 'proveedor.quimio.nuevo',
            'role' => 'provider',
            'module' => 'provider_chemo',
        ]);
        $this->assertDatabaseHas('providers', [
            'name' => 'Proveedor Quimio Nuevo',
            'provider_type' => 'chemotherapy',
        ]);
    }

    public function test_superadmin_patient_catalog_matches_source_two_panel_layout(): void
    {
        $superadmin = User::query()->create([
            'name' => 'Super Admin',
            'username' => 'superadmin.patient.layout',
            'email' => 'superadmin.patient.layout@test.local',
            'role' => 'superadmin',
            'module' => 'superadmin',
            'status' => 'active',
        ]);

        $patientUser = User::query()->create([
            'name' => 'Claudia Beatriz Salinas Vega',
            'username' => 'paciente',
            'email' => 'claudia.salinas@test.local',
            'role' => 'patient',
            'module' => 'patient',
            'status' => 'active',
        ]);

        Patient::query()->create([
            'user_id' => $patientUser->id,
            'platform_number' => '100000001',
            'full_name' => 'Claudia Beatriz Salinas Vega',
            'status' => 'active',
        ]);

        Institution::query()->create([
            'name' => 'Imss Bienestar Estado de Mexico',
            'external_id' => 'institution',
            'type' => 'publica',
            'status' => 'active',
        ]);

        $this->actingAs($superadmin)
            ->get(route('superadmin.catalog', 'patients'))
            ->assertOk()
            ->assertSee('Pacientes usuarios de plataforma')
            ->assertSee('Pacientes de unidades por institucion')
            ->assertSee('Claudia Beatriz Salinas Vega')
            ->assertSee('100000001')
            ->assertSee('Ver unidades')
            ->assertDontSee('Nuevo paciente usuario');
    }

    public function test_superadmin_access_catalog_matches_source_destination_actions(): void
    {
        $superadmin = User::query()->create([
            'name' => 'Superadministrador',
            'username' => 'superadmin.access.layout',
            'email' => 'superadmin.access.layout@test.local',
            'role' => 'superadmin',
            'module' => 'superadmin',
            'status' => 'active',
            'is_demo' => true,
        ]);

        User::query()->create([
            'name' => 'Administracion General',
            'username' => 'admin.access.layout',
            'email' => 'admin.access.layout@test.local',
            'role' => 'admin',
            'module' => 'institution',
            'status' => 'active',
            'is_demo' => true,
        ]);

        User::query()->create([
            'name' => 'Centro de Alta Especialidad Regio',
            'username' => 'unidad.access.layout',
            'email' => 'unidad.access.layout@test.local',
            'role' => 'unit',
            'module' => 'unit',
            'status' => 'inactive',
            'is_demo' => true,
            'metadata' => ['external_id' => 'CAE-014'],
        ]);

        $this->actingAs($superadmin)
            ->get(route('superadmin.catalog', 'access'))
            ->assertOk()
            ->assertSee('Modulo de acceso')
            ->assertSee('Gobierno de modulos')
            ->assertSee('Alta de unidades y servicios')
            ->assertSee('CAE-014 - Inactivo')
            ->assertSee('Inactivo')
            ->assertSee('Abrir')
            ->assertDontSee('Guardar</button>', false);
    }

    public function test_superadmin_can_update_people_catalog_statuses(): void
    {
        $superadmin = User::query()->create([
            'name' => 'Super Admin',
            'username' => 'superadmin.people.status',
            'email' => 'superadmin.people.status@test.local',
            'role' => 'superadmin',
            'module' => 'superadmin',
            'status' => 'active',
        ]);

        $doctorUser = User::query()->create([
            'name' => 'Doctor Status',
            'username' => 'doctor.status',
            'email' => 'doctor.status@test.local',
            'role' => 'doctor',
            'module' => 'doctor',
            'status' => 'active',
        ]);
        $doctor = Doctor::query()->create([
            'user_id' => $doctorUser->id,
            'full_name' => 'Doctor Status',
            'status' => 'active',
        ]);

        $patientUser = User::query()->create([
            'name' => 'Paciente Status',
            'username' => 'paciente.status',
            'email' => 'paciente.status@test.local',
            'role' => 'patient',
            'module' => 'patient',
            'status' => 'active',
        ]);
        $patient = Patient::query()->create([
            'user_id' => $patientUser->id,
            'full_name' => 'Paciente Status',
            'status' => 'active',
        ]);

        $providerUser = User::query()->create([
            'name' => 'Proveedor Status',
            'username' => 'proveedor.status',
            'email' => 'proveedor.status@test.local',
            'role' => 'provider',
            'module' => 'provider_npt',
            'status' => 'active',
        ]);
        $provider = Provider::query()->create([
            'user_id' => $providerUser->id,
            'name' => 'Proveedor Status',
            'provider_type' => 'npt',
            'status' => 'active',
        ]);

        $this->actingAs($superadmin)
            ->patch(route('superadmin.doctors.update', $doctor), [
                'full_name' => 'Doctor Status',
                'status' => 'inactive',
            ])
            ->assertRedirect();

        $this->actingAs($superadmin)
            ->patch(route('superadmin.patients.update', $patient), [
                'full_name' => 'Paciente Status',
                'status' => 'inactive',
            ])
            ->assertRedirect();

        $this->actingAs($superadmin)
            ->patch(route('superadmin.providers.update', $provider), [
                'name' => 'Proveedor Status',
                'provider_type' => 'import',
                'status' => 'inactive',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('doctors', ['id' => $doctor->id, 'status' => 'inactive']);
        $this->assertDatabaseHas('patients', ['id' => $patient->id, 'status' => 'inactive']);
        $this->assertDatabaseHas('providers', ['id' => $provider->id, 'provider_type' => 'import', 'status' => 'inactive']);
        $this->assertDatabaseHas('users', ['id' => $doctorUser->id, 'status' => 'inactive']);
        $this->assertDatabaseHas('users', ['id' => $patientUser->id, 'status' => 'inactive']);
        $this->assertDatabaseHas('users', ['id' => $providerUser->id, 'module' => 'provider_import', 'status' => 'inactive']);
    }

    public function test_module_edit_links_open_the_module_catalog_with_the_selected_record(): void
    {
        $superadmin = User::query()->create([
            'name' => 'Super Admin',
            'username' => 'superadmin.module.selection',
            'email' => 'superadmin.module.selection@test.local',
            'role' => 'superadmin',
            'module' => 'superadmin',
            'status' => 'active',
        ]);

        PlatformModule::query()->create([
            'key' => 'institution',
            'label' => 'Modulo Institucion',
            'target' => 'institution.dashboard',
            'enabled' => true,
            'roles' => ['superadmin', 'admin', 'institution'],
            'settings' => ['owner' => 'Direccion General', 'permissions' => ['Consultar']],
        ]);

        $doctor = PlatformModule::query()->create([
            'key' => 'doctor',
            'label' => 'Modulo Medico Seleccionado',
            'target' => 'doctor.dashboard',
            'enabled' => true,
            'roles' => ['superadmin', 'doctor'],
            'settings' => ['owner' => 'Direccion Medica', 'permissions' => ['Reportes']],
        ]);

        $editUrl = route('superadmin.catalog', ['section' => 'modules', 'selected_module' => $doctor->key]).'#module-config';

        $this->actingAs($superadmin)
            ->get(route('superadmin.dashboard'))
            ->assertOk()
            ->assertSee($editUrl, false);

        $this->actingAs($superadmin)
            ->get(route('superadmin.catalog', ['section' => 'modules', 'selected_module' => $doctor->key]))
            ->assertOk()
            ->assertSee('Modulo Medico Seleccionado')
            ->assertSee('Direccion Medica')
            ->assertSee('value="Reportes" checked', false)
            ->assertDontSee('value="Consultar" checked', false);
    }

    public function test_superadmin_can_view_filter_and_export_insurance_carriers(): void
    {
        $superadmin = User::query()->create([
            'name' => 'Super Admin',
            'username' => 'superadmin.insurance.carriers',
            'email' => 'superadmin.insurance.carriers@test.local',
            'role' => 'superadmin',
            'module' => 'superadmin',
            'status' => 'active',
        ]);

        InsuranceCarrier::query()->create([
            'name' => 'AXA Seguros',
            'slug' => 'aseg-axa',
            'type' => 'Aseguradora privada',
            'contact' => 'Convenios medicos',
            'scope' => 'Gastos medicos mayores y atencion hospitalaria',
            'status' => 'active',
        ]);

        $this->actingAs($superadmin)
            ->get(route('superadmin.catalog', ['section' => 'insurance-carriers', 'q' => 'AXA']))
            ->assertOk()
            ->assertSee('Catalogo de Aseguradoras')
            ->assertSee('AXA Seguros')
            ->assertSee('aseg-axa')
            ->assertSee('Convenios medicos');

        $this->actingAs($superadmin)
            ->get(route('superadmin.catalog.csv', ['section' => 'insurance-carriers']))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_superadmin_can_view_search_and_export_insurance_advisors(): void
    {
        $superadmin = User::query()->create([
            'name' => 'Super Admin',
            'username' => 'superadmin.insurance.advisors',
            'email' => 'superadmin.insurance.advisors@test.local',
            'role' => 'superadmin',
            'module' => 'superadmin',
            'status' => 'active',
        ]);

        User::query()->create([
            'name' => 'Laura Martinez',
            'username' => 'asesor.laura',
            'email' => 'laura@seguros.test',
            'role' => 'insurance_advisor',
            'module' => 'insurance_advisor',
            'status' => 'active',
            'metadata' => [
                'insurance_carrier' => 'AXA Seguros',
                'agent_number' => 'AG-2048',
                'phone' => '5555552048',
                'scope' => 'Polizas de gastos medicos mayores',
            ],
        ]);

        $this->actingAs($superadmin)
            ->get(route('superadmin.catalog', ['section' => 'insurance-advisors', 'q' => 'AXA']))
            ->assertOk()
            ->assertSee('Catalogo de Asesores de Seguros')
            ->assertSee('Laura Martinez')
            ->assertSee('asesor.laura')
            ->assertSee('AG-2048');

        $this->actingAs($superadmin)
            ->get(route('superadmin.catalog.csv', ['section' => 'insurance-advisors']))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_superadmin_can_view_search_and_export_medical_devices(): void
    {
        $superadmin = User::query()->create([
            'name' => 'Super Admin',
            'username' => 'superadmin.medical.devices',
            'email' => 'superadmin.medical.devices@test.local',
            'role' => 'superadmin',
            'module' => 'superadmin',
            'status' => 'active',
        ]);

        MedicalDevice::query()->create([
            'name' => 'Oximetro de pulso',
            'code' => 'dm-test-003',
            'category' => 'Signos vitales',
            'manufacturer' => 'Fabricante de prueba',
            'model' => 'OxiTrack Mini',
            'connectivity' => 'Bluetooth',
            'linked_module' => 'Paciente - Seguimiento remoto',
            'recorded_data' => 'SpO2 y frecuencia cardiaca',
            'compatibility' => 'Android',
            'status' => 'active',
        ]);

        $this->actingAs($superadmin)
            ->get(route('superadmin.catalog', ['section' => 'medical-devices', 'q' => 'Bluetooth']))
            ->assertOk()
            ->assertSee('Catalogo de dispositivos medicos')
            ->assertSee('Oximetro de pulso')
            ->assertSee('dm-test-003')
            ->assertSee('Paciente - Seguimiento remoto');

        $this->actingAs($superadmin)
            ->get(route('superadmin.catalog.csv', ['section' => 'medical-devices']))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_superadmin_can_view_and_update_subscription_settings(): void
    {
        $superadmin = User::query()->create([
            'name' => 'Super Admin',
            'username' => 'superadmin.subscriptions',
            'email' => 'superadmin.subscriptions@test.local',
            'role' => 'superadmin',
            'module' => 'superadmin',
            'status' => 'active',
        ]);

        $patientUser = User::query()->create([
            'name' => 'Claudia Beatriz Salinas Vega',
            'username' => 'paciente.suscripcion',
            'email' => 'claudia.subscriptions@test.local',
            'role' => 'patient',
            'module' => 'patient',
            'status' => 'active',
        ]);
        Patient::query()->create([
            'user_id' => $patientUser->id,
            'full_name' => 'Claudia Beatriz Salinas Vega',
            'platform_number' => '100000001',
            'status' => 'active',
            'metadata' => ['subscription_plan_id' => 'basic', 'country' => 'Mexico'],
        ]);

        $this->actingAs($superadmin)
            ->get(route('superadmin.catalog', 'subscriptions'))
            ->assertOk()
            ->assertSee('Plan Historial Basico')
            ->assertSee('Plan Salud Premium 360')
            ->assertSee('href="#subscription-price-basic"', false)
            ->assertSee('id="subscription-price-basic"', false)
            ->assertSee('Editar precio - Plan Historial Basico')
            ->assertSee('Guardar precio')
            ->assertSee('href="#subscription-name-basic"', false)
            ->assertSee('id="subscription-name-basic"', false)
            ->assertSee('href="#subscription-description-basic"', false)
            ->assertSee('id="subscription-description-basic"', false)
            ->assertSee('Editar descripcion - Plan Historial Basico')
            ->assertSee('Guardar descripcion')
            ->assertSee('href="#subscription-long-description-basic"', false)
            ->assertSee('id="subscription-long-description-basic"', false)
            ->assertSee('Editar descripcion amplia - Plan Historial Basico')
            ->assertSee('Esta descripcion se usara como detalle funcional del plan.')
            ->assertSee('data-legal-editor', false)
            ->assertSee('data-edit-legal', false)
            ->assertSee('data-cancel-legal', false)
            ->assertSee('Guardar cambios')
            ->assertSee('id="subscription-legal-form-usage_policies"', false)
            ->assertSee('id="subscription-legal-form-terms_conditions"', false)
            ->assertSee('Editar nombre - Plan Historial Basico')
            ->assertSee('Guardar nombre')
            ->assertSee('data-show-subscription-plan="basic"', false)
            ->assertSee('data-subscription-plan-panel="basic"', false)
            ->assertSee('Usuarios con Plan Historial Basico')
            ->assertSee('Claudia')
            ->assertSee('100000001')
            ->assertSee('data-back-to-subscriptions', false)
            ->assertSee('Propuesta de Politicas de uso')
            ->assertSee('Propuesta de Terminos y condiciones');

        $this->actingAs($superadmin)
            ->post(route('superadmin.settings.update', 'subscriptions'), [
                'mode' => 'plan',
                'plan_id' => 'complete',
                'type' => 'Suscripcion 1',
                'name' => 'Plan Historial Completo',
                'price' => '$25 MXN mensuales',
                'users_included' => '1 usuario',
                'short_description' => 'Acceso permanente al historial clinico.',
                'long_description' => 'Acceso completo y permanente a toda la informacion clinica digital.',
                'status' => 'active',
            ])
            ->assertRedirect();

        $this->actingAs($superadmin)
            ->get(route('superadmin.catalog', 'subscriptions'))
            ->assertOk()
            ->assertSee('$25 MXN mensuales');

        $this->actingAs($superadmin)
            ->post(route('superadmin.settings.update', 'subscriptions'), [
                'mode' => 'plan',
                'plan_id' => 'basic',
                'type' => 'Gratis',
                'name' => 'Plan Historial Inicial',
                'price' => 'Gratis',
                'users_included' => '1 usuario',
                'short_description' => 'Informacion medica reciente.',
                'long_description' => 'Historial medico inicial para pacientes de la plataforma.',
                'status' => 'active',
            ])
            ->assertRedirect();

        $this->actingAs($superadmin)
            ->get(route('superadmin.catalog', 'subscriptions'))
            ->assertOk()
            ->assertSee('Plan Historial Inicial');

        $this->actingAs($superadmin)
            ->get(route('superadmin.subscriptions.users.csv', 'basic'))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }
}
