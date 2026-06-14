<?php

namespace App\Console\Commands;

use App\Services\Exercises\ExerciseFixtureImporter;
use Illuminate\Console\Command;
use Throwable;

class ImportExercises extends Command
{
    protected $signature = 'exercises:import';

    protected $description = 'Import local JSON fixtures into the reusable exercise catalog';

    public function handle(ExerciseFixtureImporter $importer): int
    {
        try {
            $result = $importer->import();
        } catch (Throwable $e) {
            $this->error('Exercise import failed: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info(
            "Imported {$result['total']} exercises "
            ."({$result['created']} created, {$result['updated']} updated).",
        );

        return self::SUCCESS;
    }
}
