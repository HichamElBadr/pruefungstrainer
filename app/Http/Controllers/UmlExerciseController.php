<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\AI\JsonWrapper;
use App\Services\OllamaService;
use App\Models\Category;
use App\Models\Exercise;

use Illuminate\Http\Request;

class UmlExerciseController extends Controller
{
    public function __construct(
        private OllamaService $ollama_service,
        private JsonWrapper $json_wrapper
    ) {}
    public function index()
    {
        $prompt = "
            Erstelle bitte **EINE einzige** Rechnungsaufgabe mit Musterlösung **im JSON Format**. 
            Die Ausgabe darf **nichts anderes enthalten** als das folgende Schema exakt:

            {
                'task': 'Hier kommt die Beschreibung der Aufgabe ALS Beispiel eine Mulitiplikations Aufgaben'
            }

            WICHTIG:
            - Gib nur **genau ein JSON-Objekt** zurück.
            - Kein zusätzliches Feld wie 'solution', 'description', 'steps' usw.
            - Keine Einleitungen, Erklärungen oder Text außerhalb des JSON.
            - Verwende **doppelte Anführungszeichen** wie oben, kein anderes Format.
            ";

        $output = $this->ollama_service->generate($prompt);

        // Parse JSON with own Wrapper
        try {
            $data = $this->json_wrapper->parse($output);
            $generated_task = (string)($data['task'] ?? '');
        } catch (\Exception $e) {
            dd($e->getMessage(), $output);
        }

        $category = Category::where('name', 'UML')->firstOrFail();

        Exercise::create([
            'category_id' => $category->id,
            'prompt' => $prompt,
            'generated_task' => $generated_task ?? $output,
            'solution' => $solution ?? null
        ]);

        return view('it.uml-exercise.index', [
            'generated_task' => $generated_task,
        ]);
    }
}
