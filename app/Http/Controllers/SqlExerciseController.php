<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\DatabaseManager;
use App\Services\QueryHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;


use function Laravel\Prompts\table;

class SqlExerciseController extends Controller
{
    private $pdo;

    public function __construct()
    {
        $dbManager = new DatabaseManager();
        $dbManager->cleanOldDatabases();
        $dbName = $dbManager->createTemporaryDatabase();
        $this->pdo = $dbManager->connectToDatabase($dbName);

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

    public function generateTask()
    {
       $prompt = 'Schreibe eine SQL Aufgabe für Fachinformatiker.';

        try {
            $response = Http::post('http://localhost:11434/api/generate', [
                'model' => 'mistral',
                'prompt' => $prompt,
                'stream' => false,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                dd($data);
                $answer = $data['response'] ?? 'Keine Antwort vom Modell.';
            } else {
                $answer = 'Fehler: ' . $response->status();
            }
        } catch (\Exception $e) {
            $answer = 'Exception: ' . $e->getMessage();
        }
        return response()->json([
            'prompt' => $prompt,
            'answer' => $answer,
        ]);
    }


    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $tables = $this->getAllTables();
        $output = $this->generateTask();
        return view('it.sql-exercise.index', [
            'tables' => $tables
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

        return view('it.sql-exercise.index', [
            'tables' => $tables,
            'result' => $result,
            'userSql' => $sql
        ]);
    }
}
