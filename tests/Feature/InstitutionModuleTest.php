<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\ContractedService;
use App\Models\Doctor;
use App\Models\Institution;
use App\Models\InventoryItem;
use App\Models\MedicalUnit;
use App\Models\MedicationCatalogItem;
use App\Models\OperationalArea;
use App\Models\OperationalProfile;
use App\Models\Patient;
use App\Models\PharmacyProduct;
use App\Models\Provider;
use App\Models\ProviderRequest;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstitutionModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_institution_user_can_open_native_dashboard(): void
    {
        $user = User::query()->create([
            'name' => 'Institucion Test',
            'username' => 'institucion.test',
            'email' => 'institucion@test.local',
            'role' => 'institution',
            'module' => 'institution',
            'status' => 'active',
        ]);

        $institution = Institution::query()->create([
            'owner_user_id' => $user->id,
            'external_id' => 'inst-test',
            'name' => 'Institucion Nativa Test',
            'status' => 'active',
        ]);

        $unit = MedicalUnit::query()->create([
            'institution_id' => $institution->id,
            'name' => 'Unidad Institucional',
            'code' => 'UI',
            'beds' => 40,
            'status' => 'active',
        ]);

        $doctor = Doctor::query()->create([
            'medical_unit_id' => $unit->id,
            'full_name' => 'Dr Institucional',
            'status' => 'active',
        ]);

        $patient = Patient::query()->create([
            'full_name' => 'Paciente Institucional',
            'status' => 'active',
        ]);

        Appointment::query()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'medical_unit_id' => $unit->id,
            'status' => 'scheduled',
            'starts_at' => now()->addDay(),
        ]);

        $service = Service::query()->create([
            'name' => 'Servicio Institucional',
            'status' => 'active',
        ]);

        ContractedService::query()->create([
            'institution_id' => $institution->id,
            'medical_unit_id' => $unit->id,
            'service_id' => $service->id,
            'contract_number' => 'INST-001',
            'status' => 'active',
        ]);

        $area = OperationalArea::query()->create([
            'key' => 'area-inst',
            'label' => 'Area Institucional',
        ]);

        OperationalProfile::query()->create([
            'user_id' => $user->id,
            'medical_unit_id' => $unit->id,
            'operational_area_id' => $area->id,
            'status' => 'active',
        ]);

        MedicationCatalogItem::query()->create([
            'institution_id' => $institution->id,
            'name' => 'Catalogo Institucional',
            'status' => 'active',
        ]);

        $product = PharmacyProduct::query()->create([
            'name' => 'Producto Institucional',
            'price' => 80,
            'status' => 'active',
        ]);

        InventoryItem::query()->create([
            'pharmacy_product_id' => $product->id,
            'medical_unit_id' => $unit->id,
            'quantity' => 25,
            'status' => 'available',
        ]);

        $provider = Provider::query()->create([
            'name' => 'Proveedor Institucional',
            'provider_type' => 'npt',
            'status' => 'active',
        ]);

        ProviderRequest::query()->create([
            'provider_id' => $provider->id,
            'patient_id' => $patient->id,
            'medical_unit_id' => $unit->id,
            'external_id' => 'REQ-INST-001',
            'request_type' => 'npt',
            'status' => 'requested',
            'requested_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('institution.dashboard'))
            ->assertOk()
            ->assertSee('institution-native-screen')
            ->assertSee('institution-native-sidebar')
            ->assertSee('institution-native-table')
            ->assertSee('Institucion Nativa Test')
            ->assertSee('Unidad Institucional')
            ->assertSee('Catalogo de unidades')
            ->assertDontSee('<iframe');

        $this->actingAs($user)
            ->get(route('institution.dashboard', ['section' => 'subunits']))
            ->assertOk()
            ->assertSee('Catalogo de subunidades')
            ->assertSee('Catalogo de consultorios')
            ->assertSee('Catalogo de salas de infusion')
            ->assertSee('Catalogo de quirofanos')
            ->assertSee('Catalogo de salas de recuperacion')
            ->assertSee('Unidad Institucional')
            ->assertSee('Descargar Excel')
            ->assertSee('data-toggle-subunits', false);

        $this->actingAs($user)
            ->get(route('institution.dashboard', ['section' => 'pharmacies']))
            ->assertOk()
            ->assertSee('Catalogo de farmacias institucionales')
            ->assertSee('Farmacias institucionales')
            ->assertSee('Farmacia institucional Unidad Institucional')
            ->assertSee('Unidad Institucional');

        $this->actingAs($user)
            ->get(route('institution.dashboard', ['section' => 'services']))
            ->assertOk()
            ->assertSee('Catalogo de servicios')
            ->assertSee('Servicio Institucional')
            ->assertSee('Habilitar Servicio a Unidades')
            ->assertSee('Informacion del contrato')
            ->assertSee('data-service-edit', false)
            ->assertSee('data-service-contract', false)
            ->assertSee('Datos generales del servicio')
            ->assertSee('Habilitar a unidades')
            ->assertSee('data-service-tab="units"', false)
            ->assertSee('data-service-tab="data"', false)
            ->assertSee('Condiciones particulares')
            ->assertSee('Seleccionar visibles')
            ->assertSee('Guardar unidades')
            ->assertSee('Editar datos')
            ->assertSee('Contrato del servicio')
            ->assertSee('Guardar resumen')
            ->assertSee('Unidad Institucional');

        $this->actingAs($user)
            ->get(route('institution.dashboard', ['section' => 'medications']))
            ->assertOk()
            ->assertSee('Catalogo institucional de medicamentos')
            ->assertSee('Medicamentos institucionales')
            ->assertSee('CATALOGO INSTITUCIONAL')
            ->assertSee('Nuevo medicamento')
            ->assertSee('Buscar clave')
            ->assertSee('CSV');

        $this->actingAs($user)
            ->get(route('institution.dashboard', ['section' => 'specialties']))
            ->assertOk()
            ->assertSee('Catalogo institucional de especialidades')
            ->assertSee('Especialidades institucionales')
            ->assertSee('Servicio Institucional')
            ->assertSee('Nueva Especialidad')
            ->assertSee('Buscar especialidad')
            ->assertSee('Editar')
            ->assertSee('Eliminar');

        $this->actingAs($user)
            ->get(route('institution.dashboard', ['section' => 'create-unit']))
            ->assertOk()
            ->assertSee('Alta de unidad')
            ->assertSee('Nueva unidad')
            ->assertSee('CLUES')
            ->assertSee('Usuario de unidad')
            ->assertSee('Guardar unidad');

        $this->actingAs($user)
            ->get(route('institution.dashboard', ['section' => 'create-service']))
            ->assertOk()
            ->assertSee('Alta de servicios')
            ->assertSee('Nuevo servicio')
            ->assertSee('Unidades con servicio contratado')
            ->assertSee('Datos del Servicio')
            ->assertSee('Guardar servicio');

        $this->actingAs($user)
            ->post(route('institution.services.store'), [
                'unit_ids' => [$unit->id],
                'category' => 'Asistenciales',
                'specialty' => 'Nefrologia',
                'name' => 'Hemodialisis Institucional',
                'starts_at' => '2026-07-20',
                'ends_at' => '2027-07-20',
                'sla' => '24 h',
                'form_context' => 'service',
            ])
            ->assertRedirect(route('institution.dashboard', ['institution' => $institution->id, 'section' => 'services']));

        $createdService = Service::query()->where('name', 'Hemodialisis Institucional')->firstOrFail();
        $this->assertDatabaseHas('services', [
            'id' => $createdService->id,
            'category' => 'Asistenciales',
            'specialty' => 'Nefrologia',
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('contracted_services', [
            'institution_id' => $institution->id,
            'medical_unit_id' => $unit->id,
            'service_id' => $createdService->id,
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->post(route('institution.units.store'), [
                'clues' => 'UTM-001',
                'name' => 'Unidad Test Morelos',
                'entity' => 'Morelos',
                'municipality' => 'Cuernavaca',
                'care_level' => 'Segundo Nivel',
                'typology' => 'Hospital general',
                'address' => 'Calle Institucional 100',
                'latitude' => '18.9218',
                'longitude' => '-99.2340',
                'partida' => 'Partida 1',
                'subpartida' => 'Subpartida 1',
                'beds' => 20,
                'unit_username' => 'unidad.test.morelos',
                'unit_password' => 'Demo2026',
                'form_context' => 'unit',
            ])
            ->assertRedirect(route('institution.dashboard', ['institution' => $institution->id]));

        $this->assertDatabaseHas('medical_units', [
            'institution_id' => $institution->id,
            'clues' => 'UTM-001',
            'name' => 'Unidad Test Morelos',
            'unit_username' => 'unidad.test.morelos',
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('users', [
            'username' => 'unidad.test.morelos',
            'role' => 'unit',
            'module' => 'unit',
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->post(route('institution.specialties.store'), [
                'name' => 'Especialidad Institucional Nueva',
                'status' => 'active',
                'form_context' => 'specialty',
            ])
            ->assertRedirect(route('institution.dashboard', ['institution' => $institution->id, 'section' => 'specialties']));

        $this->assertDatabaseHas('services', [
            'name' => 'Especialidad Institucional Nueva',
            'specialty' => 'Especialidad Institucional Nueva',
            'category' => 'Especialidades',
            'status' => 'active',
        ]);

        $createdSpecialty = Service::query()->where('name', 'Especialidad Institucional Nueva')->firstOrFail();
        $this->actingAs($user)
            ->patch(route('institution.specialties.update', $createdSpecialty), [
                'name' => 'Especialidad Institucional Editada',
                'status' => 'inactive',
                'form_context' => 'specialty',
                'form_mode' => 'edit',
            ])
            ->assertRedirect(route('institution.dashboard', ['institution' => $institution->id, 'section' => 'specialties']));

        $this->assertDatabaseHas('services', [
            'id' => $createdSpecialty->id,
            'name' => 'Especialidad Institucional Editada',
            'specialty' => 'Especialidad Institucional Editada',
            'status' => 'inactive',
        ]);

        $this->actingAs($user)
            ->patch(route('institution.specialties.status', $createdSpecialty), ['status' => 'active'])
            ->assertRedirect(route('institution.dashboard', ['institution' => $institution->id, 'section' => 'specialties']));

        $this->assertDatabaseHas('services', [
            'id' => $createdSpecialty->id,
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->delete(route('institution.specialties.destroy', $createdSpecialty))
            ->assertRedirect(route('institution.dashboard', ['institution' => $institution->id, 'section' => 'specialties']));

        $this->assertDatabaseMissing('services', ['id' => $createdSpecialty->id]);

        $this->actingAs($user)
            ->post(route('institution.medications.store'), [
                'cnis' => '010.000.9999.00',
                'therapeutic_group' => 'Grupo prueba institucional',
                'name' => 'Medicamento Institucional Nuevo',
                'description' => 'Descripcion del medicamento institucional nuevo',
                'mobile_units' => 'no',
                'basic_units' => 'yes',
                'cessa' => 'undefined',
                'status' => 'active',
            ])
            ->assertRedirect(route('institution.dashboard', ['institution' => $institution->id, 'section' => 'medications']));

        $this->assertDatabaseHas('medication_catalog_items', [
            'institution_id' => $institution->id,
            'cnis' => '010.000.9999.00',
            'name' => 'Medicamento Institucional Nuevo',
            'status' => 'active',
        ]);

        $createdMedication = MedicationCatalogItem::query()->where('cnis', '010.000.9999.00')->firstOrFail();
        $this->actingAs($user)
            ->patch(route('institution.medications.update', $createdMedication), [
                'cnis' => '010.000.9999.01',
                'therapeutic_group' => 'Grupo actualizado',
                'name' => 'Medicamento Institucional Editado',
                'description' => 'Descripcion institucional actualizada',
                'mobile_units' => 'yes',
                'basic_units' => 'no',
                'cessa' => 'yes',
                'status' => 'inactive',
            ])
            ->assertRedirect(route('institution.dashboard', ['institution' => $institution->id, 'section' => 'medications']));

        $this->assertDatabaseHas('medication_catalog_items', [
            'id' => $createdMedication->id,
            'institution_id' => $institution->id,
            'cnis' => '010.000.9999.01',
            'name' => 'Medicamento Institucional Editado',
            'status' => 'inactive',
        ]);

        $this->actingAs($user)
            ->post(route('institution.services.units.sync', $service), [])
            ->assertRedirect(route('institution.dashboard', ['institution' => $institution->id, 'section' => 'services']));

        $this->assertDatabaseHas('contracted_services', [
            'institution_id' => $institution->id,
            'medical_unit_id' => $unit->id,
            'service_id' => $service->id,
            'status' => 'inactive',
        ]);

        $this->actingAs($user)
            ->post(route('institution.services.units.sync', $service), ['unit_ids' => [$unit->id]])
            ->assertRedirect(route('institution.dashboard', ['institution' => $institution->id, 'section' => 'services']));

        $this->assertDatabaseHas('contracted_services', [
            'institution_id' => $institution->id,
            'medical_unit_id' => $unit->id,
            'service_id' => $service->id,
            'status' => 'active',
        ]);
    }

    public function test_institution_user_can_update_unit_status(): void
    {
        $user = User::query()->create([
            'name' => 'Institucion Status',
            'username' => 'institucion.status',
            'email' => 'institucion.status@test.local',
            'role' => 'institution',
            'module' => 'institution',
            'status' => 'active',
        ]);

        $institution = Institution::query()->create([
            'owner_user_id' => $user->id,
            'name' => 'Institucion Status',
            'status' => 'active',
        ]);

        $unit = MedicalUnit::query()->create([
            'institution_id' => $institution->id,
            'name' => 'Unidad Status',
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->patch(route('institution.units.status', $unit), [
                'status' => 'maintenance',
                'notes' => 'Revision programada',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('medical_units', [
            'id' => $unit->id,
            'status' => 'maintenance',
        ]);

        $this->assertSame(
            'Revision programada',
            $unit->fresh()->metadata['last_status_note'] ?? null,
        );
    }
}
