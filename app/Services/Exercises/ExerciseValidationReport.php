<?php

namespace App\Services\Exercises;

class ExerciseValidationReport
{
    /**
     * @var array<string, int>
     */
    private array $collectionCounts = [];

    /**
     * @var array<int, string>
     */
    private array $errors = [];

    public function recordCollection(string $type, string $difficulty, int $count): void
    {
        $this->collectionCounts["{$type}/{$difficulty}"] = $count;
    }

    public function addError(string $message): void
    {
        $this->errors[] = $message;
    }

    /**
     * @return array<string, int>
     */
    public function collectionCounts(): array
    {
        return $this->collectionCounts;
    }

    /**
     * @return array<int, string>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    public function exerciseCount(): int
    {
        return array_sum($this->collectionCounts);
    }

    public function isValid(): bool
    {
        return $this->errors === [];
    }
}
