<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PDO;
use PDOStatement;
use Throwable;

class QueryHandler
{
    /**
     * @return array{
     *     success: bool,
     *     columns?: array<int,string>,
     *     rows?: array<int,array<string,mixed>>,
     *     message: ?string,
     *     technical_message?: string,
     *     hint?: string
     * }
     */
    public static function executeUserQuery(PDO $pdo, string $sql): array
    {
        $sql = self::withoutTrailingSemicolon(trim($sql));
        $validationError = self::validationError($sql);

        if ($validationError !== null) {
            return self::failure($validationError);
        }

        $rowLimit = max(1, (int) config('exercises.sql.query.row_limit', 200));
        $timeoutMs = max(0, (int) config('exercises.sql.query.timeout_ms', 3000));

        try {
            $stmt = $pdo->query(self::withExecutionTimeout($pdo, $sql, $timeoutMs));

            if ($stmt === false) {
                return self::failure('Die Datenbank hat die Abfrage abgelehnt.');
            }

            $columns = self::columnsFromStatement($stmt);
            [$rows, $truncated] = self::limitedRows($stmt, $rowLimit);

            if ($columns === [] && $rows !== []) {
                $columns = array_keys($rows[0]);
            }

            if ($rows === []) {
                return [
                    'success' => true,
                    'columns' => $columns,
                    'rows' => [],
                    'message' => 'Keine Ergebnisse gefunden.',
                ];
            }

            return [
                'success' => true,
                'columns' => array_keys($rows[0]),
                'rows' => $rows,
                'message' => $truncated
                    ? "Die Ausgabe wurde auf die ersten {$rowLimit} Zeilen begrenzt."
                    : null,
            ];
        } catch (Throwable $e) {
            Log::channel('sql_exercise')->error('Learner SQL query failed.', [
                'query' => $sql,
                'exception' => $e::class,
                'error' => $e->getMessage(),
            ]);

            return self::failure($e->getMessage());
        }
    }

    public static function validationError(string $sql): ?string
    {
        $sql = self::withoutTrailingSemicolon(trim($sql));

        if ($sql === '') {
            return 'Bitte gib eine SELECT-Abfrage ein.';
        }

        if (preg_match('/\/\*(?:!|m!)/i', $sql)) {
            return 'Ausfuehrbare SQL-Kommentare sind nicht erlaubt.';
        }

        $masked = trim(self::maskStringsAndComments($sql));

        if (str_contains($masked, ';')) {
            return 'Es ist nur eine einzelne SELECT-Abfrage erlaubt.';
        }

        if (! preg_match('/^select\b/i', $masked)) {
            return 'Es sind nur SELECT-Abfragen erlaubt.';
        }

        foreach (config('exercises.sql.query.forbidden_functions', []) as $function) {
            if (preg_match('/\b'.preg_quote((string) $function, '/').'\s*\(/i', $masked)) {
                return 'Diese SQL-Funktion ist in Uebungsabfragen nicht erlaubt.';
            }
        }

        foreach (config('exercises.sql.query.forbidden_schemas', []) as $schema) {
            if (preg_match('/\b'.preg_quote((string) $schema, '/').'\s*\./i', $masked)) {
                return 'Der Zugriff auf Systemdatenbanken ist nicht erlaubt.';
            }
        }

        if (preg_match('/\binto\s+(?:out|dump)file\b/i', $masked)) {
            return 'Dateizugriffe sind in Uebungsabfragen nicht erlaubt.';
        }

        if (preg_match('/\bfor\s+update\b|\block\s+in\s+share\s+mode\b/i', $masked)) {
            return 'Sperrende SELECT-Abfragen sind nicht erlaubt.';
        }

        if (str_contains($masked, '@')) {
            return 'Der Zugriff auf Server- oder Sitzungsvariablen ist nicht erlaubt.';
        }

        return null;
    }

    private static function withoutTrailingSemicolon(string $sql): string
    {
        return preg_replace('/;\s*$/', '', $sql) ?? '';
    }

    /**
     * Replace comments and string contents with spaces while preserving offsets.
     * Backtick-quoted identifiers remain visible for schema checks.
     */
    private static function maskStringsAndComments(string $sql): string
    {
        $length = strlen($sql);
        $masked = '';
        $state = 'normal';

        for ($index = 0; $index < $length; $index++) {
            $char = $sql[$index];
            $next = $index + 1 < $length ? $sql[$index + 1] : '';

            if ($state === 'line_comment') {
                if ($char === "\n" || $char === "\r") {
                    $state = 'normal';
                    $masked .= $char;
                } else {
                    $masked .= ' ';
                }

                continue;
            }

            if ($state === 'block_comment') {
                if ($char === '*' && $next === '/') {
                    $masked .= '  ';
                    $index++;
                    $state = 'normal';
                } else {
                    $masked .= ' ';
                }

                continue;
            }

            if ($state === 'single_quote' || $state === 'double_quote') {
                $quote = $state === 'single_quote' ? "'" : '"';

                if ($char === '\\' && $next !== '') {
                    $masked .= '  ';
                    $index++;

                    continue;
                }

                if ($char === $quote && $next === $quote) {
                    $masked .= '  ';
                    $index++;

                    continue;
                }

                $masked .= ' ';

                if ($char === $quote) {
                    $state = 'normal';
                }

                continue;
            }

            if ($state === 'backtick') {
                if ($char === '`') {
                    if ($next === '`') {
                        $masked .= '  ';
                        $index++;
                    } else {
                        $masked .= ' ';
                        $state = 'normal';
                    }
                } else {
                    $masked .= $char;
                }

                continue;
            }

            if ($char === '-' && $next === '-' && self::isCommentBoundary($sql, $index + 2)) {
                $masked .= '  ';
                $index++;
                $state = 'line_comment';

                continue;
            }

            if ($char === '#') {
                $masked .= ' ';
                $state = 'line_comment';

                continue;
            }

            if ($char === '/' && $next === '*') {
                $masked .= '  ';
                $index++;
                $state = 'block_comment';

                continue;
            }

            if ($char === "'") {
                $masked .= ' ';
                $state = 'single_quote';

                continue;
            }

            if ($char === '"') {
                $masked .= ' ';
                $state = 'double_quote';

                continue;
            }

            if ($char === '`') {
                $masked .= ' ';
                $state = 'backtick';

                continue;
            }

            $masked .= $char;
        }

        return $masked;
    }

    private static function isCommentBoundary(string $sql, int $index): bool
    {
        return ! isset($sql[$index]) || ctype_space($sql[$index]);
    }

    private static function withExecutionTimeout(PDO $pdo, string $sql, int $timeoutMs): string
    {
        if ($timeoutMs <= 0 || $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'mysql') {
            return $sql;
        }

        try {
            $serverVersion = strtolower((string) $pdo->getAttribute(PDO::ATTR_SERVER_VERSION));
        } catch (Throwable) {
            return $sql;
        }

        if (str_contains($serverVersion, 'mariadb')) {
            $seconds = rtrim(rtrim(number_format($timeoutMs / 1000, 3, '.', ''), '0'), '.');

            return "SET STATEMENT max_statement_time={$seconds} FOR {$sql}";
        }

        $masked = self::maskStringsAndComments($sql);
        $selectOffset = stripos($masked, 'select');

        if ($selectOffset === false) {
            return $sql;
        }

        $insertAt = $selectOffset + strlen('select');

        return substr($sql, 0, $insertAt)
            ." /*+ MAX_EXECUTION_TIME({$timeoutMs}) */"
            .substr($sql, $insertAt);
    }

    /**
     * @return array{0: array<int, array<string, mixed>>, 1: bool}
     */
    private static function limitedRows(PDOStatement $stmt, int $rowLimit): array
    {
        $rows = [];
        $truncated = false;

        while (($row = $stmt->fetch(PDO::FETCH_ASSOC)) !== false) {
            if (count($rows) >= $rowLimit) {
                $truncated = true;

                break;
            }

            $rows[] = $row;
        }

        $stmt->closeCursor();

        return [$rows, $truncated];
    }

    private static function failure(string $technicalMessage): array
    {
        $technicalMessage = self::sanitizeTechnicalMessage($technicalMessage);

        return [
            'success' => false,
            'message' => 'Deine SQL-Abfrage konnte nicht ausgeführt werden.',
            'technical_message' => $technicalMessage,
            'hint' => self::learningHint($technicalMessage),
        ];
    }

    private static function sanitizeTechnicalMessage(string $message): string
    {
        $message = preg_split('/\R(?:Stack trace:|#0\b)/i', $message, 2)[0] ?? $message;
        $message = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', ' ', $message) ?? $message;
        $message = preg_replace('/\s+in\s+[A-Za-z]:\\\\[^\r\n]+$/i', '', $message) ?? $message;
        $message = preg_replace('/\s+in\s+\/(?:home|opt|srv|tmp|usr|var|www)[^\r\n]+$/i', '', $message) ?? $message;
        $message = preg_replace(
            '/\b(?:password|passwd|pwd)\s*[=:]\s*[^\s;]+/i',
            'password=[redacted]',
            $message,
        ) ?? $message;
        $message = preg_replace(
            '/\b(user|username|host|server)\s*[=:]\s*[^\s;]+/i',
            '$1=[redacted]',
            $message,
        ) ?? $message;
        $message = preg_replace('/\b(?:mysql|pgsql|sqlsrv):[^\s]+/i', '[database connection redacted]', $message)
            ?? $message;
        $message = preg_replace('/\s+/u', ' ', trim($message)) ?? trim($message);

        if ($message === '') {
            return 'Die Datenbank hat keine weitere Fehlermeldung geliefert.';
        }

        return Str::limit($message, 1200, '...');
    }

    private static function learningHint(string $technicalMessage): string
    {
        $message = strtolower($technicalMessage);

        if (
            str_contains($message, 'unknown column')
            || str_contains($message, 'no such column')
            || str_contains($message, '42s22')
        ) {
            return 'Prüfe, ob die Spalte wirklich existiert und ob Tabellenalias und Spaltenname korrekt geschrieben sind.';
        }

        if (
            str_contains($message, 'ambiguous')
            || str_contains($message, 'column in field list is ambiguous')
        ) {
            return 'Qualifiziere mehrdeutige Spalten mit dem Tabellenalias, zum Beispiel kunde.name.';
        }

        if (
            (str_contains($message, 'table') && str_contains($message, 'not found'))
            || str_contains($message, 'no such table')
            || str_contains($message, "doesn't exist")
            || str_contains($message, '42s02')
        ) {
            return 'Prüfe den Tabellennamen und kontrolliere, ob die benötigte Tabelle in der Datenbasis vorhanden ist.';
        }

        if (
            str_contains($message, 'syntax')
            || str_contains($message, '1064')
            || str_contains($message, '42000')
        ) {
            return 'Prüfe die SQL-Syntax, insbesondere Kommas, Klammern, Schlüsselwörter und die Reihenfolge der Klauseln.';
        }

        if (
            str_contains($message, 'timeout')
            || str_contains($message, 'max_statement_time')
            || str_contains($message, 'query execution was interrupted')
        ) {
            return 'Vereinfache die Abfrage und prüfe JOIN-Bedingungen sowie Filter, damit nicht unnötig viele Zeilen verarbeitet werden.';
        }

        return 'Prüfe Tabellenname, Spaltenname, JOIN-Bedingungen und SQL-Syntax.';
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
