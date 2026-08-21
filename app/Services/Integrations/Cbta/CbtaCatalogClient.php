<?php

namespace App\Services\Integrations\Cbta;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class CbtaCatalogClient
{
    public function nptCatalog(string $medicalUnitCode): array
    {
        return $this->catalog($medicalUnitCode, 'npt');
    }

    public function oncologyCatalog(string $medicalUnitCode): array
    {
        return $this->catalog($medicalUnitCode, 'oncology');
    }

    public function prevalidateMixture(array $payload): array
    {
        $response = $this->request()->post('/api/internal/v1/mixture-requests/prevalidate', $payload);

        $response->throw();

        $data = $response->json('data');

        if (! is_array($data)
            || ! array_key_exists('valid', $data)
            || ! isset($data['catalog_version'], $data['items'], $data['errors'])) {
            throw new RuntimeException('CBTA devolvio un contrato de prevalidacion invalido.');
        }

        return $data;
    }

    public function createMixtureRequest(array $payload): array
    {
        $response = $this->request()->post('/api/internal/v1/mixture-requests', $payload);
        $response->throw();
        $data = $response->json('data');

        if (! is_array($data) || ! isset($data['request_id'], $data['status'])) {
            throw new RuntimeException('CBTA devolvio un contrato de solicitud invalido.');
        }

        return $data;
    }

    public function mixtureRequest(string $remoteRequestId): array
    {
        $response = $this->request()->get('/api/internal/v1/mixture-requests/'.rawurlencode($remoteRequestId));
        $response->throw();
        $data = $response->json('data');

        if (! is_array($data) || ! isset($data['request_id'], $data['status'])) {
            throw new RuntimeException('CBTA devolvio un estado de solicitud invalido.');
        }

        return $data;
    }

    public function uploadMixtureDocument(string $remoteRequestId, array $document): array
    {
        $response = $this->request()
            ->attach('document', $document['contents'], $document['name'])
            ->post('/api/internal/v1/mixture-requests/'.rawurlencode($remoteRequestId).'/documents', [
                'type' => $document['type'],
            ]);
        $response->throw();
        $data = $response->json('data');

        if (! is_array($data) || ! isset($data['id'], $data['type'], $data['sha256'])) {
            throw new RuntimeException('CBTA devolvio un contrato de documento invalido.');
        }

        return $data;
    }

    public function downloadMixtureDocument(string $remoteRequestId, int $documentId): array
    {
        $response = $this->request()->get(
            '/api/internal/v1/mixture-requests/'.rawurlencode($remoteRequestId).'/documents/'.$documentId
        );
        $response->throw();

        return [
            'contents' => $response->body(),
            'mime_type' => $response->header('Content-Type') ?: 'application/octet-stream',
        ];
    }

    public function downloadMixtureRemission(string $remoteRequestId): array
    {
        $response = $this->request()->get(
            '/api/internal/v1/mixture-requests/'.rawurlencode($remoteRequestId).'/remission'
        );
        $response->throw();

        return [
            'contents' => $response->body(),
            'mime_type' => $response->header('Content-Type') ?: 'application/pdf',
        ];
    }

    private function catalog(string $medicalUnitCode, string $type): array
    {
        $response = $this->request()->get(
            '/api/internal/v1/medical-units/'.rawurlencode($medicalUnitCode).'/catalogs/'.$type
        );

        $response->throw();

        $data = $response->json('data');

        if (! is_array($data) || ! isset($data['catalog_version'], $data['items'])) {
            throw new RuntimeException('CBTA devolvio un contrato de catalogo invalido.');
        }

        return $data;
    }

    private function request(): PendingRequest
    {
        $baseUrl = rtrim((string) config('cbta.base_url'), '/');
        $token = (string) config('cbta.token');

        if ($baseUrl === '' || $token === '') {
            throw new RuntimeException('La integracion CBTA no esta configurada.');
        }

        return Http::baseUrl($baseUrl)
            ->acceptJson()
            ->withToken($token)
            ->connectTimeout((int) config('cbta.connect_timeout', 3))
            ->timeout((int) config('cbta.timeout', 10))
            ->retry(2, 200, throw: false);
    }
}
