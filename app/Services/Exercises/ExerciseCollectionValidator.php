<?php

namespace App\Services\Exercises;

use App\Services\CalculationExerciseTopicCatalog;
use App\Services\DatabaseManager;
use App\Services\PlantUmlService;
use App\Services\QueryHandler;
use Illuminate\Contracts\Container\Container;
use Throwable;

class ExerciseCollectionValidator
{
    public function __construct(
        private readonly JsonExerciseProvider $exerciseProvider,
        private readonly CalculationExerciseTopicCatalog $calculationTopics,
        private readonly Container $container,
    ) {}

    public function validate(
        bool $executeSql = true,
        bool $renderUml = true,
    ): ExerciseValidationReport {
        $report = new ExerciseValidationReport;
        $ids = [];
        $calculationCoverage = [];

        foreach (config('exercises.types', []) as $type) {
            foreach (config('exercises.difficulties', []) as $difficulty) {
                try {
                    $exercises = $this->exerciseProvider->all($type, $difficulty);
                    $report->recordCollection($type, $difficulty, count($exercises));

                    $minimum = max(1, (int) config('exercises.minimum_per_collection', 1));

                    if (count($exercises) < $minimum) {
                        $report->addError(
                            "[{$type}/{$difficulty}] Mindestens {$minimum} Aufgaben erforderlich; "
                            .count($exercises).' vorhanden.',
                        );
                    }
                } catch (Throwable $e) {
                    $report->recordCollection($type, $difficulty, 0);
                    $report->addError("[{$type}/{$difficulty}] {$e->getMessage()}");

                    continue;
                }

                foreach ($exercises as $exercise) {
                    $id = (string) $exercise['id'];

                    if (isset($ids[$id])) {
                        $report->addError(
                            "[{$type}/{$difficulty}/{$id}] Doppelte Aufgaben-ID; zuerst verwendet in {$ids[$id]}.",
                        );
                    } else {
                        $ids[$id] = "{$type}/{$difficulty}";
                    }

                    match ($type) {
                        'sql' => $this->validateSql($exercise, $report, $executeSql),
                        'uml' => $this->validateUml($exercise, $report, $renderUml),
                        'calculation' => $this->validateCalculation(
                            $exercise,
                            $report,
                            $calculationCoverage,
                        ),
                        default => null,
                    };
                }
            }
        }

        $this->validateCalculationCoverage($calculationCoverage, $report);

        return $report;
    }

    /**
     * @param  array<string, mixed>  $exercise
     */
    private function validateSql(
        array $exercise,
        ExerciseValidationReport $report,
        bool $executeSql,
    ): void {
        $id = (string) $exercise['id'];
        $solution = (string) $exercise['solution'];
        $validationError = QueryHandler::validationError($solution);

        if ($validationError !== null) {
            $report->addError("[sql/{$exercise['difficulty']}/{$id}] Unsichere Musterloesung: {$validationError}");

            return;
        }

        if (! $executeSql) {
            return;
        }

        $database = null;
        $databaseManager = $this->container->make(DatabaseManager::class);

        try {
            $database = $databaseManager->createTemporaryDatabase();
            $databaseManager->createMySqlExercise((string) $exercise['setup_sql'], $database);
            $pdo = $databaseManager->connectToDatabase($database);
            $result = QueryHandler::executeUserQuery($pdo, $solution);

            if (! $result['success']) {
                $report->addError(
                    "[sql/{$exercise['difficulty']}/{$id}] Musterloesung konnte nicht ausgefuehrt werden: "
                    .$result['technical_message'],
                );
            } elseif ($result['columns'] === []) {
                $report->addError("[sql/{$exercise['difficulty']}/{$id}] Musterloesung liefert keine Spalten.");
            }
        } catch (Throwable $e) {
            $report->addError(
                "[sql/{$exercise['difficulty']}/{$id}] SQL-Ausfuehrung fehlgeschlagen: {$e->getMessage()}",
            );
        } finally {
            if ($database !== null) {
                try {
                    $databaseManager->dropTemporaryDatabase($database);
                } catch (Throwable $e) {
                    $report->addError(
                        "[sql/{$exercise['difficulty']}/{$id}] Temporaere Datenbank konnte nicht geloescht werden: "
                        .$e->getMessage(),
                    );
                }
            }
        }
    }

    /**
     * @param  array<string, mixed>  $exercise
     */
    private function validateUml(
        array $exercise,
        ExerciseValidationReport $report,
        bool $renderUml,
    ): void {
        $id = (string) $exercise['id'];
        $solution = trim((string) $exercise['solution_plantuml']);

        if (
            ! preg_match('/^@startuml\b/i', $solution)
            || ! preg_match('/@enduml\s*$/i', $solution)
        ) {
            $report->addError(
                "[uml/{$exercise['difficulty']}/{$id}] solution_plantuml muss @startuml und @enduml enthalten.",
            );

            return;
        }

        if (! $renderUml) {
            return;
        }

        $imagePath = null;

        try {
            $imagePath = $this->container->make(PlantUmlService::class)->generate($solution);
        } catch (Throwable $e) {
            $report->addError(
                "[uml/{$exercise['difficulty']}/{$id}] PlantUML-Validierung fehlgeschlagen: {$e->getMessage()}",
            );
        } finally {
            if ($imagePath !== null && is_file($imagePath)) {
                unlink($imagePath);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $exercise
     * @param  array<string, array<string, bool>>  $coverage
     */
    private function validateCalculation(
        array $exercise,
        ExerciseValidationReport $report,
        array &$coverage,
    ): void {
        $difficulty = (string) $exercise['difficulty'];
        $topic = (string) $exercise['topic'];
        $id = (string) $exercise['id'];
        $coverage[$difficulty][$topic] = true;

        if ($this->calculationTopics->find($topic) === null) {
            $report->addError(
                "[calculation/{$difficulty}/{$id}] Unbekanntes Rechenthema '{$topic}'.",
            );
        }

        $expectedResult = str_replace(',', '.', (string) $exercise['expected_result']);
        $solutionSteps = str_replace(',', '.', (string) $exercise['solution_steps']);

        if (! str_contains($solutionSteps, $expectedResult)) {
            $report->addError(
                "[calculation/{$difficulty}/{$id}] solution_steps enthaelt expected_result nicht.",
            );
        }
    }

    /**
     * @param  array<string, array<string, bool>>  $coverage
     */
    private function validateCalculationCoverage(
        array $coverage,
        ExerciseValidationReport $report,
    ): void {
        $requiredTopics = array_column($this->calculationTopics->all(), 'slug');

        foreach (config('exercises.difficulties', []) as $difficulty) {
            foreach ($requiredTopics as $topic) {
                if (! isset($coverage[$difficulty][$topic])) {
                    $report->addError(
                        "[calculation/{$difficulty}] Keine Aufgabe fuer das Thema '{$topic}' vorhanden.",
                    );
                }
            }
        }
    }
}
