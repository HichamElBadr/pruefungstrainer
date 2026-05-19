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
    private ?string $dbName = null;

    public function __construct(
        private readonly DatabaseManager $dbManager,
        private readonly SqlExerciseGenerator $sqlExerciseGenerator,
    ) {}

    public function index(AiResponseProvider $ai)
    {
        $payload = [
            'request_id' => (string) Str::uuid(),
            'difficulty' => request()->get('difficulty', 'medium'),
            'language' => 'de',
            'extra_context' => implode("\n", [
                'Zielgruppe: Fachinformatiker Anwendungsentwicklung (IHK AP2).',
                'Mindestens 3 Tabellen, je mind. 3 Datensätze.',
                '3NF: Vorname/Nachname getrennt, keine redundanten Felder, saubere FK.',
                'Nur SELECT in der solution, idealerweise mehrere JOINs.',
                'Kontext: Online-Shop oder Hochschule.',
            ]),
        ];

        $mysqlstatement = null;

        try {
            $generated = $this->sqlExerciseGenerator->generate($ai, $payload, 'sql');

            $this->dbName = $generated['database'];
            session(['sql_temp_db' => $this->dbName]);

            $task = $generated['task'];
            $mysqlstatement = $generated['mysqlstatement'];
            $solution = $generated['solution'];

            $category = Category::where('name', 'SQL')->firstOrFail();

            $exercise = Exercise::create([
                'user_id' => auth()->id(),
                'category_id' => $category->id,
                'prompt' => json_encode($generated['payload'], JSON_UNESCAPED_UNICODE),
                'generated_task' => $task,
                'solution' => $solution,
            ]);

            session(['sql_exercise_id' => $exercise->id]);

            $tables = $this->getAllTables();

            return view('it.sql-exercise.index', compact('tables', 'task', 'solution', 'mysqlstatement'));
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

        return view('it.sql-exercise.index', [
            'tables' => $tables,
            'task' => $exercise->generated_task,
            'solution' => $exercise->solution,
            'result' => $result,
            'userSql' => $sql,
        ]);
    }

    private function currentDatabaseName(): string
    {
        $dbName = session('sql_temp_db');

        abort_if(!$dbName, 409, 'Keine temporäre Datenbank in der Session gefunden. Bitte starte die SQL-Übung neu.');

        return $dbName;
    }

    private function currentExercise(): Exercise
    {
        $exerciseId = session('sql_exercise_id');

        abort_if(!$exerciseId, 409, 'Keine SQL-Übung in der Session gefunden. Bitte starte die SQL-Übung neu.');

        return Exercise::query()
            ->whereKey($exerciseId)
            ->where('user_id', auth()->id())
            ->firstOrFail();
    }
}
