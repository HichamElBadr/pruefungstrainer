<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Exercise;
use App\Services\AI\JsonWrapper;
use App\Services\DatabaseManager;
use App\Services\QueryHandler;
use App\Services\OllamaService;
use Illuminate\Container\Attributes\Database;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Termwind\Components\Ol;

use function Laravel\Prompts\table;

class SqlExerciseController extends Controller
{
    private $pdo;
    private $ollama;
    private $task;
    private $dbManager;
    private $dbName;
    private $jsonWrapper;

    /**
     * Injects all required services and performs initial housekeeping.
     *
     * Note: Old temporary databases are cleaned up on construction.
     *
     * @param DatabaseManager $dbManager Manages temporary DB creation/cleanup and connections.
     * @param JsonWrapper     $jsonWrapper Robust JSON parser/validator for AI responses.
     * @param OllamaService   $ollamaService Client for the local AI (Ollama).
     */
    public function __construct(DatabaseManager $dbManager, JsonWrapper $jsonWrapper, OllamaService $ollamaService)
    {
        $this->ollama = $ollamaService;
        $this->jsonWrapper = $jsonWrapper;
        $this->dbManager = $dbManager;

        //TODO umlagern in einer Methode.
        $this->dbManager->cleanOldDatabases();
    }

    /**
     * Calls the AI service with a given prompt and stores the raw response.
     *
     * @param string $prompt Prompt instructing the AI to produce a SQL exercise JSON.
     * @return string The raw AI response (expected to be JSON).
     */
    public function generateTask($prompt)
    {
        $this->task = $this->ollama->generate($prompt);
        return $this->task;
    }

    /**
     * Generates a new SQL exercise, provisions a temporary database with tables/data,
     * persists the exercise meta, and renders the exercise view including table previews.
     *
     * Steps:
     *  1) Create a per-session temporary database and store its name in the session.
     *  2) Ask the AI for a strict JSON payload: {"task","mysqlstatement","solution"}.
     *  3) Parse JSON safely; on failure, dump the error and raw payload.
     *  4) Execute the provided DDL/DML on the temporary DB.
     *  5) Persist exercise (category=SQL) and render the page with table snapshots.
     *
     * @return \Illuminate\Contracts\View\View The exercise screen with schema preview and task text.
     *
     * @throws \Throwable On JSON parsing issues or DB provisioning failures.
     */
    public function index()
    {
        $this->dbName = $this->dbManager->createTemporaryDatabase();
        session(['sql_temp_db' => $this->dbName]);

        $prompt = 'Erstelle bitte eine SQL-Aufgabe für einen Fachinformatiker mit mindestens 5 Datensätzen pro Tabelle und es sollen mindestens 4 verschiedene Tabellen exisitieren.
        Die Tabellen sollen alle auch in der dritten Normalform sein!
        NUR im JSON-Format, exakt folgendes Schema:
        {
        "task": "Hier kommt die Beschreibung der Aufgabe",
        "mysqlstatement": "Hier kommen alle CREATE TABLE und INSERT INTO Befehle, die die Beispieltabellen befüllen",
        "solution": "Hier kommt die SQL-Lösung als SELECT-Statement"
        }

        WICHTIG:
        1. Keine Markdown-Syntax, keine Listen, keine Backticks.
        2. Verwende nur doppelte Anführungszeichen für Strings.
        3. JSON muss gültig sein, es darf kein zusätzliches Text außerhalb der JSON-Struktur kommen.
        4. Die Aufgabe soll nur SELECT-Abfragen enthalten, möglichst mit JOINs zwischen Tabellen.
        5. Halte alles in einer einzigen JSON-Struktur.

        Beispielausgabe, die gültig sein muss:

        {
        "task": "Erstelle eine SQL-Abfrage, die alle Bestellungen mit Kundenname und Produktname anzeigt.",
        "mysqlstatement": "CREATE TABLE kunden (...); CREATE TABLE produkte (...); INSERT INTO kunden ...; INSERT INTO produkte ...;",
        "solution": "SELECT ... FROM kunden JOIN produkte ON ..."
        }';

        $generated_task = $this->generateTask($prompt);

        // Parse JSON with own Wrapper
        try {
            $data = $this->jsonWrapper->parse($generated_task);
            $task = (string)($data['task'] ?? '');
            $mysqlstatement = (string)($data['mysqlstatement'] ?? '');
            $solution = (string)($data['solution'] ?? '');
        } catch (\Exception $e) {
            dd($e->getMessage(), $generated_task);
        }

        //Create new exercise table
        $this->dbManager->createMySqlExercise($mysqlstatement, $this->dbName);
        $category = Category::where('name', 'SQL')->firstOrFail();

        Exercise::create([
            'category_id' => $category->id,
            'prompt' => $prompt,
            'generated_task' => $task ?? $generated_task,
            'solution' => $solution ?? null
        ]);

        $tables = $this->getAllTables();

        return view('it.sql-exercise.index', [
            'tables' => $tables,
            'task' => $task ?? $generated_task,
            'solution' => $solution ?? null,
            'mysqlstatement' => $mysqlstatement ?? null
        ]);
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
