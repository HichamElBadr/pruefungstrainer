<?php

namespace App\Console\Commands;

use App\Services\Exercises\JsonExerciseProvider;
use App\Services\PlantUmlRenderCache;
use Illuminate\Console\Command;
use Throwable;

class RenderUmlSolutions extends Command
{
    protected $signature = 'exercises:render-uml-solutions';

    protected $description = 'Render and cache PlantUML sample solutions from local UML fixtures';

    public function handle(
        JsonExerciseProvider $exerciseProvider,
        PlantUmlRenderCache $renderCache,
    ): int {
        $rendered = 0;
        $reused = 0;
        $failed = 0;

        foreach (config('exercises.difficulties', []) as $difficulty) {
            try {
                $exercises = $exerciseProvider->all('uml', $difficulty);
            } catch (Throwable $e) {
                $failed++;
                $this->error("[uml/{$difficulty}] {$e->getMessage()}");

                continue;
            }

            foreach ($exercises as $exercise) {
                $id = (string) ($exercise['id'] ?? 'unknown');

                try {
                    $result = $renderCache->cache((string) ($exercise['solution_plantuml'] ?? ''));
                    $result['rendered'] ? $rendered++ : $reused++;
                } catch (Throwable $e) {
                    $failed++;
                    $this->error("[{$id}] {$e->getMessage()}");
                }
            }
        }

        $this->info(
            "UML solution cache: {$rendered} rendered, {$reused} reused, {$failed} failed.",
        );

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
