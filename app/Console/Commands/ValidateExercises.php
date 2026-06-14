<?php

namespace App\Console\Commands;

use App\Services\Exercises\ExerciseCollectionValidator;
use Illuminate\Console\Command;

class ValidateExercises extends Command
{
    protected $signature = 'exercises:validate
        {--skip-sql-execution : Skip temporary MySQL setup and solution execution}
        {--skip-uml-render : Skip PlantUML rendering and only validate UML wrappers}';

    protected $description = 'Validate all local JSON exercise collections';

    public function handle(ExerciseCollectionValidator $validator): int
    {
        $executeSql = ! $this->option('skip-sql-execution');
        $renderUml = ! $this->option('skip-uml-render');
        $report = $validator->validate($executeSql, $renderUml);

        foreach ($report->collectionCounts() as $collection => $count) {
            $this->line("{$collection}: {$count}");
        }

        if (! $executeSql) {
            $this->warn('SQL execution checks were skipped.');
        }

        if (! $renderUml) {
            $this->warn('PlantUML rendering checks were skipped.');
        }

        if (! $report->isValid()) {
            $this->newLine();
            $this->error('Exercise validation failed:');

            foreach ($report->errors() as $error) {
                $this->line(" - {$error}");
            }

            return self::FAILURE;
        }

        $this->newLine();
        $this->info("Validated {$report->exerciseCount()} exercises successfully.");

        return self::SUCCESS;
    }
}
