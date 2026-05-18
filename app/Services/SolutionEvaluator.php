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
        if (!is_numeric($userValue) || !is_numeric($expected)) {
            return false;
        }

        return abs((float) $userValue - (float) $expected) <= $tolerance;
    }
}
