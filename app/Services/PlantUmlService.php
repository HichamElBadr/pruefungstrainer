<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class PlantUmlService
{
    protected string $javaPath;

    protected string $jarPath;

    protected string $outputDir;

    protected string $tempDir;

    protected string $javaTmpDir;

    protected int $retentionSeconds;

    public function __construct()
    {
        $this->javaPath = config('plantuml.java_path');
        $this->jarPath = config('plantuml.jar_path');
        $this->outputDir = config('plantuml.output_dir');
        $this->tempDir = config('plantuml.temp_dir');
        $this->javaTmpDir = config('plantuml.java_tmp_dir');
        $this->retentionSeconds = config('plantuml.retention_seconds');

        $this->ensureDirectory($this->outputDir);
        $this->ensureDirectory($this->tempDir);
        $this->ensureDirectory($this->javaTmpDir);
    }

    public function generate(string $umlCode): string
    {
        if (! file_exists($this->jarPath)) {
            throw new \RuntimeException("PlantUML JAR nicht gefunden unter: {$this->jarPath}");
        }

        $this->cleanupArtifacts();

        $tempFileName = Str::random(10).'.puml';
        $tempFilePath = $this->tempDir.DIRECTORY_SEPARATOR.$tempFileName;
        file_put_contents($tempFilePath, $umlCode);

        $process = new Process([
            $this->javaPath,
            '-Djava.awt.headless=true',
            "-Djava.io.tmpdir={$this->javaTmpDir}",
            '-jar',
            $this->jarPath,
            $tempFilePath,
            '-tpng',
            '-charset',
            'UTF-8',
            '-o',
            $this->outputDir,
            '-v',
        ]);
        $process->setWorkingDirectory(base_path());
        $process->setTimeout(60);
        $process->run();

        if (! $process->isSuccessful()) {
            Log::error('PlantUML stderr: '.$process->getErrorOutput());
            Log::error('PlantUML stdout: '.$process->getOutput());
            throw new ProcessFailedException($process);
        }

        $basename = pathinfo($tempFileName, PATHINFO_FILENAME);
        $pngPath = $this->outputDir.DIRECTORY_SEPARATOR.$basename.'.png';
        $fallback = $this->tempDir.DIRECTORY_SEPARATOR.$basename.'.png';

        if (! file_exists($pngPath) && file_exists($fallback)) {
            rename($fallback, $pngPath);
        }

        if (! file_exists($pngPath) || filesize($pngPath) < 10) {
            Log::warning('PlantUML PNG fehlt oder ist zu klein', [
                'pngPath' => $pngPath,
                'stderr' => $process->getErrorOutput(),
                'stdout' => $process->getOutput(),
            ]);
            throw new \RuntimeException('PlantUML konnte keine gültige PNG-Datei erzeugen.');
        }

        @unlink($tempFilePath);

        return $pngPath;
    }

    private function cleanupArtifacts(): void
    {
        $cutoff = time() - $this->retentionSeconds;

        foreach ([$this->outputDir, $this->tempDir] as $directory) {
            foreach (glob($directory.DIRECTORY_SEPARATOR.'*') ?: [] as $path) {
                if (! is_file($path) || filemtime($path) >= $cutoff) {
                    continue;
                }

                @unlink($path);
            }
        }
    }

    private function ensureDirectory(string $path): void
    {
        if (! is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }
}
