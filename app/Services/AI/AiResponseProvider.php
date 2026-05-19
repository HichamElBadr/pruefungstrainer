<?php

namespace App\Services\AI;

use Closure;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

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
            fixtureCriteria: ['difficulty' => $payload['difficulty'] ?? null],
            fallbackOnFailure: false,
        );
    }

    public function getCalculation(array $payload, string $fixtureKey = 'calculation'): array
    {
        return $this->getValidatedResponse(
            fixtureKey: $fixtureKey,
            liveResolver: fn () => $this->gateway->generateCalculation($payload),
            requiredFields: ['title', 'task', 'expected_result', 'expected_unit', 'sample_solution'],
            fixtureCriteria: [
                'topic' => $payload['topic_slug'] ?? $payload['topic'] ?? null,
                'difficulty' => $payload['difficulty'] ?? null,
            ],
            fallbackCriteria: [
                'topic' => $payload['topic_slug'] ?? $payload['topic'] ?? null,
            ],
        );
    }

    private function getValidatedResponse(
        string $fixtureKey,
        Closure $liveResolver,
        array $requiredFields,
        array $fixtureCriteria = [],
        array $fallbackCriteria = [],
        bool $fallbackOnFailure = true,
    ): array
    {
        $mode = config('services.ai.mode', 'ollama');
        $selectedFixture = $this->selectedFixture($fixtureKey);

        if ($mode === 'fixtures') {
            return $this->withSource($this->validateResponse(
                $this->aiFixtureService->loadMatching($selectedFixture, $fixtureCriteria, $fallbackCriteria),
                $requiredFields,
            ), 'fixture');
        }

        try {
            return $this->withSource($this->validateResponse($liveResolver(), $requiredFields), 'generated');
        } catch (Throwable $e) {
            if (!$fallbackOnFailure) {
                throw $e;
            }

            Log::warning('AI response failed; using fixture fallback.', [
                'fixture' => $selectedFixture,
                'criteria' => $fixtureCriteria,
                'fallback_criteria' => $fallbackCriteria,
                'exception' => $e::class,
                'error' => $e->getMessage(),
            ]);

            return $this->withSource($this->validateResponse(
                $this->aiFixtureService->loadMatching($selectedFixture, $fixtureCriteria, $fallbackCriteria),
                $requiredFields,
            ), 'fixture');
        }
    }

    private function validateResponse(array $data, array $requiredFields): array
    {
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || !is_string($data[$field]) || trim($data[$field]) === '') {
                throw new RuntimeException("AI response missing/invalid field: {$field}");
            }
        }

        return $data;
    }

    private function withSource(array $data, string $source): array
    {
        $data['source'] = $source;

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
