<?php

namespace Tests\Feature;

use App\Models\{InsuranceCarrier, InsurancePlan, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InsuranceCarrierCatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::query()->create([
            'name' => 'Administrador catálogo', 'username' => 'admin.carriers',
            'email' => 'admin@carriers.test', 'role' => 'superadmin',
            'module' => 'superadmin', 'status' => 'active',
        ]));
    }

    private function carrier(string $name, string $status = 'active'): InsuranceCarrier
    {
        return InsuranceCarrier::query()->create([
            'name' => $name, 'slug' => str($name)->slug(), 'status' => $status,
            'type' => 'GMM', 'contact' => 'Contacto de prueba', 'phone' => '5551234567',
            'email' => 'contacto@carriers.test', 'scope' => 'Nacional',
        ]);
    }

    private function plan(InsuranceCarrier $carrier, string $name, string $category, string $status = 'active'): InsurancePlan
    {
        return $carrier->plans()->create(compact('name', 'category', 'status'));
    }

    private function url(array $filters = [], string $route = 'superadmin.catalog'): string
    {
        return route($route, ['section' => 'insurance-carriers', 'view' => 'carriers'] + $filters);
    }

    public function test_all_active_and_inactive_views_show_real_plan_counts_and_matching_actions(): void
    {
        $a = $this->carrier('Aseguradora A');
        $b = $this->carrier('Aseguradora B');
        $c = $this->carrier('Aseguradora C', 'inactive');
        foreach ([[$a, 'gmm'], [$a, 'gmm'], [$a, 'life'], [$b, 'gmm'], [$b, 'life'], [$b, 'life'], [$c, 'gmm']] as $i => [$carrier, $category]) {
            $this->plan($carrier, 'Plan '.$i, $category, $i === 1 ? 'inactive' : 'active');
        }
        foreach (['all' => [$a, $b, $c], 'active' => [$a, $b], 'inactive' => [$c]] as $status => $expected) {
            $response = $this->get($this->url(['status' => $status]))->assertOk();
            $response->assertViewHas('records', fn ($records) => $records->pluck('id')->all() === array_column($expected, 'id'));
            $response->assertSeeInOrder(['Tipo de seguro', 'Contacto', 'Teléfono', 'Correo', 'Alcance', 'GMM', 'Vida', 'Estado', 'Acciones']);
            $dom = new \DOMDocument;
            @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$response->getContent());
            $xpath = new \DOMXPath($dom);
            $this->assertSame(1, $xpath->query('//a[@data-admin-filter="'.$status.'" and @aria-current="true"]')->length);
            $this->assertSame(0, $xpath->query('//a[@data-admin-filter="carriers"]')->length);
            foreach ($expected as $carrier) {
                $row = '//tr[@data-insurance-carrier-id="'.$carrier->id.'"]';
                $this->assertSame($carrier->status === 'active' ? 'Desactivar' : 'Activar', $xpath->query($row.'//button')->item(0)->textContent);
                foreach (['gmm' => 7, 'life' => 8] as $category => $column) {
                    $count = $carrier->plans()->where('category', $category)->count();
                    $link = $xpath->query($row.'/td['.$column.']/a')->item(0);
                    $this->assertSame($count.($count === 1 ? ' plan' : ' planes'), $link->textContent);
                    parse_str(parse_url($link->getAttribute('href'), PHP_URL_QUERY), $query);
                    $this->assertEquals(['view' => $category, 'carrier' => $carrier->id, 'status' => 'all'], $query);
                }
            }
        }
        $csv = $this->get($this->url(['status' => 'inactive'], 'superadmin.catalog.csv'))->assertOk()->streamedContent();
        $rows = array_map('str_getcsv', array_filter(explode("\n", trim($csv))));
        $this->assertCount(2, $rows);
        $this->assertSame(['Aseguradora C', 'GMM'], array_slice($rows[1], 0, 2));
        $this->assertSame(['1', '0', 'Inactivo'], array_slice($rows[1], 6, 3));
    }

    public function test_plan_links_filter_by_carrier_and_category_including_export_and_pagination(): void
    {
        $a = $this->carrier('Primera aseguradora');
        $b = $this->carrier('Segunda aseguradora');
        $this->plan($b, 'Plan ajeno', 'gmm');
        for ($i = 0; $i < 11; $i++) $this->plan($a, 'GMM '.$i, 'gmm', $i === 0 ? 'inactive' : 'active');
        $life = $this->plan($a, 'Vida propia', 'life');
        $filters = ['section' => 'insurance-carriers', 'view' => 'gmm', 'carrier' => $a->id, 'status' => 'all'];
        $response = $this->get(route('superadmin.catalog', $filters))->assertOk()->assertDontSee('Plan ajeno')->assertDontSee('Vida propia');
        $response->assertViewHas('records', fn ($records) => $records->total() === 11 && $records->count() === 10);
        $response->assertSee('carrier='.$a->id.'&amp;status=all&amp;page=2', false);
        $response->assertSee('view=life&amp;carrier='.$a->id.'&amp;status=all', false);
        $csv = $this->get(route('superadmin.catalog.csv', $filters))->assertOk()->streamedContent();
        $this->assertStringContainsString('GMM 0', $csv);
        $this->assertStringContainsString('GMM 10', $csv);
        $this->assertStringNotContainsString('Plan ajeno', $csv);
        $this->assertStringNotContainsString('Vida propia', $csv);
        $this->get(route('superadmin.catalog', array_replace($filters, ['view' => 'life'])))->assertOk()
            ->assertViewHas('records', fn ($records) => $records->pluck('id')->all() === [$life->id]);
    }

    public function test_status_action_preserves_contact_information_and_moves_carrier_between_filters(): void
    {
        $carrier = $this->carrier('Aseguradora con contacto');
        foreach (['inactive', 'active'] as $nextStatus) {
            $response = $this->get($this->url(['status' => $carrier->status]))->assertOk();
            $dom = new \DOMDocument;
            @$dom->loadHTML($response->getContent());
            $xpath = new \DOMXPath($dom);
            $data = [];
            foreach ($xpath->query('//tr[@data-insurance-carrier-id="'.$carrier->id.'"]//form/input') as $input) {
                $data[$input->getAttribute('name')] = $input->getAttribute('value');
            }
            $this->patch(route('superadmin.insurance-carriers.update', $carrier), $data)->assertRedirect()->assertSessionHasNoErrors();
            $carrier->refresh();
            $this->assertSame($nextStatus, $carrier->status);
            $this->assertSame('5551234567', $carrier->phone);
            $this->assertSame('contacto@carriers.test', $carrier->email);
            $this->assertSame('Nacional', $carrier->scope);
            $this->assertSame('Contacto de prueba', $carrier->contact);
            $this->get($this->url(['status' => $nextStatus]))->assertSee('data-insurance-carrier-id="'.$carrier->id.'"', false);
            $this->get($this->url(['status' => $nextStatus === 'active' ? 'inactive' : 'active']))->assertDontSee('data-insurance-carrier-id="'.$carrier->id.'"', false);
        }
    }

    public function test_empty_catalog_keeps_all_columns_and_create_form(): void
    {
        $this->get($this->url(['status' => 'inactive']))->assertOk()
            ->assertSee('No hay aseguradoras con estos filtros.')
            ->assertSee('admin-insurance-carrier-table')->assertSee('Tipo de seguro')
            ->assertSee('GMM')->assertSee('Vida')->assertSee('Estado')
            ->assertSee('#create-insurance-carrier', false);
    }
}
