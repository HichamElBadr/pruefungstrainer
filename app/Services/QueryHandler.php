<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use PDO;
use PDOStatement;

class QueryHandler
{
    /**
     * @return array{columns: array<int,string>, rows: array<int,array<string,mixed>>, message: ?string, error: ?string}
     */
    public static function executeUserQuery(PDO $pdo, string $sql): array
    {
        $sql = trim($sql);
        $sql = preg_replace('/;\s*$/', '', $sql) ?? '';

        if ($sql === '') {
            return self::error('Bitte gib eine SELECT-Abfrage ein.');
        }

        if (str_contains($sql, ';')) {
            return self::error('Es ist nur eine einzelne SELECT-Abfrage erlaubt.');
        }

        if (!preg_match('/^select\b/i', $sql)) {
            return self::error('Es sind nur SELECT-Abfragen erlaubt.');
        }

        try {
            $stmt = $pdo->query($sql);

            if ($stmt === false) {
                return self::error('Fehler: Die SQL-Abfrage konnte nicht ausgeführt werden.');
            }

            $columns = self::columnsFromStatement($stmt);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($columns) && ! empty($rows)) {
                $columns = array_keys($rows[0]);
            }

            if (empty($rows)) {
                return [
                    'columns' => $columns,
                    'rows' => [],
                    'message' => 'Keine Ergebnisse gefunden.',
                    'error' => null,
                ];
            }

            return [
                'columns' => array_keys($rows[0]),
                'rows' => $rows,
                'message' => null,
                'error' => null,
            ];
        } catch (\Throwable $e) {
            Log::channel('sql_exercise')->error('Learner SQL query failed.', [
                'query' => $sql,
                'exception' => $e::class,
                'error' => $e->getMessage(),
            ]);

            return self::error('Fehler: ' . $e->getMessage());
        }
    }

    private static function error(string $message): array
    {
        return [
            'columns' => [],
            'rows' => [],
            'message' => null,
            'error' => $message,
        ];
    }

    /**
     * @return array<int,string>
     */
    private static function columnsFromStatement(PDOStatement $stmt): array
    {
        $columns = [];

        for ($i = 0; $i < $stmt->columnCount(); $i++) {
            $meta = $stmt->getColumnMeta($i);
            $name = is_array($meta) ? ($meta['name'] ?? null) : null;

            if (is_string($name) && $name !== '') {
                $columns[] = $name;
            }
        }

        return $columns;
    }
}
