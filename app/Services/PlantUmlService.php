<?php

namespace App\Services;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PlantUmlService
{
    protected string $jarPath;
    protected string $outputDir;

    public function __construct()
    {
        $this->jarPath  = storage_path('app/plantuml/plantuml-1.2025.4.jar');
        $this->outputDir = storage_path('app/plantuml/output');

        if (!is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0755, true);
        }
    }

    public function generate(string $umlCode): string
    {
        // >>> 1) Pfade festlegen (absolut!) <<<
        $javaPath = 'C:\Program Files\Eclipse Adoptium\jdk-21.0.8.9-hotspot\bin\java.exe';
        $jarPath  = $this->jarPath;     // already absolute via storage_path()
        $outDir   = $this->outputDir;   // absolute

        if (!file_exists($jarPath)) {
            throw new \RuntimeException("PlantUML JAR nicht gefunden unter: {$jarPath}");
        }

        // >>> 2) Nutzer darf ohne @startuml/@enduml schreiben <<<
        if (!str_contains($umlCode, '@startuml')) {
            $umlCode = "@startuml\n" . trim($umlCode) . "\n@enduml";
        }

        // >>> 3) .puml temporär im Projekt ablegen (debug-freundlich) <<<
        $tempDir = storage_path('app/plantuml/temp');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $tempFileName = Str::random(10) . '.puml';
        $tempFilePath = $tempDir . DIRECTORY_SEPARATOR . $tempFileName;
        file_put_contents($tempFilePath, $umlCode);

        // eigenes TMP
        $javaTmp = storage_path('app/plantuml/tmpjava');
        if (!is_dir($javaTmp)) mkdir($javaTmp, 0755, true);

        // CMD mit headless + eigenem tmp + verbose
        $cmd = sprintf(
            'cmd /c ""%s" -Djava.awt.headless=true -Djava.io.tmpdir="%s" -jar "%s" "%s" -tpng -charset UTF-8 -o "%s" -v"',
            $javaPath,
            $javaTmp,
            $jarPath,
            $tempFilePath,
            $outDir
        );


        $process = Process::fromShellCommandline($cmd);
        // Arbeitsverzeichnis explizit setzen (wichtig unter XAMPP/Apache)
        $process->setWorkingDirectory(base_path());
        $process->setTimeout(60);
        $process->run();

        if (!$process->isSuccessful()) {
            // zum Debuggen hilfreich:
            Log::error('PlantUML stderr: ' . $process->getErrorOutput());
            Log::error('PlantUML stdout: ' . $process->getOutput());
            throw new ProcessFailedException($process);
        }

        // >>> 5) Erwarteten Output-Pfad ermitteln <<<
        $pngPath = $outDir . DIRECTORY_SEPARATOR . pathinfo($tempFileName, PATHINFO_FILENAME) . '.png';

        // Manche Installationen schreiben gelegentlich in das Temp-Verzeichnis → Fallback check
        $fallback = $tempDir . DIRECTORY_SEPARATOR . pathinfo($tempFileName, PATHINFO_FILENAME) . '.png';
        if (!file_exists($pngPath) && file_exists($fallback)) {
            rename($fallback, $pngPath);
        }

        if (!file_exists($pngPath) || filesize($pngPath) < 10) {
            // Loggen, falls etwas schiefging
            Log::warning('PlantUML PNG fehlt oder ist zu klein', [
                'cmd' => $cmd,
                'pngPath' => $pngPath,
                'stderr' => $process->getErrorOutput(),
                'stdout' => $process->getOutput(),
            ]);
            throw new \RuntimeException('PlantUML konnte keine gültige PNG-Datei erzeugen.');
        }

        // Optional: Temp-Datei aufräumen
        @unlink($tempFilePath);

        return $pngPath;
    }
}
