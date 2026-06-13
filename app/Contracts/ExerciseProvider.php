<?php

namespace App\Contracts;

interface ExerciseProvider
{
    /**
     * @param  array<string, scalar|null>  $criteria
     * @return array<string, mixed>
     */
    public function random(string $type, string $difficulty, array $criteria = []): array;
}
