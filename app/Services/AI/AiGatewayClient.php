<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class AiGatewayClient
{
    public function generateSql(array $payload): array
    {
        return $this->post('/generate/sql', $payload);
    }

    public function generateScan(array $payload): array
    {
        return $this->post('/generate/scan', $payload);
    }

    private function post(string $path, array $payload): array
    {
        $baseUrl = rtrim(config('services.ai_gateway.url'), '/');
        $payload['request_id'] = $payload['request_id'] ?? (string) Str::uuid();

        $resp = Http::timeout(180)
            ->acceptJson()
            ->asJson()
            ->post($baseUrl . $path, $payload);

        if (!$resp->successful()) {
            $detail = $resp->json('detail') ?? $resp->body();
            throw new RuntimeException("AI-Gateway error ({$resp->status()}): {$detail}");
        }

        return $resp->json();
    }
}
