<?php

namespace App\Http\Controllers;

use App\Contracts\ExerciseProvider;
use App\Exceptions\ExerciseSourceException;
use App\Models\Exercise;
use App\Services\PlantUmlService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
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
    ) {}

    public function create(Request $request)
    {
        $difficulty = $this->validatedDifficulty($request->query('difficulty', 'medium'));

        try {
            $exercise = $this->exerciseProvider->random('uml', $difficulty);
            session(['uml_exercise_id' => $exercise['database_id']]);

            return view('it.uml-exercise.index', $this->viewData($exercise, '', null, null));
        } catch (ExerciseSourceException $e) {
            return view('it.uml-exercise.index', $this->viewData(
                null,
                '',
                null,
                $e->getMessage(),
                $difficulty,
            ));
        }
    }

    public function render(Request $request, PlantUmlService $plantUmlService)
    {
        $validated = $request->validate([
            'uml_text' => ['required', 'string', 'max:10000'],
        ]);

        $raw = $validated['uml_text'];
        $exercise = $this->currentExerciseData();

        if (! is_array($exercise)) {
            $difficulty = $this->validatedDifficulty($request->input('difficulty', 'medium'));

            try {
                $exercise = $this->exerciseProvider->random('uml', $difficulty);
                session(['uml_exercise_id' => $exercise['database_id']]);
            } catch (ExerciseSourceException $e) {
                return view('it.uml-exercise.index', $this->viewData(
                    null,
                    $raw,
                    null,
                    $e->getMessage(),
                    $difficulty,
                ));
            }
        }

        try {
            $plantUml = $this->normalizeToPlantUml($raw);
            $pngPath = $plantUmlService->generate($plantUml);
            $dataUrl = 'data:image/png;base64,'.base64_encode(file_get_contents($pngPath));
            @unlink($pngPath);

            return view('it.uml-exercise.index', $this->viewData($exercise, $raw, $dataUrl, null));
        } catch (InvalidArgumentException $e) {
            return view('it.uml-exercise.index', $this->viewData(
                $exercise,
                $raw,
                null,
                $e->getMessage(),
            ));
        } catch (Throwable $e) {
            Log::warning('UML rendering failed.', [
                'user_id' => auth()->id(),
                'exercise_id' => $exercise['database_id'] ?? null,
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
     * @param  array<string, mixed>|null  $exercise
     * @return array<string, mixed>
     */
    private function viewData(
        ?array $exercise,
        string $input,
        ?string $imageDataUrl,
        ?string $error,
        ?string $difficulty = null,
    ): array {
        $difficulty ??= is_array($exercise) ? ($exercise['difficulty'] ?? 'medium') : 'medium';

        return [
            'input' => $input,
            'imageDataUrl' => $imageDataUrl,
            'error' => $error,
            'exercise' => $exercise,
            'difficulties' => collect(self::DIFFICULTIES)
                ->map(fn (string $label, string $value): array => compact('value', 'label'))
                ->values()
                ->all(),
            'selectedDifficulty' => $difficulty,
            'difficultyLabel' => self::DIFFICULTIES[$difficulty] ?? null,
            'sourceLabel' => is_array($exercise) ? 'Beispielaufgabe' : null,
        ];
    }

    private function validatedDifficulty(mixed $difficulty): string
    {
        $difficulty = (string) $difficulty;
        abort_if(! array_key_exists($difficulty, self::DIFFICULTIES), 404, 'Unbekannter Schwierigkeitsgrad.');

        return $difficulty;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function currentExerciseData(): ?array
    {
        $exerciseId = session('uml_exercise_id');

        if (! $exerciseId) {
            return null;
        }

        $exercise = Exercise::query()
            ->with('umlDetail')
            ->whereKey($exerciseId)
            ->where('type', 'uml')
            ->where('status', 'published')
            ->first();

        if ($exercise === null || $exercise->umlDetail === null) {
            session()->forget('uml_exercise_id');

            return null;
        }

        return [
            'database_id' => $exercise->id,
            'id' => $exercise->external_id,
            'type' => $exercise->type,
            'difficulty' => $exercise->difficulty,
            'topic' => $exercise->topic,
            'title' => $exercise->title,
            'task' => $exercise->task,
            'explanation' => $exercise->explanation,
            'source' => $exercise->source,
            'diagram_type' => $exercise->umlDetail->diagram_type,
            'starter_plantuml' => $exercise->umlDetail->starter_plantuml,
            'solution_plantuml' => $exercise->umlDetail->solution_plantuml,
        ];
    }

    private function normalizeToPlantUml(string $input): string
    {
        if (preg_match('/@(?:startuml|enduml)\b/i', $input)) {
            if (! config('plantuml.allow_raw_directives')) {
                throw new InvalidArgumentException('Direkte PlantUML-Direktiven sind deaktiviert.');
            }

            return trim($input);
        }

        $lines = preg_split('/\R/', $input);
        $out = [];
        $inClass = false;

        foreach ($lines as $line) {
            $trim = ltrim(rtrim($line));

            if ($trim === '') {
                if ($inClass) {
                    $out[] = '}';
                    $inClass = false;
                }

                continue;
            }

            if (preg_match('/^class\s+([A-Za-z_]\w*)$/i', $trim, $matches)) {
                if ($inClass) {
                    $out[] = '}';
                }

                $out[] = "class {$matches[1]} {";
                $inClass = true;

                continue;
            }

            if (preg_match('/^\w[\w$]*\s+(?:--|->|-->|o--|\*--|\.\.>|<\|--)\s+\w[\w$]*(?:\s*:\s*.*)?$/', $trim)) {
                if ($inClass) {
                    $out[] = '}';
                    $inClass = false;
                }

                $out[] = $trim;

                continue;
            }

            if ($inClass) {
                $out[] = '  '.$trim;

                continue;
            }

            throw new InvalidArgumentException("Unbekannte UML-Zeile: {$trim}");
        }

        if ($inClass) {
            $out[] = '}';
        }

        return implode("\n", $out);
    }
}
