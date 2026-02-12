<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Exercise;
use App\Services\AI\AiResponseProvider;
use App\Services\AI\JsonWrapper;
use App\Services\OllamaService;
use App\Services\SolutionEvaluator;
use Illuminate\Http\Request;
use Illuminate\Support\Js;

class ScanExerciseController extends Controller
{

    public function __construct(
        private OllamaService $ollama_service,
        private JsonWrapper $json_wrapper,
        private SolutionEvaluator $solution_evaluator
    ) {}
    /**
     * Display a listing of the resource.
     */
    public function index(AiResponseProvider $ai)
    {
        $prompt = "Erstelle bitte NUR EINE Rechnungsaufgaben mit Musterlösung im JSON Format, folgendes Schema:
        {
            'task': 'Hier kommt die Beschreibung der Aufgabe',
            'solution': 'Hier kommt die Musterlösung aber NUR die Zahl OHNE TEXT ODER EINHEIT'
        }";

        $data = $ai->get($prompt, 'scan');
        // Parse JSON with own Wrapper
        try {
            $task = (string)($data['task'] ?? '');
            $solution = (string)($data['solution'] ?? '');
        } catch (\Exception $e) {
            dd($e->getMessage(), $data);
        }

        $category = Category::where('name', 'Scan')->firstOrFail();

        Exercise::create([
            'category_id' => $category->id,
            'prompt' => $prompt,
            'generated_task' => $task,
            'solution' => $solution ?? null
        ]);

        return view('it.scan-exercise.index', [
            'generated_task' => $task,
            'solution' => $solution
        ]);
    }

    public function check(Request $request)
    {
        $user_input = $request->input('user_solution');
        $solution = Exercise::latest()->value('solution');
        $last_task = Exercise::latest()->value(column: 'generated_task');

        $is_correct = $this->solution_evaluator->compareNumeric($user_input, $solution);

        return view('it.scan-exercise.index', [
            'is_correct' => $is_correct,
            'solution' => $solution,
            'generated_task' => $last_task,
        ]);
    }
}
