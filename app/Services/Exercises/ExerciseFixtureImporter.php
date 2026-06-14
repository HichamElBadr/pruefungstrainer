<?php

namespace App\Services\Exercises;

use App\Exceptions\ExerciseSourceException;
use App\Models\Category;
use App\Models\Exercise;
use Illuminate\Support\Facades\DB;

class ExerciseFixtureImporter
{
    private const CATEGORIES = [
        'sql' => [
            'slug' => 'sql',
            'name' => 'SQL',
            'description' => 'Datenbankabfragen und relationale Datenmodelle.',
            'sort_order' => 10,
        ],
        'uml' => [
            'slug' => 'uml',
            'name' => 'UML',
            'description' => 'Modellierung mit UML-Diagrammen.',
            'sort_order' => 20,
        ],
        'calculation' => [
            'slug' => 'calculation',
            'name' => 'Calculation',
            'description' => 'IT-bezogene Rechenaufgaben.',
            'sort_order' => 30,
        ],
    ];

    public function __construct(
        private readonly JsonExerciseProvider $jsonExerciseProvider,
    ) {}

    /**
     * @return array{total: int, created: int, updated: int}
     */
    public function import(): array
    {
        return DB::transaction(function (): array {
            $seenExternalIds = [];
            $created = 0;
            $updated = 0;

            foreach (config('exercises.types', []) as $type) {
                $category = $this->upsertCategory($type);

                foreach (config('exercises.difficulties', []) as $difficulty) {
                    foreach ($this->jsonExerciseProvider->all($type, $difficulty) as $fixture) {
                        $externalId = (string) $fixture['id'];

                        if (isset($seenExternalIds[$externalId])) {
                            throw new ExerciseSourceException(
                                "Doppelte Aufgaben-ID beim Import: {$externalId}.",
                            );
                        }

                        $seenExternalIds[$externalId] = true;
                        $exercise = Exercise::updateOrCreate(
                            ['external_id' => $externalId],
                            [
                                'category_id' => $category->id,
                                'type' => $type,
                                'topic' => $fixture['topic'],
                                'difficulty' => $difficulty,
                                'title' => $fixture['title'],
                                'task' => $fixture['task'],
                                'explanation' => $fixture['explanation'],
                                'source' => 'json',
                                'status' => 'published',
                            ],
                        );

                        $exercise->wasRecentlyCreated ? $created++ : $updated++;
                        $this->upsertDetail($exercise, $fixture);
                        $this->removeObsoleteDetails($exercise);
                    }
                }
            }

            return [
                'total' => $created + $updated,
                'created' => $created,
                'updated' => $updated,
            ];
        });
    }

    private function upsertCategory(string $type): Category
    {
        $attributes = self::CATEGORIES[$type] ?? null;

        if ($attributes === null) {
            throw new ExerciseSourceException("Keine Kategorie fuer den Aufgabentyp {$type} konfiguriert.");
        }

        return Category::updateOrCreate(
            ['slug' => $attributes['slug']],
            array_merge($attributes, ['is_active' => true]),
        );
    }

    /**
     * @param  array<string, mixed>  $fixture
     */
    private function upsertDetail(Exercise $exercise, array $fixture): void
    {
        match ($exercise->type) {
            'sql' => $exercise->sqlDetail()->updateOrCreate([], [
                'setup_sql' => $fixture['setup_sql'],
                'starter_sql' => $fixture['starter_sql'] ?? null,
                'solution_sql' => $fixture['solution'],
            ]),
            'calculation' => $exercise->calculationDetail()->updateOrCreate([], [
                'expected_value' => str_replace(',', '.', (string) $fixture['expected_result']),
                'tolerance' => str_replace(',', '.', (string) ($fixture['tolerance'] ?? '0')),
                'unit' => $fixture['unit'] ?? null,
                'solution_steps' => $fixture['solution_steps'] ?? null,
            ]),
            'uml' => $exercise->umlDetail()->updateOrCreate([], [
                'diagram_type' => $fixture['diagram_type'],
                'scenario' => $fixture['scenario'],
                'requirements' => $fixture['requirements'],
                'starter_plantuml' => $fixture['starter_plantuml'] ?? null,
                'solution_plantuml' => $fixture['solution_plantuml'],
                'expected_elements' => $fixture['expected_elements'],
            ]),
            default => throw new ExerciseSourceException(
                "Aufgabentyp wird beim Import nicht unterstuetzt: {$exercise->type}.",
            ),
        };
    }

    private function removeObsoleteDetails(Exercise $exercise): void
    {
        if ($exercise->type !== 'sql') {
            $exercise->sqlDetail()->delete();
        }

        if ($exercise->type !== 'calculation') {
            $exercise->calculationDetail()->delete();
        }

        if ($exercise->type !== 'uml') {
            $exercise->umlDetail()->delete();
        }
    }
}
