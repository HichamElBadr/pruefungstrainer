<?php

namespace App\Services;

use Illuminate\Support\Str;
use PDO;

class DatabaseManager
{
    private PDO $pdoRoot;
    private array $config;

    public function __construct()
    {
        $this->config = config('exercises.sql');
        $this->pdoRoot = $this->makePdo(null, 'admin');
    }

    public function cleanOldDatabases(?int $maxAgeSeconds = null): int
    {
        $maxAgeSeconds ??= $this->config['max_age_seconds'];
        $prefix = $this->config['database_prefix'];
        $like = $this->pdoRoot->quote($prefix . '%');
        $stmt = $this->pdoRoot->query("SELECT schema_name FROM information_schema.schemata WHERE schema_name LIKE {$like}");
        $dropped = 0;

        foreach ($stmt as $row) {
            $dbName = $row['schema_name'];

            if (!preg_match('/^' . preg_quote($prefix, '/') . '(\d{10})_[a-z0-9]+$/i', $dbName, $matches)) {
                continue;
            }

            if ((time() - (int) $matches[1]) > $maxAgeSeconds) {
                $this->pdoRoot->exec("DROP DATABASE `{$dbName}`");
                $dropped++;
            }
        }

        return $dropped;
    }

    public function createTemporaryDatabase(): string
    {
        $dbName = $this->config['database_prefix'] . time() . '_' . Str::lower(Str::random(10));
        $charset = $this->config['charset'];
        $collation = $this->config['collation'];

        $this->pdoRoot->exec("CREATE DATABASE `{$dbName}` CHARACTER SET {$charset} COLLATE {$collation}");
        $this->grantRuntimeAccess($dbName);

        return $dbName;
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
        $pdo = $this->makePdo($dbName, 'admin');
        $statements = array_filter(array_map('trim', explode(';', $mysqlstatement)));

        foreach ($statements as $stmt) {
            $pdo->exec($stmt);
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
        $admin = $this->config['admin'];
        $runtime = $this->config['runtime'];

        if (
            $admin['username'] === $runtime['username']
            && $admin['password'] === $runtime['password']
        ) {
            return;
        }

        $username = str_replace("'", "''", $runtime['username']);
        $host = str_replace("'", "''", $runtime['grant_host']);

        $this->pdoRoot->exec("GRANT SELECT ON `{$dbName}`.* TO '{$username}'@'{$host}'");
    }
}
