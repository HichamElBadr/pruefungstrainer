<?php

namespace App\Services;

class SolutionEvaluator
{
    public function compareText(string $user_input, string $solution) {}

    public function compareNumeric(float $user_value, float $expected, float $tolerance = 0.01): bool
    {
        return abs($user_value - $expected) <= $tolerance;
    }
}
