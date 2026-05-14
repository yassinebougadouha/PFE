<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SupportBackendService
{
    private string $baseUrl;
    private string $prefix;
    private int $timeout;
    private ?string $bearerToken;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.support_api.base_url'), '/');
        $this->prefix = '/' . trim((string) config('services.support_api.prefix', '/api/v1'), '/');
        $this->timeout = (int) config('services.support_api.timeout', 30);
        $this->bearerToken = config('services.support_api.bearer_token');
    }

    public function ingestGlpiTicket(array $payload): array
    {
        $response = $this->client()->post($this->url('/internal/tickets/glpi-ingest'), $payload);

        if (!$response->successful()) {
            Log::warning('Support backend GLPI ingest failed: ' . $response->status() . ' ' . $response->body());
            throw new \RuntimeException('Backend ingest failed: ' . $response->body());
        }

        return $response->json() ?? [];
    }

    private function client()
    {
        $headers = ['Accept' => 'application/json'];
        if ($this->bearerToken) {
            $headers['Authorization'] = 'Bearer ' . $this->bearerToken;
        }

        return Http::timeout($this->timeout)->withHeaders($headers);
    }

    private function url(string $path): string
    {
        return $this->baseUrl . $this->prefix . '/' . ltrim($path, '/');
    }
}
