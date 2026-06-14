<?php

namespace App\Services\Exercises;

use App\Contracts\ExerciseProvider;
use App\Exceptions\ExerciseSourceException;
use JsonException;

class JsonExerciseProvider implements ExerciseProvider
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

        $directory = $this->exerciseDirectory($type, $difficulty);

        if (! is_dir($directory)) {
            throw new ExerciseSourceException(
                "Das Aufgabenverzeichnis fuer {$type} ({$difficulty}) wurde nicht gefunden.",
            );
        }

        $files = glob($directory.DIRECTORY_SEPARATOR.'*.json') ?: [];

        if ($files === []) {
            throw new ExerciseSourceException(
                "Das Aufgabenverzeichnis fuer {$type} ({$difficulty}) enthaelt keine JSON-Dateien.",
            );
        }

        sort($files);
        $fixtures = [];

        foreach ($files as $file) {
            foreach ($this->loadFile($file) as $index => $fixture) {
                $this->validateFixture($fixture, $type, $difficulty, $file, $index);
                $fixtures[] = $fixture;
            }
        }

        $matching = array_values(array_filter(
            $fixtures,
            fn (array $fixture): bool => $this->matches($fixture, $criteria),
        ));

        if ($matching === []) {
            $criteriaText = collect($criteria)
                ->map(fn (mixed $value, string $field): string => "{$field}={$value}")
                ->implode(', ');

            throw new ExerciseSourceException(
                "Keine passende JSON-Aufgabe fuer {$type} ({$difficulty}) gefunden"
                .($criteriaText !== '' ? ": {$criteriaText}." : '.'),
            );
        }

        foreach ($matching as &$exercise) {
            $exercise['source'] = 'json';
        }
        unset($exercise);

        return $matching;
    }

    private function exerciseDirectory(string $type, string $difficulty): string
    {
        $basePath = rtrim((string) config('exercises.path', resource_path('exercises')), '\\/');

        return $basePath.DIRECTORY_SEPARATOR.$type.DIRECTORY_SEPARATOR.$difficulty;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function loadFile(string $file): array
    {
        $raw = file_get_contents($file);

        if ($raw === false) {
            throw new ExerciseSourceException(
                'Die JSON-Aufgabendatei konnte nicht gelesen werden: '.basename($file),
            );
        }

        try {
            $decoded = json_decode($raw, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new ExerciseSourceException(
                'Ungueltiges JSON in '.basename($file).': '.$e->getMessage(),
                previous: $e,
            );
        }

        if (! is_array($decoded)) {
            throw new ExerciseSourceException(
                'Die JSON-Aufgabendatei muss ein Objekt oder eine Liste enthalten: '.basename($file),
            );
        }

        $fixtures = array_is_list($decoded) ? $decoded : [$decoded];

        if ($fixtures === []) {
            throw new ExerciseSourceException(
                'Die JSON-Aufgabendatei enthaelt keine Aufgaben: '.basename($file),
            );
        }

        foreach ($fixtures as $index => $fixture) {
            if (! is_array($fixture) || array_is_list($fixture)) {
                throw new ExerciseSourceException(
                    $this->fixtureLocation($file, $index).' muss ein JSON-Objekt sein.',
                );
            }
        }

        return $fixtures;
    }

    /**
     * @param  array<string, mixed>  $fixture
     */
    private function validateFixture(
        array $fixture,
        string $type,
        string $difficulty,
        string $file,
        int $index,
    ): void {
        $requiredFields = array_values(array_unique(array_merge(
            config('exercises.required_fields.common', []),
            config("exercises.required_fields.{$type}", []),
        )));

        foreach ($requiredFields as $field) {
            if (! array_key_exists($field, $fixture)) {
                throw new ExerciseSourceException(
                    $this->fixtureLocation($file, $index)." fehlt das Pflichtfeld '{$field}'.",
                );
            }

            if ($field === 'tags') {
                $this->validateTags($fixture[$field], $file, $index);

                continue;
            }

            if (! is_string($fixture[$field]) || trim($fixture[$field]) === '') {
                throw new ExerciseSourceException(
                    $this->fixtureLocation($file, $index)." enthaelt kein gueltiges Feld '{$field}'.",
                );
            }
        }

        if ($fixture['type'] !== $type) {
            throw new ExerciseSourceException(
                $this->fixtureLocation($file, $index)." hat den falschen Aufgabentyp '{$fixture['type']}'.",
            );
        }

        if ($fixture['difficulty'] !== $difficulty) {
            throw new ExerciseSourceException(
                $this->fixtureLocation($file, $index)
                ." hat den falschen Schwierigkeitsgrad '{$fixture['difficulty']}'.",
            );
        }

        if ($type === 'calculation' && ! is_numeric(str_replace(',', '.', $fixture['expected_result']))) {
            throw new ExerciseSourceException(
                $this->fixtureLocation($file, $index)." enthaelt kein numerisches Feld 'expected_result'.",
            );
        }

        if ($type === 'sql') {
            try {
                SqlSetupValidator::statements($fixture['setup_sql']);
            } catch (ExerciseSourceException $e) {
                throw new ExerciseSourceException(
                    $this->fixtureLocation($file, $index).': '.$e->getMessage(),
                    previous: $e,
                );
            }
        }
    }

    private function validateTags(mixed $tags, string $file, int $index): void
    {
        if (
            ! is_array($tags)
            || ! array_is_list($tags)
            || $tags === []
            || array_filter($tags, fn (mixed $tag): bool => ! is_string($tag) || trim($tag) === '') !== []
        ) {
            throw new ExerciseSourceException(
                $this->fixtureLocation($file, $index)." enthaelt kein gueltiges Feld 'tags'.",
            );
        }
    }

    /**
     * @param  array<string, mixed>  $fixture
     * @param  array<string, scalar|null>  $criteria
     */
    private function matches(array $fixture, array $criteria): bool
    {
        foreach ($criteria as $field => $expected) {
            if ($expected === null || $expected === '') {
                continue;
            }

            if (! array_key_exists($field, $fixture)) {
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

    /**
     * @param  array<int, string>  $supported
     */
    private function assertSupportedValue(string $label, string $value, array $supported): void
    {
        if (! in_array($value, $supported, true)) {
            throw new ExerciseSourceException("{$label} wird nicht unterstuetzt: {$value}.");
        }
    }

    private function fixtureLocation(string $file, int $index): string
    {
        return basename($file).' (Aufgabe '.($index + 1).')';
    }
}
