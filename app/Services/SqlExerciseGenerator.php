<?php

namespace App\Services;

use App\Services\AI\AiResponseProvider;
use App\Services\AI\AiFixtureService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PDOException;
use RuntimeException;
use Throwable;

class SqlExerciseGenerator
{
    public function __construct(
        private readonly DatabaseManager $dbManager,
        private readonly AiFixtureService $aiFixtureService,
    ) {}

    /**
     * @return array{
     *     database: string,
     *     payload: array<string, mixed>,
     *     title?: ?string,
     *     source: string,
     *     task: string,
     *     mysqlstatement: string,
     *     solution: string
     * }
     */
    public function generate(AiResponseProvider $ai, array $payload, string $fixtureKey = 'sql'): array
    {
        $maxAttempts = max(1, (int) config('exercises.sql.generation_attempts', 3));
        $lastFailure = null;
        $lastSqlException = null;
        $lastGeneratedSql = null;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $attemptPayload = $this->buildAttemptPayload($payload, $lastSqlException, $lastGeneratedSql);
            $dbName = null;
            $mysqlstatement = null;

            try {
                $data = $ai->getSql($attemptPayload, $fixtureKey);
                $dbName = $this->dbManager->createTemporaryDatabase();
                $mysqlstatement = (string) $data['mysqlstatement'];
                $this->dbManager->createMySqlExercise($mysqlstatement, $dbName);

                return [
                    'database' => $dbName,
                    'payload' => $attemptPayload,
                    'title' => isset($data['title']) ? (string) $data['title'] : null,
                    'source' => (string) ($data['source'] ?? 'generated'),
                    'task' => (string) $data['task'],
                    'mysqlstatement' => $mysqlstatement,
                    'solution' => (string) $data['solution'],
                ];
            } catch (PDOException $e) {
                $lastFailure = $e;
                $lastSqlException = $e;
                $lastGeneratedSql = $mysqlstatement;

                Log::channel('sql_exercise')->warning('Generated SQL rejected.', [
                    'request_id' => $payload['request_id'] ?? null,
                    'attempt' => $attempt,
                    'max_attempts' => $maxAttempts,
                    'database' => $dbName,
                    'error' => $e->getMessage(),
                ]);

                if ($dbName !== null) {
                    $this->dbManager->dropTemporaryDatabase($dbName);
                }
            } catch (Throwable $e) {
                $lastFailure = $e;

                if ($dbName !== null) {
                    $this->dbManager->dropTemporaryDatabase($dbName);
                }

                Log::channel('sql_exercise')->warning('SQL generation attempt failed before validation.', [
                    'request_id' => $payload['request_id'] ?? null,
                    'attempt' => $attempt,
                    'max_attempts' => $maxAttempts,
                    'exception' => $e::class,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::channel('sql_exercise')->warning('SQL exercise generation exhausted retries; using fixture fallback.', [
            'request_id' => $payload['request_id'] ?? null,
            'max_attempts' => $maxAttempts,
            'error' => $lastFailure?->getMessage(),
        ]);

        return $this->generateFallbackExercise($payload, $fixtureKey);
    }

    private function buildAttemptPayload(
        array $payload,
        ?PDOException $lastException,
        ?string $lastGeneratedSql,
    ): array
    {
        if ($lastException === null) {
            return $payload;
        }

        $retryContext = array_filter([
            'Previous generated SQL was rejected by MariaDB.',
            'Generate a corrected valid SQL setup that fixes the previous error.',
            'MariaDB error from previous attempt: ' . Str::limit($lastException->getMessage(), 500),
            $lastGeneratedSql
                ? 'Rejected mysqlstatement from previous attempt: ' . Str::limit($lastGeneratedSql, 2000)
                : null,
        ]);

        $payload['extra_context'] = trim(implode("\n", array_filter([
            $payload['extra_context'] ?? null,
            implode("\n", $retryContext),
        ])));

        return $payload;
    }

    private function generateFallbackExercise(array $payload, string $fixtureKey): array
    {
        $data = $this->aiFixtureService->loadMatching($fixtureKey, [
            'difficulty' => $payload['difficulty'] ?? null,
        ]);
        $dbName = $this->dbManager->createTemporaryDatabase();
        $mysqlstatement = (string) $data['mysqlstatement'];

        $this->dbManager->createMySqlExercise($mysqlstatement, $dbName);

        $payload['fallback_fixture'] = $fixtureKey;
        $payload['fallback_fixture_difficulty'] = $data['difficulty'] ?? null;

        return [
            'database' => $dbName,
            'payload' => $payload,
            'title' => isset($data['title']) ? (string) $data['title'] : null,
            'source' => 'fixture',
            'task' => (string) $data['task'],
            'mysqlstatement' => $mysqlstatement,
            'solution' => (string) $data['solution'],
        ];
    }
}
