<?php

namespace App\Services\Exercises;

use App\Exceptions\ExerciseSourceException;

class SqlSetupValidator
{
    /**
     * @return array<int, string>
     */
    public static function statements(string $sql): array
    {
        $statements = self::splitStatements($sql);

        if ($statements === []) {
            throw new ExerciseSourceException('SQL-Setup enthaelt keine ausfuehrbaren Anweisungen.');
        }

        foreach ($statements as $index => $statement) {
            self::validateStatement($statement, $index);
        }

        return $statements;
    }

    private static function validateStatement(string $statement, int $index): void
    {
        $number = $index + 1;
        $masked = self::maskStringLiterals($statement);
        $identifier = '(?:`[A-Za-z_][A-Za-z0-9_]*`|[A-Za-z_][A-Za-z0-9_]*)';

        if (preg_match('/'.$identifier.'\s*\.\s*'.$identifier.'/i', $masked)) {
            throw new ExerciseSourceException(
                "SQL-Setup-Anweisung {$number} darf keine schemaqualifizierten Objekte verwenden.",
            );
        }

        if (str_contains($masked, '@')) {
            throw new ExerciseSourceException(
                "SQL-Setup-Anweisung {$number} darf keine Server- oder Sitzungsvariablen verwenden.",
            );
        }

        foreach (config('exercises.sql.query.forbidden_functions', []) as $function) {
            if (preg_match('/\b'.preg_quote((string) $function, '/').'\s*\(/i', $masked)) {
                throw new ExerciseSourceException(
                    "SQL-Setup-Anweisung {$number} verwendet eine nicht erlaubte SQL-Funktion.",
                );
            }
        }

        if (preg_match('/^create\s+table\b/i', $masked)) {
            if (! preg_match('/^create\s+table\s+(?:if\s+not\s+exists\s+)?'.$identifier.'\s*\(/i', $masked)) {
                throw new ExerciseSourceException(
                    "SQL-Setup-Anweisung {$number} enthaelt kein erlaubtes CREATE TABLE.",
                );
            }

            if (
                preg_match('/\b(?:data|index)\s+directory\b/i', $masked)
                || preg_match('/\btablespace\b/i', $masked)
                || preg_match('/\bengine\s*=\s*federated\b/i', $masked)
                || preg_match('/\bconnection\s*=/i', $masked)
                || preg_match('/\)\s*(?:ignore\s+|replace\s+)?(?:as\s+)?select\b/i', $masked)
            ) {
                throw new ExerciseSourceException(
                    "SQL-Setup-Anweisung {$number} verwendet eine nicht erlaubte CREATE TABLE-Option.",
                );
            }

            return;
        }

        if (preg_match('/^insert\s+into\b/i', $masked)) {
            if (! preg_match('/^insert\s+into\s+'.$identifier.'\s*(?:\([^)]*\)\s*)?values\b/is', $masked)) {
                throw new ExerciseSourceException(
                    "SQL-Setup-Anweisung {$number} muss INSERT INTO ... VALUES verwenden.",
                );
            }

            if (preg_match('/\bon\s+duplicate\s+key\b/i', $masked)) {
                throw new ExerciseSourceException(
                    "SQL-Setup-Anweisung {$number} darf ON DUPLICATE KEY nicht verwenden.",
                );
            }

            return;
        }

        throw new ExerciseSourceException(
            "SQL-Setup-Anweisung {$number} ist nicht erlaubt. Erlaubt sind nur CREATE TABLE und INSERT INTO ... VALUES.",
        );
    }

    /**
     * @return array<int, string>
     */
    private static function splitStatements(string $sql): array
    {
        $statements = [];
        $buffer = '';
        $state = 'normal';
        $length = strlen($sql);

        for ($index = 0; $index < $length; $index++) {
            $char = $sql[$index];
            $next = $index + 1 < $length ? $sql[$index + 1] : '';

            if ($state === 'single_quote' || $state === 'double_quote') {
                $quote = $state === 'single_quote' ? "'" : '"';
                $buffer .= $char;

                if ($char === '\\' && $next !== '') {
                    $buffer .= $next;
                    $index++;

                    continue;
                }

                if ($char === $quote && $next === $quote) {
                    $buffer .= $next;
                    $index++;

                    continue;
                }

                if ($char === $quote) {
                    $state = 'normal';
                }

                continue;
            }

            if ($state === 'backtick') {
                $buffer .= $char;

                if ($char === '`') {
                    if ($next === '`') {
                        $buffer .= $next;
                        $index++;
                    } else {
                        $state = 'normal';
                    }
                }

                continue;
            }

            if (
                ($char === '-' && $next === '-')
                || $char === '#'
                || ($char === '/' && $next === '*')
            ) {
                throw new ExerciseSourceException('SQL-Setup-Kommentare sind nicht erlaubt.');
            }

            if ($char === "'") {
                $state = 'single_quote';
                $buffer .= $char;

                continue;
            }

            if ($char === '"') {
                $state = 'double_quote';
                $buffer .= $char;

                continue;
            }

            if ($char === '`') {
                $state = 'backtick';
                $buffer .= $char;

                continue;
            }

            if ($char === ';') {
                $statement = trim($buffer);

                if ($statement !== '') {
                    $statements[] = $statement;
                }

                $buffer = '';

                continue;
            }

            $buffer .= $char;
        }

        if ($state !== 'normal') {
            throw new ExerciseSourceException('SQL-Setup enthaelt ein nicht abgeschlossenes Anfuehrungszeichen.');
        }

        $statement = trim($buffer);

        if ($statement !== '') {
            $statements[] = $statement;
        }

        return $statements;
    }

    private static function maskStringLiterals(string $sql): string
    {
        $masked = '';
        $state = 'normal';
        $length = strlen($sql);

        for ($index = 0; $index < $length; $index++) {
            $char = $sql[$index];
            $next = $index + 1 < $length ? $sql[$index + 1] : '';

            if ($state === 'single_quote' || $state === 'double_quote') {
                $quote = $state === 'single_quote' ? "'" : '"';
                $masked .= ' ';

                if ($char === '\\' && $next !== '') {
                    $masked .= ' ';
                    $index++;

                    continue;
                }

                if ($char === $quote && $next === $quote) {
                    $masked .= ' ';
                    $index++;

                    continue;
                }

                if ($char === $quote) {
                    $state = 'normal';
                }

                continue;
            }

            if ($char === "'") {
                $state = 'single_quote';
                $masked .= ' ';

                continue;
            }

            if ($char === '"') {
                $state = 'double_quote';
                $masked .= ' ';

                continue;
            }

            $masked .= $char;
        }

        return trim($masked);
    }
}
