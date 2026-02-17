<?php

namespace App\Services\AI;

use RuntimeException;

class AiResponseProvider
{
    public function __construct(
        private readonly AiGatewayClient $gateway,
        private readonly AiFixtureService $aiFixtureService,
    ) {}

    /**
     * $fixtureKey: "sql" | "uml" | ...
     */
    public function getSql(array $payload, string $fixtureKey = 'sql'): array
    {
        $mode = config('services.ai.mode', 'ollama'); // "fixtures" | "ollama"

        if ($mode === 'fixtures') {
            $selectedFixture = $fixtureKey;

            if (app()->environment('local')) {
                $selectedFixture = request()->get('fixture', $fixtureKey);
            }

            return $this->aiFixtureService->load($selectedFixture);
        }

        // Real mode -> call AI Gateway
        $data = $this->gateway->generateSql($payload);

        // Minimal sanity check
        foreach (['task', 'mysqlstatement', 'solution'] as $k) {
            if (!isset($data[$k]) || !is_string($data[$k]) || trim($data[$k]) === '') {
                throw new RuntimeException("AI response missing/invalid field: {$k}");
            }
        }

        return $data;
    }
}
