<?php

namespace App\Services;

use App\Services\Exercises\SqlSetupValidator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PDO;
use PDOException;
use Throwable;

class DatabaseManager
{
    private PDO $pdoRoot;

    private array $config;

    public function __construct()
    {
        $this->config = config('exercises.sql');
        $this->assertSeparateRuntimeUser();
        $this->pdoRoot = $this->makePdo(null, 'admin');
    }

    /**
     * @return array{databases: int, grants: int}
     */
    public function cleanOldDatabases(?int $maxAgeSeconds = null): array
    {
        $maxAgeSeconds ??= $this->config['max_age_seconds'];
        $prefix = $this->config['database_prefix'];
        $like = $this->pdoRoot->quote($prefix.'%');
        $stmt = $this->pdoRoot->query("SELECT schema_name FROM information_schema.schemata WHERE schema_name LIKE {$like}");
        $dropped = 0;

        $remainingDatabases = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($remainingDatabases as $key => $dbName) {
            $timestamp = $this->temporaryDatabaseTimestamp($dbName);

            if ($timestamp === null) {
                continue;
            }

            if ((time() - $timestamp) > $maxAgeSeconds) {
                $this->dropTemporaryDatabase($dbName);
                unset($remainingDatabases[$key]);
                $dropped++;
            }
        }

        $revokedGrants = $this->revokeOrphanedRuntimeAccess(array_values($remainingDatabases));

        return [
            'databases' => $dropped,
            'grants' => $revokedGrants,
        ];
    }

    public function createTemporaryDatabase(): string
    {
        $dbName = $this->config['database_prefix'].time().'_'.Str::lower(Str::random(10));
        $charset = $this->config['charset'];
        $collation = $this->config['collation'];
        $created = false;

        try {
            $this->pdoRoot->exec("CREATE DATABASE `{$dbName}` CHARACTER SET {$charset} COLLATE {$collation}");
            $created = true;
            $this->grantRuntimeAccess($dbName);

            return $dbName;
        } catch (Throwable $e) {
            if ($created) {
                try {
                    $this->dropTemporaryDatabase($dbName);
                } catch (Throwable $cleanupError) {
                    Log::channel('sql_exercise')->warning('Failed to clean up partially created SQL database.', [
                        'database' => $dbName,
                        'error' => $cleanupError->getMessage(),
                    ]);
                }
            }

            throw $e;
        }
    }

    public function dropTemporaryDatabase(string $dbName): void
    {
        if ($this->temporaryDatabaseTimestamp($dbName) === null) {
            throw new \InvalidArgumentException('Invalid temporary SQL database name.');
        }

        $dropError = null;

        try {
            $this->pdoRoot->exec("DROP DATABASE IF EXISTS `{$dbName}`");
        } catch (Throwable $e) {
            $dropError = $e;
        }

        try {
            $this->revokeAllRuntimeAccess($dbName);
        } catch (Throwable $e) {
            if ($dropError === null) {
                throw $e;
            }

            Log::channel('sql_exercise')->warning('Failed to revoke SQL sandbox privileges after drop failure.', [
                'database' => $dbName,
                'error' => $e->getMessage(),
            ]);
        }

        if ($dropError !== null) {
            throw $dropError;
        }
    }

    public function connectToDatabase(string $dbName): PDO
    {
        return $this->makePdo($dbName, 'runtime');
    }

    public function getTables(string $dbName): array
    {
        $pdo = $this->connectToDatabase($dbName);
        $tableNames = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        $tables = [];

        foreach ($tableNames as $table) {
            $tables[$table] = $pdo->query("SELECT * FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);
        }

        return $tables;
    }

    public function createMySqlExercise(string $mysqlstatement, string $dbName): void
    {
        $statements = SqlSetupValidator::statements($mysqlstatement);
        $pdo = $this->makePdo($dbName, 'runtime');
        $setupError = null;

        foreach (array_values($statements) as $index => $stmt) {
            try {
                $pdo->exec($stmt);
            } catch (Throwable $e) {
                Log::channel('sql_exercise')->error('Generated SQL statement failed.', [
                    'database' => $dbName,
                    'statement_number' => $index + 1,
                    'statement' => $stmt,
                    'exception' => $e::class,
                    'error' => $e->getMessage(),
                ]);

                $setupError = $e;
                break;
            }
        }

        try {
            $this->restrictRuntimeAccessToSelect($dbName);
        } catch (Throwable $e) {
            if ($setupError !== null) {
                Log::channel('sql_exercise')->warning('Failed to remove SQL setup privileges after setup failure.', [
                    'database' => $dbName,
                    'error' => $e->getMessage(),
                ]);
            } else {
                throw $e;
            }
        }

        if ($setupError !== null) {
            throw $setupError;
        }
    }

    private function makePdo(?string $dbName, string $role): PDO
    {
        $connection = $this->config[$role];
        $charset = $this->config['charset'];
        $dsn = "mysql:host={$connection['host']};port={$connection['port']};charset={$charset}";

        if ($dbName !== null) {
            $dsn .= ";dbname={$dbName}";
        }

        return new PDO($dsn, $connection['username'], $connection['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_MULTI_STATEMENTS => false,
        ]);
    }

    private function grantRuntimeAccess(string $dbName): void
    {
        [$username, $host] = $this->escapedRuntimeAccount();

        $this->pdoRoot->exec(
            "GRANT SELECT, CREATE, INSERT ON `{$dbName}`.* TO '{$username}'@'{$host}'",
        );
    }

    private function restrictRuntimeAccessToSelect(string $dbName): void
    {
        [$username, $host] = $this->escapedRuntimeAccount();

        $this->pdoRoot->exec(
            "REVOKE CREATE, INSERT ON `{$dbName}`.* FROM '{$username}'@'{$host}'",
        );
    }

    private function revokeAllRuntimeAccess(string $dbName): void
    {
        [$username, $host] = $this->escapedRuntimeAccount();

        try {
            $this->pdoRoot->exec(
                "REVOKE ALL PRIVILEGES ON `{$dbName}`.* FROM '{$username}'@'{$host}'",
            );
        } catch (PDOException $e) {
            if (($e->errorInfo[1] ?? null) !== 1141) {
                throw $e;
            }
        }
    }

    /**
     * @param  array<int, string>  $existingDatabases
     */
    private function revokeOrphanedRuntimeAccess(array $existingDatabases): int
    {
        $runtime = $this->config['runtime'];
        $username = $this->pdoRoot->quote($runtime['username']);
        $host = $this->pdoRoot->quote($runtime['grant_host']);
        $prefix = $this->pdoRoot->quote($this->config['database_prefix'].'%');
        $statement = $this->pdoRoot->query(
            "SELECT Db FROM mysql.db WHERE User = {$username} AND Host = {$host} AND Db LIKE {$prefix}",
        );

        $revoked = 0;

        foreach ($statement->fetchAll(PDO::FETCH_COLUMN) as $dbName) {
            if (
                $this->temporaryDatabaseTimestamp($dbName) !== null
                && ! in_array($dbName, $existingDatabases, true)
            ) {
                $this->revokeAllRuntimeAccess($dbName);
                $revoked++;
            }
        }

        return $revoked;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function escapedRuntimeAccount(): array
    {
        $runtime = $this->config['runtime'];

        return [
            str_replace("'", "''", $runtime['username']),
            str_replace("'", "''", $runtime['grant_host']),
        ];
    }

    private function assertSeparateRuntimeUser(): void
    {
        if ($this->config['admin']['username'] === $this->config['runtime']['username']) {
            throw new \RuntimeException(
                'SQL_EXERCISE_RUNTIME_USERNAME must differ from SQL_EXERCISE_ADMIN_USERNAME.',
            );
        }
    }

    private function temporaryDatabaseTimestamp(string $dbName): ?int
    {
        $prefix = preg_quote($this->config['database_prefix'], '/');

        if (! preg_match('/^'.$prefix.'(?<timestamp>\d{10})(?:_[a-z0-9]{10})?$/i', $dbName, $matches)) {
            return null;
        }

        return (int) $matches['timestamp'];
    }
}
