<?php

namespace App\Http\Controllers;

use App\Services\PlantUmlService;
use Illuminate\Http\Request;

class UmlExerciseController extends Controller
{
    public function create()
    {
        // Beispiel-Eingabe im vereinfachten Format (ohne @startuml/@enduml)
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
        $raw = (string) $request->input('uml_text');

        if (trim($raw) === '') {
            return view('it.uml-exercise.index', [
                'input' => $raw,
                'imageDataUrl' => null,
                'error' => 'Bitte gib etwas Text ein.',
            ]);
        }

        // Wenn der Nutzer bereits echtes PlantUML mit @startuml nutzt, nimm es 1:1.
        $plantUml = str_contains($raw, '@startuml') ? $raw : $this->normalizeToPlantUml($raw);

        try {
            $pngPath = $plantUmlService->generate($plantUml);
            $dataUrl = 'data:image/png;base64,' . base64_encode(file_get_contents($pngPath));

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

    /**
     * Mini-Normalizer: wandelt vereinfachte Eingabe in PlantUML für Klassen um.
     * Unterstützt:
     *  - class Foo  (+ nachfolgende Zeilen als Body)
     *  - Beziehungen: A -> B : label (oder -->, ..>, etc.)
     */
    private function normalizeToPlantUml(string $input): string
    {
        $lines = preg_split('/\R/', $input);
        $out = [];
        $inClass = false;

        foreach ($lines as $line) {
            $t = rtrim($line);
            $trim = ltrim($t);

            if ($trim === '') {
                // Leere Zeile trennt ggf. Klassenblöcke
                if ($inClass) { $out[] = "}"; $inClass = false; }
                continue;
            }

            // Neue Klasse?
            if (preg_match('/^class\s+([A-Za-z_]\w*)$/i', $trim, $m)) {
                if ($inClass) { $out[] = "}"; }
                $out[] = "class {$m[1]} {";
                $inClass = true;
                continue;
            }

            // Beziehung (A -> B : label)
            if (preg_match('/^\w[\w$]*\s*[-.o*+#<]*[<>]*\s*[-.]*>\s*\w[\w$]*(?:\s*:\s*.*)?$/', $trim)) {
                if ($inClass) { $out[] = "}"; $inClass = false; }
                $out[] = $trim;
                continue;
            }

            // Zeilen innerhalb einer Klasse (Attribute/Methoden)
            if ($inClass) {
                // einfach übernehmen (PlantUML versteht +/-/# prefix)
                $out[] = "  " . $trim;
                continue;
            }

            // Fallback: unbekannte Zeile außerhalb – gib sie roh aus (PlantUML erlaubt viele Direktiven)
            $out[] = $trim;
        }

        if ($inClass) { $out[] = "}"; }

        // @startuml/@enduml packt dein Service automatisch, falls nicht vorhanden
        return implode("\n", $out);
    }
}
