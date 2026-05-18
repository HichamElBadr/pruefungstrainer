<?php

namespace App\Services;

use PDO;

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
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($rows)) {
                return [
                    'columns' => [],
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
        } catch (\Exception $e) {
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
}
