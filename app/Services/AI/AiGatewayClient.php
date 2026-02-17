<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class AiGatewayClient
{
    public function generateSql(array $payload): array
    {
        $baseUrl = rtrim(config('services.ai_gateway.url'), '/');

        // request_id notfalls serverseitig erzeugen
        $payload['request_id'] = $payload['request_id'] ?? (string) Str::uuid();

        $resp = Http::timeout(180)
            ->acceptJson()
            ->asJson()
            ->post($baseUrl . '/generate/sql', $payload);

        if (!$resp->successful()) {
            // detail von FastAPI (falls vorhanden)
            $detail = $resp->json('detail') ?? $resp->body();
            throw new RuntimeException("AI-Gateway error ({$resp->status()}): {$detail}");
        }

        return $resp->json();
    }
}
