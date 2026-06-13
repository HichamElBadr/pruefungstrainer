<?php

namespace Tests\Unit;

use App\Contracts\ExerciseProvider;
use App\Exceptions\ExerciseSourceException;
use App\Services\Exercises\DatabaseExerciseProvider;
use App\Services\Exercises\JsonExerciseProvider;
use App\Services\QueryHandler;
use Illuminate\Support\Facades\File;
use PDO;
use Tests\TestCase;

class JsonExerciseProviderTest extends TestCase
{
    private string $temporaryRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->temporaryRoot = storage_path('framework/testing/json-exercises-'.uniqid());
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->temporaryRoot);

        parent::tearDown();
    }

    public function test_database_is_the_default_bound_exercise_source(): void
    {
        $this->assertSame('database', config('exercises.source'));
        $this->assertInstanceOf(DatabaseExerciseProvider::class, app(ExerciseProvider::class));
    }

    public function test_fixture_collections_cover_every_type_and_difficulty(): void
    {
        foreach (config('exercises.types') as $type) {
            foreach (config('exercises.difficulties') as $difficulty) {
                $fixtures = $this->fixturesFrom(resource_path("exercises/{$type}/{$difficulty}"));

                $this->assertGreaterThanOrEqual(
                    3,
                    count($fixtures),
                    "Expected several fixtures for {$type}/{$difficulty}.",
                );

                foreach ($fixtures as $fixture) {
                    $this->assertSame($type, $fixture['type']);
                    $this->assertSame($difficulty, $fixture['difficulty']);
                }
            }
        }
    }

    public function test_provider_filters_calculation_exercises_by_topic_and_difficulty(): void
    {
        $provider = new JsonExerciseProvider;
        $topics = [
            'prozentrechnung',
            'dreisatz',
            'multiplikation',
            'division',
            'speichergroessen',
            'stromverbrauch',
            'hardwarekosten',
        ];

        foreach (config('exercises.difficulties') as $difficulty) {
            foreach ($topics as $topic) {
                $exercise = $provider->random('calculation', $difficulty, ['topic' => $topic]);

                $this->assertSame('calculation', $exercise['type']);
                $this->assertSame($difficulty, $exercise['difficulty']);
                $this->assertSame($topic, $exercise['topic']);
                $this->assertSame('json', $exercise['source']);
            }
        }
    }

    public function test_all_sql_fixture_setup_and_solution_queries_are_executable(): void
    {
        foreach (config('exercises.difficulties') as $difficulty) {
            foreach ($this->fixturesFrom(resource_path("exercises/sql/{$difficulty}")) as $fixture) {
                $pdo = new PDO('sqlite::memory:');
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                foreach (array_filter(array_map('trim', explode(';', $fixture['setup_sql']))) as $statement) {
                    $pdo->exec($statement);
                }

                $result = QueryHandler::executeUserQuery($pdo, $fixture['solution']);

                $this->assertTrue($result['success'], "SQL fixture failed: {$fixture['id']}");
                $this->assertNotEmpty($result['columns'], "SQL fixture returned no columns: {$fixture['id']}");
            }
        }
    }

    public function test_missing_exercise_directory_has_a_clear_error(): void
    {
        config(['exercises.path' => $this->temporaryRoot]);

        $this->expectException(ExerciseSourceException::class);
        $this->expectExceptionMessage('Aufgabenverzeichnis');

        (new JsonExerciseProvider)->random('sql', 'easy');
    }

    public function test_empty_exercise_directory_has_a_clear_error(): void
    {
        $directory = $this->temporaryRoot.'/sql/easy';
        File::ensureDirectoryExists($directory);
        config(['exercises.path' => $this->temporaryRoot]);

        $this->expectException(ExerciseSourceException::class);
        $this->expectExceptionMessage('keine JSON-Dateien');

        (new JsonExerciseProvider)->random('sql', 'easy');
    }

    public function test_invalid_json_has_a_clear_error(): void
    {
        $directory = $this->temporaryRoot.'/sql/easy';
        File::ensureDirectoryExists($directory);
        file_put_contents($directory.'/broken.json', '{"id":');
        config(['exercises.path' => $this->temporaryRoot]);

        $this->expectException(ExerciseSourceException::class);
        $this->expectExceptionMessage('Ungueltiges JSON');

        (new JsonExerciseProvider)->random('sql', 'easy');
    }

    public function test_missing_required_field_has_a_clear_error(): void
    {
        $directory = $this->temporaryRoot.'/sql/easy';
        File::ensureDirectoryExists($directory);
        $fixture = $this->validSqlFixture();
        unset($fixture['setup_sql']);
        file_put_contents($directory.'/missing-field.json', json_encode($fixture, JSON_THROW_ON_ERROR));
        config(['exercises.path' => $this->temporaryRoot]);

        $this->expectException(ExerciseSourceException::class);
        $this->expectExceptionMessage("fehlt das Pflichtfeld 'setup_sql'");

        (new JsonExerciseProvider)->random('sql', 'easy');
    }

    public function test_unsafe_sql_setup_has_a_clear_error(): void
    {
        $directory = $this->temporaryRoot.'/sql/easy';
        File::ensureDirectoryExists($directory);
        $fixture = $this->validSqlFixture();
        $fixture['setup_sql'] = 'DROP DATABASE pruefungstrainer;';
        file_put_contents($directory.'/unsafe-setup.json', json_encode($fixture, JSON_THROW_ON_ERROR));
        config(['exercises.path' => $this->temporaryRoot]);

        $this->expectException(ExerciseSourceException::class);
        $this->expectExceptionMessage('Erlaubt sind nur CREATE TABLE und INSERT INTO');

        (new JsonExerciseProvider)->random('sql', 'easy');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fixturesFrom(string $directory): array
    {
        $fixtures = [];

        foreach (glob($directory.'/*.json') ?: [] as $file) {
            $decoded = json_decode(file_get_contents($file), true, flags: JSON_THROW_ON_ERROR);

            foreach (array_is_list($decoded) ? $decoded : [$decoded] as $fixture) {
                $fixtures[] = $fixture;
            }
        }

        return $fixtures;
    }

    /**
     * @return array<string, mixed>
     */
    private function validSqlFixture(): array
    {
        return [
            'id' => 'sql-easy-test',
            'type' => 'sql',
            'difficulty' => 'easy',
            'topic' => 'select',
            'title' => 'Test',
            'task' => 'Testaufgabe',
            'setup_sql' => 'CREATE TABLE items (id INT PRIMARY KEY);',
            'solution' => 'SELECT id FROM items;',
            'explanation' => 'Testerklaerung',
            'tags' => ['SQL'],
        ];
    }
}
