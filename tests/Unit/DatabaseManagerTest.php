<?php

namespace Tests\Unit;

use App\Services\DatabaseManager;
use Mockery;
use PDO;
use PDOStatement;
use ReflectionClass;
use Tests\TestCase;

class DatabaseManagerTest extends TestCase
{
    public function test_cleanup_drops_expired_current_and_legacy_database_names_only(): void
    {
        $oldTimestamp = time() - 3600;
        $recentTimestamp = time() - 60;
        $legacyDatabase = "sql_exercise_{$oldTimestamp}";
        $currentDatabase = "sql_exercise_{$oldTimestamp}_abcdefghij";
        $orphanedDatabase = 'sql_exercise_1700000000_orphaned01';

        $schemaStatement = Mockery::mock(PDOStatement::class);
        $schemaStatement->shouldReceive('fetchAll')
            ->once()
            ->with(PDO::FETCH_COLUMN)
            ->andReturn([
                $legacyDatabase,
                $currentDatabase,
                "sql_exercise_{$recentTimestamp}",
                "sql_exercise_{$recentTimestamp}_abcdefghij",
                "sql_exercise_{$oldTimestamp}_invalid-suffix",
                "sql_exercise_backup_{$oldTimestamp}",
                'application',
            ]);
        $grantStatement = Mockery::mock(PDOStatement::class);
        $grantStatement->shouldReceive('fetchAll')
            ->once()
            ->with(PDO::FETCH_COLUMN)
            ->andReturn([$orphanedDatabase]);

        $pdo = Mockery::mock(PDO::class);
        $pdo->shouldReceive('quote')
            ->twice()
            ->with('sql_exercise_%')
            ->andReturn("'sql_exercise_%'");
        $pdo->shouldReceive('quote')
            ->once()
            ->with('sql_exercise_reader')
            ->andReturn("'sql_exercise_reader'");
        $pdo->shouldReceive('quote')
            ->once()
            ->with('localhost')
            ->andReturn("'localhost'");
        $pdo->shouldReceive('query')
            ->once()
            ->with("SELECT schema_name FROM information_schema.schemata WHERE schema_name LIKE 'sql_exercise_%'")
            ->andReturn($schemaStatement);
        $pdo->shouldReceive('query')
            ->once()
            ->with(
                "SELECT Db FROM mysql.db WHERE User = 'sql_exercise_reader' "
                ."AND Host = 'localhost' AND Db LIKE 'sql_exercise_%'",
            )
            ->andReturn($grantStatement);
        $pdo->shouldReceive('exec')
            ->once()
            ->with("DROP DATABASE IF EXISTS `{$legacyDatabase}`");
        $pdo->shouldReceive('exec')
            ->once()
            ->with("REVOKE ALL PRIVILEGES ON `{$legacyDatabase}`.* FROM 'sql_exercise_reader'@'localhost'");
        $pdo->shouldReceive('exec')
            ->once()
            ->with("DROP DATABASE IF EXISTS `{$currentDatabase}`");
        $pdo->shouldReceive('exec')
            ->once()
            ->with("REVOKE ALL PRIVILEGES ON `{$currentDatabase}`.* FROM 'sql_exercise_reader'@'localhost'");
        $pdo->shouldReceive('exec')
            ->once()
            ->with("REVOKE ALL PRIVILEGES ON `{$orphanedDatabase}`.* FROM 'sql_exercise_reader'@'localhost'");

        $manager = $this->databaseManager($pdo);

        $this->assertSame([
            'databases' => 2,
            'grants' => 1,
        ], $manager->cleanOldDatabases(600));
    }

    public function test_explicit_drop_accepts_current_and_legacy_formats_but_rejects_other_names(): void
    {
        $timestamp = time() - 3600;
        $legacyDatabase = "sql_exercise_{$timestamp}";
        $currentDatabase = "sql_exercise_{$timestamp}_abcdefghij";

        $pdo = Mockery::mock(PDO::class);
        $pdo->shouldReceive('exec')
            ->once()
            ->with("DROP DATABASE IF EXISTS `{$legacyDatabase}`");
        $pdo->shouldReceive('exec')
            ->once()
            ->with("REVOKE ALL PRIVILEGES ON `{$legacyDatabase}`.* FROM 'sql_exercise_reader'@'localhost'");
        $pdo->shouldReceive('exec')
            ->once()
            ->with("DROP DATABASE IF EXISTS `{$currentDatabase}`");
        $pdo->shouldReceive('exec')
            ->once()
            ->with("REVOKE ALL PRIVILEGES ON `{$currentDatabase}`.* FROM 'sql_exercise_reader'@'localhost'");

        $manager = $this->databaseManager($pdo);
        $manager->dropTemporaryDatabase($legacyDatabase);
        $manager->dropTemporaryDatabase($currentDatabase);

        $this->expectException(\InvalidArgumentException::class);
        $manager->dropTemporaryDatabase("sql_exercise_{$timestamp}_invalid-suffix");
    }

    private function databaseManager(PDO $pdo): DatabaseManager
    {
        $reflection = new ReflectionClass(DatabaseManager::class);
        $manager = $reflection->newInstanceWithoutConstructor();

        $pdoProperty = $reflection->getProperty('pdoRoot');
        $pdoProperty->setValue($manager, $pdo);

        $configProperty = $reflection->getProperty('config');
        $configProperty->setValue($manager, [
            'database_prefix' => 'sql_exercise_',
            'max_age_seconds' => 2000,
            'admin' => [
                'username' => 'root',
            ],
            'runtime' => [
                'username' => 'sql_exercise_reader',
                'grant_host' => 'localhost',
            ],
        ]);

        return $manager;
    }
}
