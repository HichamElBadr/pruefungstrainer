<?php

namespace App\Services\AI;

use Closure;
use RuntimeException;

class AiResponseProvider
{
    public function __construct(
        private readonly AiGatewayClient $gateway,
        private readonly AiFixtureService $aiFixtureService,
    ) {}

    public function getSql(array $payload, string $fixtureKey = 'sql'): array
    {
        return $this->getValidatedResponse(
            fixtureKey: $fixtureKey,
            liveResolver: fn () => $this->gateway->generateSql($payload),
            requiredFields: ['task', 'mysqlstatement', 'solution'],
        );
    }

    public function getCalculation(array $payload, string $fixtureKey = 'calculation'): array
    {
        return $this->getValidatedResponse(
            fixtureKey: $fixtureKey,
            liveResolver: fn () => $this->gateway->generateCalculation($payload),
            requiredFields: ['title', 'task', 'expected_result', 'expected_unit', 'sample_solution'],
        );
    }

    private function getValidatedResponse(string $fixtureKey, Closure $liveResolver, array $requiredFields): array
    {
        $mode = config('services.ai.mode', 'ollama');

        $data = $mode === 'fixtures'
            ? $this->aiFixtureService->load($this->selectedFixture($fixtureKey))
            : $liveResolver();

        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || !is_string($data[$field]) || trim($data[$field]) === '') {
                throw new RuntimeException("AI response missing/invalid field: {$field}");
            }
        }

        return $data;
    }

    private function selectedFixture(string $fixtureKey): string
    {
        if (app()->environment('local')) {
            return request()->get('fixture', $fixtureKey);
        }

        return $fixtureKey;
    }
}
