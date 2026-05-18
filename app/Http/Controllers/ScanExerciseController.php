<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Exercise;
use App\Services\AI\AiResponseProvider;
use App\Services\SolutionEvaluator;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ScanExerciseController extends Controller
{
    public function __construct(private readonly SolutionEvaluator $solutionEvaluator) {}

    public function index(AiResponseProvider $ai)
    {
        $payload = [
            'request_id' => (string) Str::uuid(),
            'difficulty' => request()->get('difficulty', 'medium'),
            'language' => 'de',
            'extra_context' => 'Erzeuge genau eine kurze Rechenaufgabe. Die Lösung muss nur eine Zahl ohne Einheit sein.',
        ];

        $data = $ai->getScan($payload, 'scan');
        $task = (string) $data['task'];
        $solution = (string) $data['solution'];

        $category = Category::where('name', 'Scan')->firstOrFail();

        $exercise = Exercise::create([
            'user_id' => auth()->id(),
            'category_id' => $category->id,
            'prompt' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'generated_task' => $task,
            'solution' => $solution,
        ]);

        session(['scan_exercise_id' => $exercise->id]);

        return view('it.scan-exercise.index', [
            'generated_task' => $task,
            'solution' => $solution,
        ]);
    }

    public function check(Request $request)
    {
        $validated = $request->validate([
            'user_solution' => ['required', 'numeric'],
        ]);

        $exercise = $this->currentExercise();
        $isCorrect = $this->solutionEvaluator->compareNumeric(
            $validated['user_solution'],
            $exercise->solution,
        );

        return view('it.scan-exercise.index', [
            'is_correct' => $isCorrect,
            'solution' => $exercise->solution,
            'generated_task' => $exercise->generated_task,
        ]);
    }

    private function currentExercise(): Exercise
    {
        $exerciseId = session('scan_exercise_id');

        abort_if(!$exerciseId, 409, 'Keine Scan-Übung in der Session gefunden. Bitte starte die Übung neu.');

        return Exercise::query()
            ->whereKey($exerciseId)
            ->where('user_id', auth()->id())
            ->firstOrFail();
    }
}
