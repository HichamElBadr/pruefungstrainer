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

        $prompt = '
Erstelle bitte eine anspruchsvolle SQL-Aufgabe für angehende Fachinformatiker für Anwendungsentwicklung.

Die Aufgabe soll:
- mindestens 3 verschiedene Tabellen enthalten (z. B. kunden, bestellungen, produkte, kategorie, mitarbeiter usw.)
- mindestens 3 Datensätze pro Tabelle haben,
- alle Tabellen in der **dritten Normalform (3NF)** sein. Das bedeutet u. a.:
  - Vorname und Nachname müssen getrennt gespeichert werden,
  - keine redundanten oder abgeleiteten Attribute,
  - Fremdschlüssel müssen korrekt genutzt werden.
- nur SELECT-Abfragen enthalten, idealerweise mit mehreren JOINs (z. B. INNER JOIN, LEFT JOIN)
- einen realistischen betrieblichen Kontext haben (z. B. ein Online-Shop, eine Firma, eine Schule, etc.)
- in der Bestellung Tabellen sollen korrekt alle passenden Fremdschlüsseln drinnen sein.

Antworte **ausschließlich im gültigen JSON-Format** nach folgendem Schema:

{
  "task": "Hier steht die Aufgabenbeschreibung in einem Satz oder kurzen Absatz.",
  "mysqlstatement": "Hier kommen alle CREATE TABLE und INSERT INTO Befehle, die die Tabellen und Beispielwerte anlegen.",
  "solution": "Hier kommt die korrekte SQL-Abfrage (nur SELECT, keine anderen Befehle)."
}

WICHTIG:
1. Keine Markdown-Syntax, keine Listen, keine Backticks.
2. Nur doppelte Anführungszeichen für Strings.
3. Keine Kommentare oder Text außerhalb der JSON-Struktur.
4. Der JSON-String muss **vollständig gültig und parsebar** sein.
5. Die Abfrage soll inhaltlich sinnvoll sein und JOINs zwischen mehreren Tabellen enthalten.

Beispielausgabe (nur zur Orientierung):

{
  "task": "Erstelle eine SQL-Abfrage, die alle Bestellungen mit Kundenvorname, Nachname, Produktname und Kategoriebezeichnung anzeigt.",
  "mysqlstatement": "CREATE TABLE kunden (...); CREATE TABLE produkte (...); CREATE TABLE kategorien (...); CREATE TABLE bestellungen (...); INSERT INTO ...;",
  "solution": "SELECT k.vorname, k.nachname, p.name AS produktname, kat.name AS kategorie FROM bestellungen b JOIN kunden k ON b.kunden_id = k.id JOIN produkte p ON b.produkt_id = p.id JOIN kategorien kat ON p.kategorie_id = kat.id;"
}
';


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
