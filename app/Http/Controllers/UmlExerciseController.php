<?php

namespace App\Http\Controllers;

use App\Services\PlantUmlService;
use Illuminate\Http\Request;
use InvalidArgumentException;

class UmlExerciseController extends Controller
{
    public function create()
    {
        $sample = <<<TXT
            class Person
            - name : String
            - age  : Integer
            + getName() : String

            class Hund
            + bellen() : void

            Person -> Hund : besitzt
            TXT;

        return view('it.uml-exercise.index', [
            'input' => $sample,
            'imageDataUrl' => null,
            'error' => null,
        ]);
    }

    public function render(Request $request, PlantUmlService $plantUmlService)
    {
        $validated = $request->validate([
            'uml_text' => ['required', 'string', 'max:10000'],
        ]);

        $raw = $validated['uml_text'];

        try {
            $plantUml = $this->normalizeToPlantUml($raw);
            $pngPath = $plantUmlService->generate($plantUml);
            $dataUrl = 'data:image/png;base64,' . base64_encode(file_get_contents($pngPath));
            @unlink($pngPath);

            return view('it.uml-exercise.index', [
                'input' => $raw,
                'imageDataUrl' => $dataUrl,
                'error' => null,
            ]);
        } catch (\Throwable $e) {
            return view('it.uml-exercise.index', [
                'input' => $raw,
                'imageDataUrl' => null,
                'error' => 'Rendering fehlgeschlagen: ' . $e->getMessage(),
            ]);
        }
    }

    private function normalizeToPlantUml(string $input): string
    {
        if (preg_match('/@(?:startuml|enduml)\b/i', $input)) {
            if (!config('plantuml.allow_raw_directives')) {
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
                $out[] = '  ' . $trim;
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
