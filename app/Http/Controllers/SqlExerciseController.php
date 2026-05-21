<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Exercise;
use App\Services\AI\AiResponseProvider;
use App\Services\DatabaseManager;
use App\Services\QueryHandler;
use App\Services\SqlExerciseGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class SqlExerciseController extends Controller
{
    private const DIFFICULTIES = [
        'easy' => [
            'label' => 'Einfach',
            'prompt' => 'easy: simple SELECT queries with WHERE, ORDER BY, and basic filtering. Avoid JOIN, GROUP BY, HAVING, and subqueries.',
        ],
        'medium' => [
            'label' => 'Mittel',
            'prompt' => 'medium: JOINs, GROUP BY, and aggregate functions. Use two or three related tables.',
        ],
        'hard' => [
            'label' => 'Schwer',
            'prompt' => 'hard: multiple JOINs, HAVING, subqueries, and more complex conditions. Use at least three related tables.',
        ],
    ];

    private ?string $dbName = null;

    public function __construct(
        private readonly DatabaseManager $dbManager,
        private readonly SqlExerciseGenerator $sqlExerciseGenerator,
    ) {}

    public function index()
    {
        return view('it.sql-exercise.select-difficulty', [
            'difficulties' => $this->difficultyOptions(),
        ]);
    }

    public function generate(string $difficulty, AiResponseProvider $ai)
    {
        abort_if(! array_key_exists($difficulty, self::DIFFICULTIES), 404, 'Unbekannter Schwierigkeitsgrad.');

        $payload = [
            'request_id' => (string) Str::uuid(),
            'difficulty' => $difficulty,
            'language' => 'de',
            'extra_context' => implode("\n", [
                'Target audience: Fachinformatiker Anwendungsentwicklung (IHK AP2).',
                'Selected difficulty: '.$difficulty.' ('.self::DIFFICULTIES[$difficulty]['label'].').',
                'Difficulty definition: '.self::DIFFICULTIES[$difficulty]['prompt'],
                'The generated SQL task must match exactly the selected difficulty.',
                'Keep task text in German.',
                'Use normalized, coherent sample data with at least 3 rows per table.',
                'Use MySQL-compatible CREATE TABLE and INSERT statements.',
                'The solution must contain exactly one SELECT query.',
                'Suggested contexts: online shop, school, training company, support tickets, or inventory.',
            ]),
        ];

        $mysqlstatement = null;

        try {
            $generated = $this->sqlExerciseGenerator->generate($ai, $payload, 'sql');

            $this->dbName = $generated['database'];
            session(['sql_temp_db' => $this->dbName]);

            $task = $generated['task'];
            $title = $generated['title'] ?? null;
            $source = (string) ($generated['source'] ?? 'generated');
            $mysqlstatement = $generated['mysqlstatement'];
            $solution = $generated['solution'];

            $category = Category::where('name', 'SQL')->firstOrFail();

            $exercise = Exercise::create([
                'user_id' => auth()->id(),
                'category_id' => $category->id,
                'title' => $title,
                'difficulty' => $difficulty,
                'source' => $source,
                'prompt' => json_encode($generated['payload'], JSON_UNESCAPED_UNICODE),
                'generated_task' => $task,
                'solution' => $solution,
            ]);

            session(['sql_exercise_id' => $exercise->id]);

            $tables = $this->getAllTables();

            return view('it.sql-exercise.index', [
                'tables' => $tables,
                'task' => $task,
                'solution' => $solution,
                'mysqlstatement' => $mysqlstatement,
                'difficultyLabel' => self::DIFFICULTIES[$difficulty]['label'],
                'sourceLabel' => $this->sourceLabel($source),
            ]);
        } catch (Throwable $e) {
            Log::channel('sql_exercise')->error('SQL exercise flow failed.', [
                'user_id' => auth()->id(),
                'request_id' => $payload['request_id'],
                'database' => $this->dbName,
                'difficulty' => $payload['difficulty'],
                'generated_sql' => $mysqlstatement,
                'exception' => $e::class,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function getAllTables(): array
    {
        return $this->dbManager->getTables($this->currentDatabaseName());
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

        return view('it.sql-exercise.index', [
            'tables' => $tables,
            'task' => $exercise->generated_task,
            'solution' => $exercise->solution,
            'difficultyLabel' => $this->difficultyLabel($exercise->difficulty),
            'sourceLabel' => $this->sourceLabel($exercise->source),
            'result' => $result,
            'solutionResult' => $solutionResult,
            'userSql' => $sql,
        ]);
    }

    private function currentDatabaseName(): string
    {
        $dbName = session('sql_temp_db');

        abort_if(! $dbName, 409, 'Keine temporäre Datenbank in der Session gefunden. Bitte starte die SQL-Übung neu.');

        return $dbName;
    }

    private function currentExercise(): Exercise
    {
        $exerciseId = session('sql_exercise_id');

        abort_if(! $exerciseId, 409, 'Keine SQL-Übung in der Session gefunden. Bitte starte die SQL-Übung neu.');

        return Exercise::query()
            ->whereKey($exerciseId)
            ->where('user_id', auth()->id())
            ->firstOrFail();
    }

    private function difficultyOptions(): array
    {
        return array_map(
            fn (string $value, array $config) => [
                'value' => $value,
                'label' => $config['label'],
            ],
            array_keys(self::DIFFICULTIES),
            self::DIFFICULTIES,
        );
    }

    private function difficultyLabel(?string $difficulty): ?string
    {
        if ($difficulty === null || ! array_key_exists($difficulty, self::DIFFICULTIES)) {
            return null;
        }

        return self::DIFFICULTIES[$difficulty]['label'];
    }

    private function sourceLabel(?string $source): ?string
    {
        return match ($source) {
            'generated' => 'KI-generiert',
            'fixture' => 'Beispielaufgabe',
            default => null,
        };
    }
}
