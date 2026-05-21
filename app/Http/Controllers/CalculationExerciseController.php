<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Exercise;
use App\Services\AI\AiResponseProvider;
use App\Services\CalculationExerciseTopicCatalog;
use App\Services\SolutionEvaluator;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CalculationExerciseController extends Controller
{
    public function __construct(
        private readonly CalculationExerciseTopicCatalog $topics,
        private readonly SolutionEvaluator $solutionEvaluator,
    ) {}

    public function overview()
    {
        return view('it.calculation-exercises.index', [
            'topics' => $this->topics->all(),
        ]);
    }

    public function generate(string $topic, AiResponseProvider $ai)
    {
        $selectedTopic = $this->topics->find($topic);

        abort_if(! $selectedTopic, 404, 'Unbekanntes Rechenthema.');

        $payload = [
            'request_id' => (string) Str::uuid(),
            'difficulty' => request()->get('difficulty', 'medium'),
            'language' => 'de',
            'topic_slug' => $selectedTopic['slug'],
            'topic' => $selectedTopic['label'],
            'extra_context' => 'Erzeuge genau eine Rechenaufgabe zum angegebenen Thema. Aufgabe, erwartetes Ergebnis und Musterlösung müssen zum Thema passen.',
        ];

        $data = $ai->getCalculation($payload, 'calculation');
        $title = (string) $data['title'];
        $task = (string) $data['task'];
        $expectedResult = (string) $data['expected_result'];
        $expectedUnit = (string) $data['expected_unit'];
        $sampleSolution = (string) $data['sample_solution'];
        $source = (string) ($data['source'] ?? 'generated');

        $category = Category::firstOrCreate(['name' => 'Calculation']);

        $exercise = Exercise::create([
            'user_id' => auth()->id(),
            'category_id' => $category->id,
            'title' => $title,
            'difficulty' => $payload['difficulty'],
            'source' => $source,
            'prompt' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'generated_task' => $task,
            'solution' => $expectedResult,
            'expected_unit' => $expectedUnit,
            'sample_solution' => $sampleSolution,
        ]);

        session(['calculation_exercise_id' => $exercise->id]);

        return view('it.calculation-exercises.index', [
            'topics' => $this->topics->all(),
            'selectedTopic' => $selectedTopic,
            'title' => $title,
            'generated_task' => $task,
            'expected_unit' => $expectedUnit,
            'sourceLabel' => $this->sourceLabel($source),
        ]);
    }

    public function check(Request $request)
    {
        $validated = $request->validate([
            'user_solution' => ['required', 'string', 'max:255'],
        ]);

        $exercise = $this->currentExercise();
        $isCorrect = $this->solutionEvaluator->compareNumeric(
            $validated['user_solution'],
            $exercise->solution,
        );

        return view('it.calculation-exercises.index', [
            'topics' => $this->topics->all(),
            'is_correct' => $isCorrect,
            'title' => $exercise->title,
            'solution' => $exercise->solution,
            'expected_unit' => $exercise->expected_unit,
            'sample_solution' => $exercise->sample_solution,
            'user_solution' => $validated['user_solution'],
            'generated_task' => $exercise->generated_task,
            'selectedTopic' => $this->selectedTopicFromExercise($exercise),
            'sourceLabel' => $this->sourceLabel($exercise->source),
        ]);
    }

    private function currentExercise(): Exercise
    {
        $exerciseId = session('calculation_exercise_id');

        abort_if(! $exerciseId, 409, 'Keine Rechenaufgabe in der Session gefunden. Bitte starte eine neue Aufgabe.');

        return Exercise::query()
            ->whereKey($exerciseId)
            ->where('user_id', auth()->id())
            ->firstOrFail();
    }

    private function selectedTopicFromExercise(Exercise $exercise): ?array
    {
        $payload = json_decode($exercise->prompt, true);

        if (! is_array($payload) || ! isset($payload['topic'])) {
            return null;
        }

        foreach ($this->topics->all() as $topic) {
            if ($topic['label'] === $payload['topic']) {
                return $topic;
            }
        }

        return null;
    }

    private function sourceLabel(?string $source): ?string
    {
        return match ($source) {
            'generated' => 'KI-generiert',
            'fixture' => 'Beispielaufgabe',
            default => null,
        };
    }
}
