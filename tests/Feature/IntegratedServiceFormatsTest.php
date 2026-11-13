<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\ServiceFormat;
use App\Models\User;
use Database\Seeders\IntegratedServiceFormatsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IntegratedServiceFormatsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create(['name' => 'Admin formatos', 'username' => 'service-formats', 'role' => 'superadmin', 'module' => 'superadmin', 'status' => 'active']);
    }

    public function test_each_service_lists_only_its_formats_and_supports_multiple_and_empty_lists(): void
    {
        $first = Service::create(['name' => 'Servicio A', 'status' => 'active']);
        $second = Service::create(['name' => 'Servicio B', 'status' => 'active']);
        $empty = Service::create(['name' => 'Servicio C', 'status' => 'active']);
        $first->formats()->create(['key' => 'request', 'name' => 'Solicitud A', 'purpose' => 'request']);
        $first->formats()->create(['key' => 'execution', 'name' => 'Ejecución A', 'purpose' => 'execution']);
        $second->formats()->create(['key' => 'operation', 'name' => '<script>alert(1)</script>', 'purpose' => 'operation']);

        $response = $this->actingAs($this->admin())->get(route('superadmin.catalog', 'services'))
            ->assertOk()->assertSee('Formatos')->assertSee('Ver formatos de Servicio A')
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
        $document = new \DOMDocument;
        @$document->loadHTML('<?xml encoding="utf-8" ?>'.$response->getContent());
        $xpath = new \DOMXPath($document);
        $firstTemplate = $xpath->query('//template[@id="service-formats-'.$first->id.'"]')->item(0);
        $this->assertSame(2, $xpath->query('.//li', $firstTemplate)->length);
        $this->assertStringContainsString('Solicitud A', $firstTemplate->textContent);
        $this->assertStringContainsString('Ejecución A', $firstTemplate->textContent);
        $this->assertStringNotContainsString('alert(1)', $firstTemplate->textContent);
        $emptyTemplate = $xpath->query('//template[@id="service-formats-'.$empty->id.'"]')->item(0);
        $this->assertStringContainsString('Este servicio aún no tiene formatos asociados.', $emptyTemplate->textContent);
        $this->assertSame(3, $xpath->query('//button[@data-service-formats]')->length);

        $csv = $this->get(route('superadmin.catalog.csv', 'services'))->assertOk()->streamedContent();
        $this->assertStringContainsString('Formatos', $csv);
        $this->assertStringContainsString('Ejecución A; Solicitud A', $csv);
    }

    public function test_import_associates_existing_forms_without_duplicates_or_overwriting_captured_names(): void
    {
        $central = Service::create(['name' => 'Central de mezclas']);
        $npt = Service::create(['name' => 'Nutrición Parenteral']);
        $unknown = Service::create(['name' => 'Servicio sin formato']);
        $central->formats()->create(['key' => 'npt-request', 'name' => 'Nombre personalizado', 'purpose' => 'request']);
        $this->seed(IntegratedServiceFormatsSeeder::class);
        $this->seed(IntegratedServiceFormatsSeeder::class);
        $this->assertSame(2, $central->formats()->count());
        $this->assertSame(1, $npt->formats()->count());
        $this->assertSame(0, $unknown->formats()->count());
        $this->assertSame('Nombre personalizado', $central->formats()->where('key', 'npt-request')->firstOrFail()->name);
        foreach (ServiceFormat::whereNotNull('source_view')->get() as $format) {
            $this->assertTrue(view()->exists($format->source_view));
        }
    }

    public function test_non_admin_cannot_read_or_export_service_formats(): void
    {
        $user = User::create(['name' => 'Proveedor', 'username' => 'formats-provider', 'role' => 'provider', 'status' => 'active']);
        $this->actingAs($user)->get(route('superadmin.catalog', 'services'))->assertForbidden();
        $this->get(route('superadmin.catalog.csv', 'services'))->assertForbidden();
    }

    public function test_formats_open_from_their_services_with_the_correct_mixture_and_services_navigation(): void
    {
        $npt = Service::create(['name' => 'Nutrición Parenteral', 'status' => 'active']);
        $oncology = Service::create(['name' => 'Quimioterapia', 'status' => 'active']);
        $outpatient = Service::create(['name' => 'Consulta externa', 'status' => 'active']);
        $this->seed(IntegratedServiceFormatsSeeder::class);
        $this->actingAs($this->admin());

        $catalog = $this->get(route('superadmin.catalog', 'services'))->assertOk();
        $catalog->assertDontSee('href="'.route('superadmin.catalog', 'prescription-format').'"', false)
            ->assertDontSee('href="'.route('superadmin.catalog', 'mix-request-format').'"', false);

        foreach ([
            [$npt, 'npt-request', 'PACIENTE Y UBICACION', 'SOLICITUD DE ONCOLOGICOS'],
            [$oncology, 'oncology-request', 'SOLICITUD DE ONCOLOGICOS', 'PACIENTE Y UBICACION'],
            [$outpatient, 'prescription', 'Medicamentos indicados', 'data-mix-panel'],
        ] as [$service, $key, $expected, $unexpected]) {
            $format = $service->formats()->where('key', $key)->firstOrFail();
            $url = route('superadmin.services.formats.show', ['service' => $service, 'format' => $format]);
            $document = new \DOMDocument;
            @$document->loadHTML('<?xml encoding="utf-8" ?>'.$catalog->getContent());
            $xpath = new \DOMXPath($document);
            $this->assertSame(1, $xpath->query('//template[@id="service-formats-'.$service->id.'"]//a[@href="'.$url.'"]')->length);

            $response = $this->get($url)->assertOk()->assertSee($expected)->assertDontSee($unexpected)
                ->assertSee('Servicios integrales · '.$service->name)->assertSee('Volver a servicios integrales');
            @$document->loadHTML('<?xml encoding="utf-8" ?>'.$response->getContent());
            $xpath = new \DOMXPath($document);
            $this->assertSame(1, $xpath->query('//aside//a[@class="is-active" and @href="'.route('superadmin.catalog', 'services').'"]')->length);
            if ($key !== 'prescription') {
                $response->assertDontSee('Todos los formatos');
                $this->assertSame(1, $xpath->query('//*[@data-mix-panel]')->length);
                $this->assertSame(1, $xpath->query('//button[@data-admin-filter]')->length);
            }
        }
    }

    public function test_format_access_requires_admin_its_own_service_and_an_implemented_format(): void
    {
        $service = Service::create(['name' => 'Nutrición Parenteral']);
        $other = Service::create(['name' => 'Otro servicio']);
        $format = $service->formats()->create(['key' => 'npt-request', 'name' => 'NPT', 'source_view' => 'untrusted.template']);
        $unsupported = $service->formats()->create(['key' => 'custom', 'name' => 'Otro formato', 'source_view' => 'superadmin._mix_request_format']);
        $url = route('superadmin.services.formats.show', ['service' => $service, 'format' => $format]);
        $this->get($url)->assertRedirect(route('login'));
        $user = User::create(['name' => 'Proveedor', 'username' => 'service-formats-other', 'role' => 'provider', 'status' => 'active']);
        $this->actingAs($user)->get($url)->assertForbidden();
        $this->actingAs($this->admin())->get($url)->assertOk()->assertSee('PACIENTE Y UBICACION');
        $this->get(route('superadmin.services.formats.show', ['service' => $other, 'format' => $format]))->assertNotFound();
        $this->get(route('superadmin.services.formats.show', ['service' => $service, 'format' => $unsupported]))->assertNotFound();
    }
}
