<?php

namespace App\Http\Controllers;

use App\Contracts\ExerciseProvider;
use App\Exceptions\ExerciseSourceException;
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
            Log::warning('Calculation catalog exercise could not be loaded.', [
                'topic' => $selectedTopic['slug'],
                'difficulty' => $difficulty,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->route('calculation-exercises.index', ['difficulty' => $difficulty])
                ->withErrors(['exercise_source' => $e->getMessage()]);
        }

        session(['calculation_exercise_id' => $data['database_id']]);

        return view('it.calculation-exercises.index', array_merge(
            $this->baseViewData($difficulty),
            [
                'selectedTopic' => $selectedTopic,
                'title' => $data['title'],
                'task' => $data['task'],
                'unit' => $data['unit'],
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
        $detail = $exercise->calculationDetail;
        abort_if($detail === null, 500, 'Rechenaufgabendetails fehlen.');
        $difficulty = $exercise->difficulty ?? 'medium';
        $isCorrect = $this->solutionEvaluator->compareNumeric(
            $validated['user_solution'],
            $detail->expected_value,
            (float) ($detail->tolerance ?? 0),
        );
        $sampleSolution = trim(
            ($detail->solution_steps ?? '')
            .($exercise->explanation ? "\n\n".$exercise->explanation : ''),
        );

        return view('it.calculation-exercises.index', array_merge(
            $this->baseViewData($difficulty),
            [
                'is_correct' => $isCorrect,
                'title' => $exercise->title,
                'expected_value' => $this->displayDecimal($detail->expected_value),
                'unit' => $detail->unit,
                'solution_steps' => $sampleSolution,
                'user_solution' => $validated['user_solution'],
                'task' => $exercise->task,
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
            ->with('calculationDetail')
            ->whereKey($exerciseId)
            ->where('type', 'calculation')
            ->where('status', 'published')
            ->firstOrFail();
    }

    private function selectedTopicFromExercise(Exercise $exercise): ?array
    {
        if ($exercise->topic === null) {
            return null;
        }

        foreach ($this->topics->all() as $topic) {
            if ($topic['slug'] === $exercise->topic || $topic['label'] === $exercise->topic) {
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

    private function displayDecimal(mixed $value): string
    {
        $normalized = rtrim(rtrim((string) $value, '0'), '.');

        return $normalized === '' ? '0' : $normalized;
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
