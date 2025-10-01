<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
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

    public function __construct(DatabaseManager $dbManager, JsonWrapper $jsonWrapper, OllamaService $ollamaService)
    {
        $this->ollama = $ollamaService;
        $this->jsonWrapper = $jsonWrapper;
        $this->dbManager = $dbManager;

        //TODO umlagern in einer Methode.
        $this->dbManager->cleanOldDatabases();
    }

    // API-method
    public function generateTask($prompt)
    {
        $this->task = $this->ollama->generate($prompt);
        return $this->task;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {


        $this->dbName = $this->dbManager->createTemporaryDatabase();
        session(['sql_temp_db' => $this->dbName]);

        $prompt = 'Erstelle bitte eine SQL-Aufgabe für einen Fachinformatiker NUR im JSON-Format, exakt folgendes Schema:
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
        }'
        ;

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
        $this->dbManager->createExerciseTable($mysqlstatement, $this->dbName);


        Exercise::create([
            'category' => 'sql',
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
