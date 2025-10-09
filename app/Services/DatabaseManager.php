<?php

namespace App\Services;

use PDO;

class DatabaseManager
{
    private $pdo_root;

    public function __construct()
    {
        $host = 'localhost';
        $user = 'root';
        $pass = '';
        $charset = 'utf8mb4';

        $this->pdo_root = new PDO("mysql:host=$host;charset=$charset", $user, $pass);
        $this->pdo_root->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function cleanOldDatabases($maxAgeSeconds = 2000)
    {
        $stmt = $this->pdo_root->query("SELECT schema_name FROM information_schema.schemata WHERE schema_name LIKE 'sql_exercise_%'");
        foreach ($stmt as $row) {
            $dbNameOld = $row['schema_name'];
            $parts = explode('_', $dbNameOld);
            $timestamp = end($parts);
            if (is_numeric($timestamp) && (time() - (int)$timestamp) > $maxAgeSeconds) {
                $this->pdo_root->exec("DROP DATABASE `$dbNameOld`");
            }
        }
    }

    public function createTemporaryDatabase(): string
    {
        $dbName = 'sql_exercise_' . time();
        $this->pdo_root->exec("CREATE DATABASE `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        return $dbName;
    }

    public function connectToDatabase(string $dbName): PDO
    {
        return new PDO("mysql:host=localhost;dbname=$dbName;charset=utf8mb4", 'root', '');
    }


    //Maybe create a new class for this or switch this function into a  other class
    public function createMySqlExercise(string $mysqlstatement, string $dbName)
    {
        $pdo = $this->connectToDatabase($dbName);
        // Splitten an ; und jede Query einzeln ausführen
        $statements = array_filter(array_map('trim', explode(';', $mysqlstatement)));

        foreach ($statements as $stmt) {
            $pdo->exec($stmt);
        }
    }
}
