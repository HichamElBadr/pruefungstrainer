<?php

namespace App\Services\AI;

use RuntimeException;
use App\Services\OllamaService;

class AiResponseProvider
{
    public function __construct(
        private readonly OllamaService $ollama,
        private readonly JsonWrapper $jsonWrapper,
        private readonly AiFixtureService $ai_Fixture_service,
    ) {
    }

    /**
     * $fixtureKey: "sql" | "uml" |
     * 
     */
    public function get(string $prompt, string $fixtureKey): array
    {
        $mode = config('services.ai.mode', 'ollama');

        // Wenn wir im Fixture-Modus sind, kommen die Daten aus JSON-Dateien
        if ($mode === 'fixtures') {

            // Standard-Fixture (z. B. "sql", "uml", "scan")
            $selectedFixture = $fixtureKey;

            // Nur in der lokalen Entwicklungsumgebung erlauben wir
            // ein Umschalten über die URL (?fixture=sql2)
            if (app()->environment('local')) {
                $selectedFixture = request()->get('fixture', $fixtureKey);
            }

            return $this->ai_Fixture_service->load("{$selectedFixture}.json");
        }

        // normal generate with ollama
        $raw = $this->ollama->generate($prompt);
        $parsed = $this->jsonWrapper->parse($raw);

        if (!is_array($parsed)) {
            throw new RuntimeException('AI response parsing failed.');
        }

        return $parsed;
    }
}
