<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Exercise;
use App\Services\AI\AiGatewayClient;
use App\Services\AI\AiResponseProvider;
use App\Services\DatabaseManager;
use App\Services\QueryHandler;

use Illuminate\Container\Attributes\Database;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Termwind\Components\Ol;

use function Laravel\Prompts\table;

class SqlExerciseController extends Controller
{
    private $dbManager;
    private $dbName;

    /**
     *
     * Note: Old temporary databases are cleaned up on construction.
     *
     * @param DatabaseManager $dbManager Manages temporary DB creation/cleanup and connections.
     */
    public function __construct(DatabaseManager $dbManager)
    {
        $this->dbManager = $dbManager;

        //TODO umlagern in einer Methode.
        $this->dbManager->cleanOldDatabases();
    }

    public function index(AiResponseProvider $ai)
    {
        $this->dbName = $this->dbManager->createTemporaryDatabase();
        session(['sql_temp_db' => $this->dbName]);

        $payload = [
            'request_id' => (string) Str::uuid(),
            'difficulty' => request()->get('difficulty', 'medium'),
            'language' => 'de',
            'extra_context' => implode("\n", [
                "Zielgruppe: Fachinformatiker Anwendungsentwicklung (IHK AP2).",
                "Mindestens 3 Tabellen, je mind. 3 Datensätze.",
                "3NF: Vorname/Nachname getrennt, keine redundanten Felder, saubere FK.",
                "Nur SELECT in der solution, idealerweise mehrere JOINs.",
                "Kontext: Online-Shop oder Hochschule."
            ]),
            // 'topic' => 'JOIN', // optional
        ];

        $data = $ai->getSql($payload, 'sql');

        $task = (string) $data['task'];
        $mysqlstatement = (string) $data['mysqlstatement'];
        $solution = (string) $data['solution'];
        $meta = $data['meta'] ?? null;

        $this->dbManager->createMySqlExercise($mysqlstatement, $this->dbName);

        $category = Category::where('name', 'SQL')->firstOrFail();

        Exercise::create([
            'category_id' => $category->id,
            'prompt' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'generated_task' => $task,
            'solution' => $solution,
            // optional: meta speichern falls Spalte vorhanden
            // 'meta' => json_encode($meta, JSON_UNESCAPED_UNICODE),
        ]);

        $tables = $this->getAllTables();

        return view('it.sql-exercise.index', compact('tables', 'task', 'solution', 'mysqlstatement'));
    }

    /**
     * Returns an associative array of all tables in the current temporary database,
     * with each key being the table name and each value being an array of rows.
     *
     * @return array<string, array<int, array<string,mixed>>> Map: tableName => rows.
     *
     * @throws \PDOException If querying table metadata or data fails.
     */
    private function getAllTables(): array
    {
        $dbName = session('sql_temp_db');

        if (!$dbName) {
            dd('Keine temporäre Datenbank in der Session gefunden. Bitte Seite neu laden.');
        }

        // Neue Verbindung zur richtigen DB aufbauen
        $pdo = $this->dbManager->connectToDatabase($dbName);

        $stmt = $pdo->query("SHOW TABLES");
        $tables = [];

        $tableNames = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        foreach ($tableNames as $table) {
            $data = $pdo->query("SELECT * FROM `$table`")->fetchAll(\PDO::FETCH_ASSOC);
            $tables[$table] = $data;
        }

        return $tables;
    }

    /**
     * Executes a user-provided SQL query against the session's temporary database
     * and renders the exercise view including the query result.
     *
     * Also reloads the latest stored exercise prompt and solution for display.
     *
     * @param Request $request The HTTP request containing the SQL string in 'sql_input'.
     * @return \Illuminate\Contracts\View\View The exercise view with execution result and context.
     *
     * @throws \PDOException If executing the user query fails.
     */
    public function executeUserQuery(Request $request)
    {
        $pdo = $this->dbManager->connectToDatabase(session('sql_temp_db'));
        $tables = $this->getAllTables();
        $sql = $request->input('sql_input');
        $result = QueryHandler::executeUserQuery($pdo, $sql);
        $lastTask = Exercise::latest()->value('generated_task');
        $solution = Exercise::latest()->value('solution');

        return view('it.sql-exercise.index', [
            'tables' => $tables,
            'task' => $lastTask,
            'solution' => $solution,
            'result' => $result,
            'userSql' => $sql,
        ]);
    }
}
