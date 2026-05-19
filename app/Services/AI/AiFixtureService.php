<?php

namespace App\Services\AI;

use RuntimeException;

/**
 * Decode fixture JSON and return it as an array.
 */
class AiFixtureService
{
    public function load(string $fixtureName): array
    {
        $fixtures = $this->loadAll($fixtureName);

        return $fixtures[0];
    }

    public function loadMatching(string $fixtureName, array $criteria, array $fallbackCriteria = []): array
    {
        $fixtures = $this->loadAll($fixtureName);
        $matched = $this->matchingFixtures($fixtures, $criteria);

        if (empty($matched) && $fallbackCriteria !== []) {
            $matched = $this->matchingFixtures($fixtures, $fallbackCriteria);
        }

        if (empty($matched) && count($fixtures) === 1) {
            return $fixtures[0];
        }

        if (empty($matched)) {
            throw new RuntimeException("No matching fixture found: {$fixtureName}");
        }

        return $matched[0];
    }

    public function loadAll(string $fixtureName): array
    {
        $path = resource_path("ai-fixtures/{$fixtureName}.json");

        if (!file_exists($path)) {
            throw new RuntimeException("Fixture not found: {$path}");
        }

        $raw = file_get_contents($path);
        $data = json_decode($raw, true);

        if (!is_array($data)) {
            throw new RuntimeException("Invalid JSON fixture: {$fixtureName}");
        }

        $fixtures = array_is_list($data) ? $data : [$data];

        foreach ($fixtures as $fixture) {
            if (!is_array($fixture)) {
                throw new RuntimeException("Invalid JSON fixture item: {$fixtureName}");
            }
        }

        return $fixtures;
    }

    private function matchingFixtures(array $fixtures, array $criteria): array
    {
        return array_values(array_filter(
            $fixtures,
            fn (array $fixture) => $this->matches($fixture, $criteria),
        ));
    }

    private function matches(array $fixture, array $criteria): bool
    {
        foreach ($criteria as $field => $expected) {
            if ($expected === null || $expected === '') {
                continue;
            }

            if (!array_key_exists($field, $fixture)) {
                return false;
            }

            if ($this->normalize($fixture[$field]) !== $this->normalize($expected)) {
                return false;
            }
        }

        return true;
    }

    private function normalize(mixed $value): string
    {
        return strtolower(trim((string) $value));
    }
}
