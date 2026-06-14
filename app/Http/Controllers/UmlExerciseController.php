<?php

namespace App\Http\Controllers;

use App\Contracts\ExerciseProvider;
use App\Exceptions\ExerciseSourceException;
use App\Models\Exercise;
use App\Services\PlantUmlInput;
use App\Services\PlantUmlService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class UmlExerciseController extends Controller
{
    private const DIFFICULTIES = [
        'easy' => 'Einfach',
        'medium' => 'Mittel',
        'hard' => 'Schwer',
    ];

    public function __construct(
        private readonly ExerciseProvider $exerciseProvider,
        private readonly PlantUmlInput $plantUmlInput,
    ) {}

    public function create(Request $request)
    {
        $difficulty = $this->validatedDifficulty($request->query('difficulty', 'medium'));
        $diagramType = $this->validatedDiagramType($request->query('diagram_type'));
        $filterByDifficulty = $diagramType === null || $request->query->has('difficulty');

        try {
            $exercise = $this->selectExercise($difficulty, $diagramType, $filterByDifficulty);
            $input = (string) ($exercise['starter_plantuml'] ?? '');

            return view('it.uml-exercise.index', $this->viewData($exercise, $input));
        } catch (ExerciseSourceException $e) {
            return view('it.uml-exercise.index', $this->viewData(
                null,
                '',
                null,
                $e->getMessage(),
                $difficulty,
                $diagramType,
            ));
        }
    }

    public function render(Request $request, PlantUmlService $plantUmlService)
    {
        $validated = $request->validate([
            'exercise_id' => ['required', 'integer'],
            'uml_text' => ['required', 'string', 'max:10000'],
        ]);

        $raw = $validated['uml_text'];
        $exercise = $this->exerciseData((int) $validated['exercise_id']);

        if ($exercise === null) {
            return view('it.uml-exercise.index', $this->viewData(
                null,
                $raw,
                null,
                'Die ausgewaehlte UML-Aufgabe ist nicht mehr verfuegbar.',
            ));
        }

        try {
            $plantUml = $this->plantUmlInput->wrap($raw);
            $dataUrl = $this->renderDataUrl($plantUmlService, $plantUml);

            return view('it.uml-exercise.index', $this->viewData($exercise, $raw, $dataUrl));
        } catch (Throwable $e) {
            Log::warning('UML rendering failed.', [
                'user_id' => auth()->id(),
                'exercise_id' => $exercise['database_id'],
                'exception' => $e::class,
                'error' => $e->getMessage(),
            ]);

            return view('it.uml-exercise.index', $this->viewData(
                $exercise,
                $raw,
                null,
                'Das UML-Diagramm konnte nicht gerendert werden. Bitte pruefe deine Eingabe oder versuche es erneut.',
            ));
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function selectExercise(
        string $difficulty,
        ?string $diagramType,
        bool $filterByDifficulty,
    ): array {
        if ($diagramType === null || $filterByDifficulty) {
            return $this->exerciseProvider->random(
                'uml',
                $difficulty,
                $diagramType === null ? [] : ['diagram_type' => $diagramType],
            );
        }

        $matching = [];

        foreach (array_keys(self::DIFFICULTIES) as $candidateDifficulty) {
            foreach ($this->exerciseProvider->all('uml', $candidateDifficulty) as $exercise) {
                if ($exercise['diagram_type'] === $diagramType) {
                    $matching[] = $exercise;
                }
            }
        }

        if ($matching === []) {
            throw new ExerciseSourceException(
                "Keine UML-Aufgabe fuer den Diagrammtyp {$diagramType} gefunden.",
            );
        }

        return $matching[random_int(0, count($matching) - 1)];
    }

    /**
     * @param  array<string, mixed>|null  $exercise
     * @return array<string, mixed>
     */
    private function viewData(
        ?array $exercise,
        string $input,
        ?string $imageDataUrl = null,
        ?string $error = null,
        ?string $difficulty = null,
        ?string $diagramType = null,
    ): array {
        $difficulty = is_array($exercise)
            ? (string) ($exercise['difficulty'] ?? 'medium')
            : ($difficulty ?? 'medium');
        $diagramType = is_array($exercise)
            ? ($exercise['diagram_type'] ?? null)
            : $diagramType;
        $diagramTypes = config('exercises.uml.diagram_types', []);

        return [
            'input' => $input,
            'imageDataUrl' => $imageDataUrl,
            'error' => $error,
            'exercise' => $exercise,
            'difficulties' => collect(self::DIFFICULTIES)
                ->map(fn (string $label, string $value): array => compact('value', 'label'))
                ->values()
                ->all(),
            'diagramTypes' => collect($diagramTypes)
                ->map(fn (string $label, string $value): array => compact('value', 'label'))
                ->values()
                ->all(),
            'selectedDifficulty' => $difficulty,
            'selectedDiagramType' => $diagramType,
            'difficultyLabel' => self::DIFFICULTIES[$difficulty] ?? null,
            'diagramTypeLabel' => $diagramType !== null ? ($diagramTypes[$diagramType] ?? null) : null,
            'sourceLabel' => is_array($exercise) ? 'Beispielaufgabe' : null,
        ];
    }

    private function validatedDifficulty(mixed $difficulty): string
    {
        $difficulty = (string) $difficulty;
        abort_if(! array_key_exists($difficulty, self::DIFFICULTIES), 404, 'Unbekannter Schwierigkeitsgrad.');

        return $difficulty;
    }

    private function validatedDiagramType(mixed $diagramType): ?string
    {
        if ($diagramType === null || $diagramType === '') {
            return null;
        }

        $diagramType = (string) $diagramType;
        abort_if(
            ! array_key_exists($diagramType, config('exercises.uml.diagram_types', [])),
            404,
            'Unbekannter UML-Diagrammtyp.',
        );

        return $diagramType;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function exerciseData(int $exerciseId): ?array
    {
        $exercise = Exercise::query()
            ->with('umlDetail')
            ->whereKey($exerciseId)
            ->where('type', 'uml')
            ->where('status', 'published')
            ->first();

        if ($exercise === null || $exercise->umlDetail === null) {
            return null;
        }

        return [
            'database_id' => $exercise->id,
            'id' => $exercise->external_id,
            'type' => $exercise->type,
            'difficulty' => $exercise->difficulty,
            'topic' => $exercise->topic,
            'title' => $exercise->title,
            'scenario' => $exercise->umlDetail->scenario,
            'requirements' => $exercise->umlDetail->requirements ?? [],
            'task' => $exercise->task,
            'explanation' => $exercise->explanation,
            'hints' => $exercise->hints ?? [],
            'source' => $exercise->source,
            'diagram_type' => $exercise->umlDetail->diagram_type,
            'starter_plantuml' => $exercise->umlDetail->starter_plantuml,
            'solution_plantuml' => $exercise->umlDetail->solution_plantuml,
            'expected_elements' => $exercise->umlDetail->expected_elements ?? [],
        ];
    }

    private function renderDataUrl(PlantUmlService $plantUmlService, string $plantUml): string
    {
        $pngPath = $plantUmlService->generate($plantUml);

        try {
            $contents = file_get_contents($pngPath);

            if ($contents === false) {
                throw new RuntimeException('Das generierte UML-Bild konnte nicht gelesen werden.');
            }

            return 'data:image/png;base64,'.base64_encode($contents);
        } finally {
            if (is_file($pngPath)) {
                unlink($pngPath);
            }
        }
    }
}
