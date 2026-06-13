<?php

namespace App\Http\Controllers;

use App\Contracts\ExerciseProvider;
use App\Exceptions\ExerciseSourceException;
use App\Models\Exercise;
use App\Services\DatabaseManager;
use App\Services\QueryHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class SqlExerciseController extends Controller
{
    private const DIFFICULTIES = [
        'easy' => 'Einfach',
        'medium' => 'Mittel',
        'hard' => 'Schwer',
    ];

    public function __construct(
        private readonly DatabaseManager $dbManager,
        private readonly ExerciseProvider $exerciseProvider,
    ) {}

    public function index()
    {
        return view('it.sql-exercise.select-difficulty', [
            'difficulties' => $this->difficultyOptions(),
        ]);
    }

    public function generate(string $difficulty)
    {
        abort_if(! array_key_exists($difficulty, self::DIFFICULTIES), 404, 'Unbekannter Schwierigkeitsgrad.');

        try {
            $data = $this->exerciseProvider->random('sql', $difficulty);
            $exercise = $this->findSqlExercise((int) $data['database_id']);
            $tables = $this->withTemporaryDatabase(
                $exercise,
                fn (string $dbName): array => $this->dbManager->getTables($dbName),
            );

            return view('it.sql-exercise.index', $this->viewData($exercise, $tables, [
                'userSql' => $exercise->sqlDetail->starter_sql,
            ]));
        } catch (ExerciseSourceException $e) {
            Log::warning('SQL catalog exercise could not be loaded.', [
                'user_id' => auth()->id(),
                'difficulty' => $difficulty,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->route('sql-uebung')
                ->withErrors(['exercise_source' => $e->getMessage()]);
        } catch (Throwable $e) {
            Log::channel('sql_exercise')->error('SQL exercise preview failed.', [
                'user_id' => auth()->id(),
                'difficulty' => $difficulty,
                'exception' => $e::class,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->route('sql-uebung')
                ->withErrors([
                    'exercise_source' => 'Die SQL-Aufgabe konnte nicht vorbereitet werden. Bitte versuche es erneut.',
                ]);
        }
    }

    public function executeUserQuery(Request $request, Exercise $exercise)
    {
        $validated = $request->validate([
            'sql_input' => ['required', 'string', 'max:5000'],
        ]);
        $exercise = $this->validateSqlExercise($exercise);
        $sql = $validated['sql_input'];

        try {
            return $this->withTemporaryDatabase(
                $exercise,
                function (string $dbName) use ($exercise, $sql) {
                    $pdo = $this->dbManager->connectToDatabase($dbName);
                    $tables = $this->dbManager->getTables($dbName);
                    $result = QueryHandler::executeUserQuery($pdo, $sql);
                    $solutionResult = $result['success']
                        ? QueryHandler::executeUserQuery($pdo, $exercise->sqlDetail->solution_sql)
                        : null;

                    return view('it.sql-exercise.index', $this->viewData($exercise, $tables, [
                        'result' => $result,
                        'solutionResult' => $solutionResult,
                        'userSql' => $sql,
                    ]));
                },
            );
        } catch (Throwable $e) {
            Log::channel('sql_exercise')->error('SQL exercise execution sandbox failed.', [
                'user_id' => auth()->id(),
                'exercise_id' => $exercise->id,
                'exception' => $e::class,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->route('sql-uebung')
                ->withErrors([
                    'exercise_source' => 'Die SQL-Ausführungsumgebung konnte nicht vorbereitet werden. Bitte versuche es erneut.',
                ]);
        }
    }

    /**
     * @template T
     *
     * @param  callable(string): T  $callback
     * @return T
     */
    private function withTemporaryDatabase(Exercise $exercise, callable $callback): mixed
    {
        $dbName = null;

        try {
            $dbName = $this->dbManager->createTemporaryDatabase();
            $this->dbManager->createMySqlExercise($exercise->sqlDetail->setup_sql, $dbName);

            return $callback($dbName);
        } finally {
            if ($dbName !== null) {
                $this->dropTemporaryDatabase($dbName, $exercise->id);
            }
        }
    }

    private function dropTemporaryDatabase(string $dbName, int $exerciseId): void
    {
        try {
            $this->dbManager->dropTemporaryDatabase($dbName);
        } catch (Throwable $e) {
            Log::channel('sql_exercise')->warning('Failed to clean up request-scoped SQL database.', [
                'database' => $dbName,
                'exercise_id' => $exerciseId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function findSqlExercise(int $exerciseId): Exercise
    {
        return $this->validateSqlExercise(
            Exercise::query()->with('sqlDetail')->findOrFail($exerciseId),
        );
    }

    private function validateSqlExercise(Exercise $exercise): Exercise
    {
        abort_unless($exercise->type === 'sql' && $exercise->status === 'published', 404);
        $exercise->loadMissing('sqlDetail');

        if ($exercise->sqlDetail === null) {
            throw new ExerciseSourceException(
                "SQL-Aufgabendetails fehlen fuer {$exercise->external_id}.",
            );
        }

        return $exercise;
    }

    /**
     * @param  array<string, array<int, array<string, mixed>>>  $tables
     * @param  array<string, mixed>  $additional
     * @return array<string, mixed>
     */
    private function viewData(Exercise $exercise, array $tables, array $additional = []): array
    {
        return array_merge([
            'exerciseId' => $exercise->id,
            'tables' => $tables,
            'task' => $exercise->task,
            'solution' => $exercise->sqlDetail->solution_sql,
            'difficultyLabel' => $this->difficultyLabel($exercise->difficulty),
            'sourceLabel' => $this->sourceLabel($exercise->source),
            'explanation' => $exercise->explanation,
        ], $additional);
    }

    private function difficultyOptions(): array
    {
        return collect(self::DIFFICULTIES)
            ->map(fn (string $label, string $value): array => compact('value', 'label'))
            ->values()
            ->all();
    }

    private function difficultyLabel(?string $difficulty): ?string
    {
        return $difficulty !== null ? (self::DIFFICULTIES[$difficulty] ?? null) : null;
    }

    private function sourceLabel(?string $source): ?string
    {
        return match ($source) {
            'fixture', 'json' => 'Beispielaufgabe',
            default => null,
        };
    }
}
