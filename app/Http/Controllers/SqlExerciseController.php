<?php

namespace App\Http\Controllers;

use App\Contracts\ExerciseProvider;
use App\Exceptions\ExerciseSourceException;
use App\Models\Category;
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

    private ?string $dbName = null;

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

        $setupSql = null;
        $persistedExerciseId = null;

        try {
            $data = $this->exerciseProvider->random('sql', $difficulty);
            $setupSql = (string) $data['setup_sql'];
            $this->dbName = $this->dbManager->createTemporaryDatabase();
            $this->dbManager->createMySqlExercise($setupSql, $this->dbName);
            session(['sql_temp_db' => $this->dbName]);

            $category = Category::firstOrCreate(['name' => 'SQL']);
            $exercise = Exercise::create([
                'user_id' => auth()->id(),
                'category_id' => $category->id,
                'title' => $data['title'],
                'difficulty' => $difficulty,
                'source' => $data['source'],
                'prompt' => json_encode($this->exerciseMetadata($data), JSON_UNESCAPED_UNICODE),
                'generated_task' => $data['task'],
                'solution' => $data['solution'],
            ]);
            $persistedExerciseId = $exercise->id;
            session(['sql_exercise_id' => $exercise->id]);

            return view('it.sql-exercise.index', [
                'tables' => $this->getAllTables(),
                'task' => $data['task'],
                'solution' => $data['solution'],
                'mysqlstatement' => $setupSql,
                'difficultyLabel' => self::DIFFICULTIES[$difficulty],
                'sourceLabel' => $this->sourceLabel($data['source']),
                'explanation' => $data['explanation'],
            ]);
        } catch (ExerciseSourceException $e) {
            Log::warning('SQL exercise fixture could not be loaded.', [
                'user_id' => auth()->id(),
                'difficulty' => $difficulty,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->route('sql-uebung')
                ->withErrors(['exercise_source' => $e->getMessage()]);
        } catch (Throwable $e) {
            $this->cleanupFailedExercise($persistedExerciseId);

            Log::channel('sql_exercise')->error('SQL exercise flow failed.', [
                'user_id' => auth()->id(),
                'database' => $this->dbName,
                'difficulty' => $difficulty,
                'setup_sql' => $setupSql,
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

    public function executeUserQuery(Request $request)
    {
        $validated = $request->validate([
            'sql_input' => ['required', 'string', 'max:5000'],
        ]);

        $pdo = $this->dbManager->connectToDatabase($this->currentDatabaseName());
        $tables = $this->getAllTables();
        $sql = $validated['sql_input'];
        $result = QueryHandler::executeUserQuery($pdo, $sql);
        $exercise = $this->currentExercise();
        $solutionResult = QueryHandler::executeUserQuery($pdo, (string) $exercise->solution);
        $metadata = json_decode($exercise->prompt, true);

        return view('it.sql-exercise.index', [
            'tables' => $tables,
            'task' => $exercise->generated_task,
            'solution' => $exercise->solution,
            'difficultyLabel' => $this->difficultyLabel($exercise->difficulty),
            'sourceLabel' => $this->sourceLabel($exercise->source),
            'result' => $result,
            'solutionResult' => $solutionResult,
            'userSql' => $sql,
            'explanation' => is_array($metadata) ? ($metadata['explanation'] ?? null) : null,
        ]);
    }

    private function getAllTables(): array
    {
        return $this->dbManager->getTables($this->currentDatabaseName());
    }

    private function currentDatabaseName(): string
    {
        $dbName = session('sql_temp_db');

        abort_if(! $dbName, 409, 'Keine temporaere Datenbank in der Session gefunden. Bitte starte die SQL-Uebung neu.');

        return $dbName;
    }

    private function currentExercise(): Exercise
    {
        $exerciseId = session('sql_exercise_id');

        abort_if(! $exerciseId, 409, 'Keine SQL-Uebung in der Session gefunden. Bitte starte die SQL-Uebung neu.');

        return Exercise::query()
            ->whereKey($exerciseId)
            ->where('user_id', auth()->id())
            ->firstOrFail();
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

    /**
     * @param  array<string, mixed>  $exercise
     * @return array<string, mixed>
     */
    private function exerciseMetadata(array $exercise): array
    {
        unset($exercise['setup_sql'], $exercise['solution']);

        return $exercise;
    }

    private function cleanupFailedExercise(?int $exerciseId): void
    {
        session()->forget(['sql_temp_db', 'sql_exercise_id']);

        if ($exerciseId !== null) {
            Exercise::whereKey($exerciseId)->delete();
        }

        if ($this->dbName === null) {
            return;
        }

        try {
            $this->dbManager->dropTemporaryDatabase($this->dbName);
        } catch (Throwable $e) {
            Log::channel('sql_exercise')->warning('Failed to clean up SQL exercise database.', [
                'database' => $this->dbName,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
