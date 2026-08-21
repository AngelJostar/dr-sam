<?php

namespace Tests\Feature;

use App\Services\Integrations\Cbta\CbtaCatalogClient;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class CbtaCatalogClientTest extends TestCase
{
    public function test_it_reads_the_npt_catalog_using_the_service_token(): void
    {
        config()->set('cbta.base_url', 'https://cbta.test');
        config()->set('cbta.token', 'service-token');

        Http::fake([
            'https://cbta.test/api/internal/v1/medical-units/DRSAM-DEMO/catalogs/npt' => Http::response([
                'data' => [
                    'medical_unit' => ['external_code' => 'DRSAM-DEMO', 'name' => 'Hospital Demo'],
                    'catalog_type' => 'npt',
                    'catalog_version' => '2026-08-05T12:00:00-06:00',
                    'items' => [['product_code' => 'NPT-CAT-000001']],
                ],
            ]),
        ]);

        $catalog = app(CbtaCatalogClient::class)->nptCatalog('DRSAM-DEMO');

        $this->assertSame('npt', $catalog['catalog_type']);
        $this->assertSame('NPT-CAT-000001', $catalog['items'][0]['product_code']);
        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer service-token'));
    }

    public function test_it_rejects_an_invalid_catalog_contract(): void
    {
        config()->set('cbta.base_url', 'https://cbta.test');
        config()->set('cbta.token', 'service-token');
        Http::fake(['*' => Http::response(['data' => ['items' => []]])]);

        $this->expectException(RuntimeException::class);

        app(CbtaCatalogClient::class)->oncologyCatalog('DRSAM-DEMO');
    }

    public function test_it_prevalidates_a_mixture_without_creating_it(): void
    {
        config()->set('cbta.base_url', 'https://cbta.test');
        config()->set('cbta.token', 'service-token');

        Http::fake([
            'https://cbta.test/api/internal/v1/mixture-requests/prevalidate' => Http::response([
                'data' => [
                    'valid' => true,
                    'catalog_version' => '2026-08-05T12:00:00-06:00',
                    'items' => [[
                        'product_code' => 'NPT-CAT-000001',
                        'presentation_code' => 'NPT-PRES-000001',
                        'valid' => true,
                    ]],
                    'errors' => [],
                ],
                'meta' => ['mutated_inventory' => false, 'created_request' => false],
            ]),
        ]);

        $result = app(CbtaCatalogClient::class)->prevalidateMixture([
            'medical_unit_code' => 'HOSP-000001',
            'catalog_type' => 'npt',
            'items' => [[
                'product_code' => 'NPT-CAT-000001',
                'presentation_code' => 'NPT-PRES-000001',
                'quantity' => 100,
                'unit' => 'ml',
            ]],
        ]);

        $this->assertTrue($result['valid']);
        Http::assertSent(fn ($request) => $request->method() === 'POST'
            && $request->hasHeader('Authorization', 'Bearer service-token')
            && $request['medical_unit_code'] === 'HOSP-000001');
    }

    public function test_it_uploads_a_private_mixture_document(): void
    {
        config()->set('cbta.base_url', 'https://cbta.test');
        config()->set('cbta.token', 'service-token');
        Http::fake([
            'https://cbta.test/api/internal/v1/mixture-requests/REQ-1/documents' => Http::response([
                'data' => [
                    'id' => 10,
                    'type' => 'authorization',
                    'name' => 'autorizacion.pdf',
                    'sha256' => hash('sha256', 'pdf-content'),
                ],
            ], 201),
        ]);

        $document = app(CbtaCatalogClient::class)->uploadMixtureDocument('REQ-1', [
            'type' => 'authorization',
            'name' => 'autorizacion.pdf',
            'contents' => 'pdf-content',
        ]);

        $this->assertSame(10, $document['id']);
        Http::assertSent(fn ($request) => $request->method() === 'POST'
            && $request->url() === 'https://cbta.test/api/internal/v1/mixture-requests/REQ-1/documents'
            && $request->hasHeader('Authorization', 'Bearer service-token'));
    }

    public function test_it_downloads_a_private_mixture_document(): void
    {
        config()->set('cbta.base_url', 'https://cbta.test');
        config()->set('cbta.token', 'service-token');
        Http::fake([
            'https://cbta.test/api/internal/v1/mixture-requests/REQ-1/documents/10' => Http::response(
                'pdf-content',
                200,
                ['Content-Type' => 'application/pdf']
            ),
        ]);

        $document = app(CbtaCatalogClient::class)->downloadMixtureDocument('REQ-1', 10);

        $this->assertSame('pdf-content', $document['contents']);
        $this->assertSame('application/pdf', $document['mime_type']);
        Http::assertSent(fn ($request) => $request->method() === 'GET'
            && $request->url() === 'https://cbta.test/api/internal/v1/mixture-requests/REQ-1/documents/10'
            && $request->hasHeader('Authorization', 'Bearer service-token'));
    }

    public function test_it_downloads_the_official_mixture_remission(): void
    {
        config()->set('cbta.base_url', 'https://cbta.test');
        config()->set('cbta.token', 'service-token');
        Http::fake([
            'https://cbta.test/api/internal/v1/mixture-requests/REQ-1/remission' => Http::response(
                'remission-pdf', 200, ['Content-Type' => 'application/pdf']
            ),
        ]);

        $remission = app(CbtaCatalogClient::class)->downloadMixtureRemission('REQ-1');

        $this->assertSame('remission-pdf', $remission['contents']);
        $this->assertSame('application/pdf', $remission['mime_type']);
        Http::assertSent(fn ($request) => $request->method() === 'GET'
            && $request->url() === 'https://cbta.test/api/internal/v1/mixture-requests/REQ-1/remission'
            && $request->hasHeader('Authorization', 'Bearer service-token'));
    }
}
