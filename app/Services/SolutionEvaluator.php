<?php

namespace App\Services;

class SolutionEvaluator
{
    public function compareText(string $userInput, string $solution): bool
    {
        return trim($userInput) === trim($solution);
    }

    public function compareNumeric(mixed $userValue, mixed $expected, float $tolerance = 0.01): bool
    {
        $normalizedUserValue = $this->normalizeNumericValue($userValue);
        $normalizedExpected = $this->normalizeNumericValue($expected);

        if ($normalizedUserValue === null || $normalizedExpected === null) {
            return false;
        }

        return abs($normalizedUserValue - $normalizedExpected) <= $tolerance;
    }

    private function normalizeNumericValue(mixed $value): ?float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $normalized = str_replace([' ', "\u{00A0}", ','], ['', '', '.'], trim((string) $value));

        if (!is_numeric($normalized)) {
            return null;
        }

        return (float) $normalized;
    }
}
