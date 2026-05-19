<?php

namespace Tests\Unit;

use App\Services\AI\AiResponseProvider;
use App\Services\AI\AiFixtureService;
use App\Services\DatabaseManager;
use App\Services\SqlExerciseGenerator;
use Mockery;
use PDOException;
use RuntimeException;
use Tests\TestCase;

class SqlExerciseGeneratorTest extends TestCase
{
    public function test_it_retries_after_invalid_generated_sql_and_returns_the_first_valid_attempt(): void
    {
        config(['exercises.sql.generation_attempts' => 3]);

        $payload = [
            'request_id' => 'retry-test',
            'extra_context' => 'Base context',
        ];

        $ai = Mockery::mock(AiResponseProvider::class);
        $ai->shouldReceive('getSql')
            ->with($payload, 'sql')
            ->once()
            ->andReturn([
                'task' => 'Kaputte Aufgabe',
                'mysqlstatement' => 'INVALID SQL',
                'solution' => 'SELECT 1;',
            ]);
        $ai->shouldReceive('getSql')
            ->withArgs(fn (array $retryPayload, string $fixtureKey) => $fixtureKey === 'sql'
                && $retryPayload['request_id'] === 'retry-test'
                && str_contains($retryPayload['extra_context'], 'Base context')
                && str_contains($retryPayload['extra_context'], 'Previous generated SQL was rejected by MariaDB.')
                && str_contains($retryPayload['extra_context'], 'invalid sql')
                && str_contains($retryPayload['extra_context'], 'Rejected mysqlstatement from previous attempt: INVALID SQL'))
            ->once()
            ->andReturn([
                'task' => 'Gueltige Aufgabe',
                'mysqlstatement' => 'CREATE TABLE answers (id INT PRIMARY KEY);',
                'solution' => 'SELECT id FROM answers;',
            ]);

        $dbManager = Mockery::mock(DatabaseManager::class);
        $dbManager->shouldReceive('createTemporaryDatabase')
            ->twice()
            ->andReturn('sql_exercise_1779115612_invalid', 'sql_exercise_1779115613_valid');
        $dbManager->shouldReceive('createMySqlExercise')
            ->once()
            ->with('INVALID SQL', 'sql_exercise_1779115612_invalid')
            ->andThrow(new PDOException('invalid sql'));
        $dbManager->shouldReceive('dropTemporaryDatabase')
            ->once()
            ->with('sql_exercise_1779115612_invalid');
        $dbManager->shouldReceive('createMySqlExercise')
            ->once()
            ->with('CREATE TABLE answers (id INT PRIMARY KEY);', 'sql_exercise_1779115613_valid');

        $fixtures = Mockery::mock(AiFixtureService::class);
        $fixtures->shouldReceive('loadMatching')->never();

        $service = new SqlExerciseGenerator($dbManager, $fixtures);
        $result = $service->generate($ai, $payload);

        $this->assertSame('sql_exercise_1779115613_valid', $result['database']);
        $this->assertStringContainsString('Previous generated SQL was rejected by MariaDB.', $result['payload']['extra_context']);
        $this->assertSame('Gueltige Aufgabe', $result['task']);
        $this->assertSame('CREATE TABLE answers (id INT PRIMARY KEY);', $result['mysqlstatement']);
        $this->assertSame('SELECT id FROM answers;', $result['solution']);
    }

    public function test_it_uses_the_sql_fixture_after_live_generation_attempts_are_exhausted(): void
    {
        config(['exercises.sql.generation_attempts' => 2]);

        $payload = [
            'request_id' => 'fallback-test',
        ];

        $ai = Mockery::mock(AiResponseProvider::class);
        $ai->shouldReceive('getSql')
            ->twice()
            ->andThrow(new RuntimeException('bad gateway payload'));

        $fixtures = Mockery::mock(AiFixtureService::class);
        $fixtures->shouldReceive('loadMatching')
            ->once()
            ->with('sql', ['difficulty' => null])
            ->andReturn([
                'title' => 'Fixture Titel',
                'task' => 'Fixture Aufgabe',
                'mysqlstatement' => 'CREATE TABLE answers (id INT PRIMARY KEY);',
                'solution' => 'SELECT id FROM answers;',
            ]);

        $dbManager = Mockery::mock(DatabaseManager::class);
        $dbManager->shouldReceive('createTemporaryDatabase')
            ->once()
            ->andReturn('sql_exercise_1779115614_fixture');
        $dbManager->shouldReceive('createMySqlExercise')
            ->once()
            ->with('CREATE TABLE answers (id INT PRIMARY KEY);', 'sql_exercise_1779115614_fixture');

        $service = new SqlExerciseGenerator($dbManager, $fixtures);
        $result = $service->generate($ai, $payload);

        $this->assertSame('sql_exercise_1779115614_fixture', $result['database']);
        $this->assertSame('sql', $result['payload']['fallback_fixture']);
        $this->assertSame('Fixture Aufgabe', $result['task']);
        $this->assertSame('Fixture Titel', $result['title']);
    }
}
