<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ValidateExercisesCommandTest extends TestCase
{
    private string $temporaryRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->temporaryRoot = storage_path('framework/testing/validate-exercises-'.uniqid());
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->temporaryRoot);

        parent::tearDown();
    }

    public function test_command_validates_the_current_fixture_collections(): void
    {
        $this->artisan('exercises:validate', [
            '--skip-sql-execution' => true,
            '--skip-uml-render' => true,
        ])
            ->expectsOutputToContain('sql/easy: 3')
            ->expectsOutputToContain('uml/easy: 10')
            ->expectsOutputToContain('uml/hard: 9')
            ->expectsOutputToContain('calculation/hard: 7')
            ->expectsOutputToContain('Validated 58 exercises successfully.')
            ->assertExitCode(0);
    }

    public function test_command_fails_for_duplicate_exercise_ids(): void
    {
        $this->copyExerciseCollections();
        $path = $this->temporaryRoot.'/sql/easy/exercises.json';
        $fixtures = json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        $fixtures[1]['id'] = $fixtures[0]['id'];
        file_put_contents($path, json_encode($fixtures, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        config(['exercises.path' => $this->temporaryRoot]);

        $this->artisan('exercises:validate', [
            '--skip-sql-execution' => true,
            '--skip-uml-render' => true,
        ])
            ->expectsOutputToContain('Doppelte Aufgaben-ID')
            ->assertExitCode(1);
    }

    public function test_command_fails_when_a_calculation_topic_is_missing(): void
    {
        $this->copyExerciseCollections();
        $path = $this->temporaryRoot.'/calculation/hard/exercises.json';
        $fixtures = json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        $fixtures = array_values(array_filter(
            $fixtures,
            fn (array $fixture): bool => $fixture['topic'] !== 'hardwarekosten',
        ));
        file_put_contents($path, json_encode($fixtures, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        config(['exercises.path' => $this->temporaryRoot]);

        $this->artisan('exercises:validate', [
            '--skip-sql-execution' => true,
            '--skip-uml-render' => true,
        ])
            ->expectsOutputToContain("Keine Aufgabe fuer das Thema 'hardwarekosten' vorhanden.")
            ->assertExitCode(1);
    }

    public function test_command_fails_for_an_unsafe_sql_solution(): void
    {
        $this->copyExerciseCollections();
        $path = $this->temporaryRoot.'/sql/easy/exercises.json';
        $fixtures = json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        $fixtures[0]['solution'] = 'SELECT SLEEP(10);';
        file_put_contents($path, json_encode($fixtures, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        config(['exercises.path' => $this->temporaryRoot]);

        $this->artisan('exercises:validate', [
            '--skip-sql-execution' => true,
            '--skip-uml-render' => true,
        ])
            ->expectsOutputToContain('Unsichere Musterloesung')
            ->assertExitCode(1);
    }

    public function test_command_enforces_the_minimum_collection_size(): void
    {
        config(['exercises.minimum_per_collection' => 8]);

        $this->artisan('exercises:validate', [
            '--skip-sql-execution' => true,
            '--skip-uml-render' => true,
        ])
            ->expectsOutputToContain('Mindestens 8 Aufgaben erforderlich')
            ->assertExitCode(1);
    }

    public function test_command_rejects_unknown_calculation_topics(): void
    {
        $this->copyExerciseCollections();
        $path = $this->temporaryRoot.'/calculation/easy/exercises.json';
        $fixtures = json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        $fixtures[0]['topic'] = 'unbekannt';
        file_put_contents($path, json_encode($fixtures, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        config(['exercises.path' => $this->temporaryRoot]);

        $this->artisan('exercises:validate', [
            '--skip-sql-execution' => true,
            '--skip-uml-render' => true,
        ])
            ->expectsOutputToContain("Unbekanntes Rechenthema 'unbekannt'")
            ->assertExitCode(1);
    }

    private function copyExerciseCollections(): void
    {
        File::copyDirectory(resource_path('exercises'), $this->temporaryRoot);
    }
}
