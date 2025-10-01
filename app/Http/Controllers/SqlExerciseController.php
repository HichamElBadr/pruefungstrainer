<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Exercise;
use App\Services\DatabaseManager;
use App\Services\QueryHandler;
use App\Services\OllamaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;


use function Laravel\Prompts\table;

class SqlExerciseController extends Controller
{
    private $pdo;
    private $ollama;
    private $task;

    public function __construct()
    {
        $dbManager = new DatabaseManager();
        $dbManager->cleanOldDatabases();
        $dbName = $dbManager->createTemporaryDatabase();
        $this->pdo = $dbManager->connectToDatabase($dbName);
        $this->ollama = new OllamaService();

        // Beispieltabellen einrichten
        $this->pdo->exec("
            CREATE TABLE kunden (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255),
                land VARCHAR(100)
            );
        ");
        $this->pdo->exec("INSERT INTO kunden (name, land) VALUES ('Max Mustermann', 'Deutschland'), ('John Doe', 'USA')");
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
        $tables = $this->getAllTables();
        $prompt = "Erstelle NUR EINE!!! einfache Addition Aufgaben als JSON mit folgenden Feldern:
            {
            task: '...',
            solution: '...'
            } OHNE ERKLÄRUNG UND NUR DIE AUFGABE PLUS LÖSUNG IN JSON FORMAT MEHR NICHT!!!";
        $generated_task = $this->generateTask($prompt);
        $responseText = $generated_task;

        // Regex: alles zwischen ```json und ```
        if (preg_match('/```json(.*?)```/s', $responseText, $matches)) {
            $jsonString = trim($matches[1]);
            $data = json_decode($jsonString, true);

            $task = $data['task'] ?? null;
            $solution = $data['solution'] ?? null;
        }

        Exercise::create([
            'category' => 'sql',
            'prompt' => $prompt,
            'generated_task' => $task ?? $responseText,
            'solution' => $solution ?? null
        ]);

        return view('it.sql-exercise.index', [
            'tables' => $tables,
            'task' => $task ?? $responseText,
            'solution' => $solution ?? null
        ]);
    }

    private function getAllTables()
    {
        $stmt = $this->pdo->query("SHOW TABLES");
        $tables = [];

        while ($row = $stmt->fetchColumn()) {
            $data = $this->pdo->query("SELECT * FROM $row")->fetchAll(\PDO::FETCH_ASSOC);
            $tables[$row] = $data;
        }

        return $tables;
    }

    public function executeUserQuery(Request $request)
    {
        $tables = $this->getAllTables();
        $sql = $request->input('sql_input');
        $result = QueryHandler::executeUserQuery($this->pdo, $sql);
        $lastTask = Exercise::latest()->value('generated_task');

        return view('it.sql-exercise.index', [
            'tables' => $tables,
            'result' => $result,
            'userSql' => $sql,
            'task' => $lastTask
        ]);
    }
}
