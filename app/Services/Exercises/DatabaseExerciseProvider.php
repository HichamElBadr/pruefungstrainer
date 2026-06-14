<?php

namespace App\Services\Exercises;

use App\Contracts\ExerciseProvider;
use App\Exceptions\ExerciseSourceException;
use App\Models\Exercise;

class DatabaseExerciseProvider implements ExerciseProvider
{
    public function random(string $type, string $difficulty, array $criteria = []): array
    {
        $matching = $this->all($type, $difficulty, $criteria);

        return $matching[random_int(0, count($matching) - 1)];
    }

    public function all(string $type, string $difficulty, array $criteria = []): array
    {
        $this->assertSupportedValue('Aufgabentyp', $type, config('exercises.types', []));
        $this->assertSupportedValue('Schwierigkeitsgrad', $difficulty, config('exercises.difficulties', []));

        $exercises = Exercise::query()
            ->with($this->detailRelation($type))
            ->where('type', $type)
            ->where('difficulty', $difficulty)
            ->where('status', 'published')
            ->get()
            ->map(fn (Exercise $exercise): array => $this->toPayload($exercise))
            ->filter(fn (array $exercise): bool => $this->matches($exercise, $criteria))
            ->values()
            ->all();

        if ($exercises === []) {
            $criteriaText = collect($criteria)
                ->map(fn (mixed $value, string $field): string => "{$field}={$value}")
                ->implode(', ');

            throw new ExerciseSourceException(
                "Keine passende Datenbank-Aufgabe fuer {$type} ({$difficulty}) gefunden"
                .($criteriaText !== '' ? ": {$criteriaText}." : '.')
                .' Fuehre php artisan exercises:import aus.',
            );
        }

        return $exercises;
    }

    /**
     * @return array<string, mixed>
     */
    private function toPayload(Exercise $exercise): array
    {
        $this->assertDetailExists($exercise);

        $payload = [
            'database_id' => $exercise->id,
            'id' => $exercise->external_id,
            'type' => $exercise->type,
            'difficulty' => $exercise->difficulty,
            'topic' => $exercise->topic,
            'title' => $exercise->title,
            'task' => $exercise->task,
            'explanation' => $exercise->explanation,
            'source' => $exercise->source,
        ];

        return match ($exercise->type) {
            'sql' => array_merge($payload, [
                'setup_sql' => $exercise->sqlDetail->setup_sql,
                'starter_sql' => $exercise->sqlDetail->starter_sql,
                'solution' => $exercise->sqlDetail->solution_sql,
            ]),
            'calculation' => array_merge($payload, [
                'expected_result' => $this->normalizedDecimal(
                    $exercise->calculationDetail->expected_value,
                ),
                'tolerance' => $this->normalizedDecimal(
                    $exercise->calculationDetail->tolerance,
                ),
                'unit' => $exercise->calculationDetail->unit,
                'solution_steps' => $exercise->calculationDetail->solution_steps,
            ]),
            'uml' => array_merge($payload, [
                'diagram_type' => $exercise->umlDetail->diagram_type,
                'starter_plantuml' => $exercise->umlDetail->starter_plantuml,
                'solution_plantuml' => $exercise->umlDetail->solution_plantuml,
            ]),
            default => $payload,
        };
    }

    private function assertDetailExists(Exercise $exercise): void
    {
        $detailExists = match ($exercise->type) {
            'sql' => $exercise->sqlDetail !== null,
            'calculation' => $exercise->calculationDetail !== null,
            'uml' => $exercise->umlDetail !== null,
            default => false,
        };

        if (! $detailExists) {
            throw new ExerciseSourceException(
                "Aufgabendetails fehlen fuer {$exercise->external_id} ({$exercise->type}).",
            );
        }
    }

    private function detailRelation(string $type): string
    {
        return match ($type) {
            'sql' => 'sqlDetail',
            'calculation' => 'calculationDetail',
            'uml' => 'umlDetail',
            default => throw new ExerciseSourceException("Aufgabentyp wird nicht unterstuetzt: {$type}."),
        };
    }

    /**
     * @param  array<string, mixed>  $exercise
     * @param  array<string, scalar|null>  $criteria
     */
    private function matches(array $exercise, array $criteria): bool
    {
        foreach ($criteria as $field => $expected) {
            if ($expected === null || $expected === '') {
                continue;
            }

            if (! array_key_exists($field, $exercise)) {
                return false;
            }

            if ($this->normalize($exercise[$field]) !== $this->normalize($expected)) {
                return false;
            }
        }

        return true;
    }

    private function normalizedDecimal(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = rtrim(rtrim((string) $value, '0'), '.');

        return $normalized === '' ? '0' : $normalized;
    }

    private function normalize(mixed $value): string
    {
        return strtolower(trim((string) $value));
    }

    /**
     * @param  array<int, string>  $supported
     */
    private function assertSupportedValue(string $label, string $value, array $supported): void
    {
        if (! in_array($value, $supported, true)) {
            throw new ExerciseSourceException("{$label} wird nicht unterstuetzt: {$value}.");
        }
    }
}
