<?php

namespace App\Http\Controllers;

use App\Contracts\ExerciseProvider;
use App\Exceptions\ExerciseSourceException;
use App\Models\Category;
use App\Models\Exercise;
use App\Services\CalculationExerciseTopicCatalog;
use App\Services\SolutionEvaluator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CalculationExerciseController extends Controller
{
    private const DIFFICULTIES = [
        'easy' => 'Einfach',
        'medium' => 'Mittel',
        'hard' => 'Schwer',
    ];

    public function __construct(
        private readonly CalculationExerciseTopicCatalog $topics,
        private readonly SolutionEvaluator $solutionEvaluator,
        private readonly ExerciseProvider $exerciseProvider,
    ) {}

    public function overview(Request $request)
    {
        $difficulty = $this->validatedDifficulty($request->query('difficulty', 'medium'));

        return view('it.calculation-exercises.index', $this->baseViewData($difficulty));
    }

    public function generate(Request $request, string $topic)
    {
        $selectedTopic = $this->topics->find($topic);
        abort_if(! $selectedTopic, 404, 'Unbekanntes Rechenthema.');

        $difficulty = $this->validatedDifficulty($request->input('difficulty', 'medium'));

        try {
            $data = $this->exerciseProvider->random('calculation', $difficulty, [
                'topic' => $selectedTopic['slug'],
            ]);
        } catch (ExerciseSourceException $e) {
            Log::warning('Calculation exercise fixture could not be loaded.', [
                'topic' => $selectedTopic['slug'],
                'difficulty' => $difficulty,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->route('calculation-exercises.index', ['difficulty' => $difficulty])
                ->withErrors(['exercise_source' => $e->getMessage()]);
        }

        $sampleSolution = trim($data['solution_steps']."\n\n".$data['explanation']);
        $category = Category::firstOrCreate(['name' => 'Calculation']);
        $exercise = Exercise::create([
            'user_id' => auth()->id(),
            'category_id' => $category->id,
            'title' => $data['title'],
            'difficulty' => $difficulty,
            'source' => $data['source'],
            'prompt' => json_encode($data, JSON_UNESCAPED_UNICODE),
            'generated_task' => $data['task'],
            'solution' => $data['expected_result'],
            'expected_unit' => $data['unit'],
            'sample_solution' => $sampleSolution,
        ]);

        session(['calculation_exercise_id' => $exercise->id]);

        return view('it.calculation-exercises.index', array_merge(
            $this->baseViewData($difficulty),
            [
                'selectedTopic' => $selectedTopic,
                'title' => $data['title'],
                'generated_task' => $data['task'],
                'expected_unit' => $data['unit'],
                'sourceLabel' => $this->sourceLabel($data['source']),
            ],
        ));
    }

    public function check(Request $request)
    {
        $validated = $request->validate([
            'user_solution' => ['required', 'string', 'max:255'],
        ]);

        $exercise = $this->currentExercise();
        $difficulty = $exercise->difficulty ?? 'medium';
        $isCorrect = $this->solutionEvaluator->compareNumeric(
            $validated['user_solution'],
            $exercise->solution,
        );

        return view('it.calculation-exercises.index', array_merge(
            $this->baseViewData($difficulty),
            [
                'is_correct' => $isCorrect,
                'title' => $exercise->title,
                'solution' => $exercise->solution,
                'expected_unit' => $exercise->expected_unit,
                'sample_solution' => $exercise->sample_solution,
                'user_solution' => $validated['user_solution'],
                'generated_task' => $exercise->generated_task,
                'selectedTopic' => $this->selectedTopicFromExercise($exercise),
                'sourceLabel' => $this->sourceLabel($exercise->source),
            ],
        ));
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
            if ($topic['slug'] === $payload['topic'] || $topic['label'] === $payload['topic']) {
                return $topic;
            }
        }

        return null;
    }

    private function sourceLabel(?string $source): ?string
    {
        return match ($source) {
            'fixture', 'json' => 'Beispielaufgabe',
            default => null,
        };
    }

    private function validatedDifficulty(mixed $difficulty): string
    {
        $difficulty = (string) $difficulty;
        abort_if(! array_key_exists($difficulty, self::DIFFICULTIES), 404, 'Unbekannter Schwierigkeitsgrad.');

        return $difficulty;
    }

    /**
     * @return array<string, mixed>
     */
    private function baseViewData(string $difficulty): array
    {
        return [
            'topics' => $this->topics->all(),
            'difficulties' => collect(self::DIFFICULTIES)
                ->map(fn (string $label, string $value): array => compact('value', 'label'))
                ->values()
                ->all(),
            'selectedDifficulty' => $difficulty,
            'difficultyLabel' => self::DIFFICULTIES[$difficulty],
        ];
    }
}
