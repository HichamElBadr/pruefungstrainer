<?php

namespace Tests\Unit;

use App\Services\QueryHandler;
use PDO;
use PDOStatement;
use RuntimeException;
use Tests\TestCase;

class QueryHandlerTest extends TestCase
{
    public function test_it_accepts_single_select_queries_and_returns_structured_rows(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->exec('CREATE TABLE answers (value INTEGER)');
        $pdo->exec('INSERT INTO answers VALUES (42)');

        $result = QueryHandler::executeUserQuery($pdo, 'SELECT value FROM answers;');

        $this->assertTrue($result['success']);
        $this->assertSame(['value'], $result['columns']);
        $this->assertSame([['value' => 42]], $result['rows']);
        $this->assertNull($result['message']);
    }

    public function test_it_keeps_column_names_for_empty_select_results(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->exec('CREATE TABLE products (product_name TEXT, price REAL)');
        $pdo->exec("INSERT INTO products VALUES ('Monitor', 189.00)");

        $result = QueryHandler::executeUserQuery(
            $pdo,
            'SELECT product_name, price FROM products WHERE price BETWEEN 50 AND 100 ORDER BY product_name ASC;',
        );

        $this->assertTrue($result['success']);
        $this->assertSame(['product_name', 'price'], $result['columns']);
        $this->assertSame([], $result['rows']);
        $this->assertSame('Keine Ergebnisse gefunden.', $result['message']);
    }

    public function test_it_rejects_non_select_and_multiple_queries_with_structured_errors(): void
    {
        $pdo = new PDO('sqlite::memory:');

        $nonSelect = QueryHandler::executeUserQuery($pdo, 'DELETE FROM answers');
        $multiple = QueryHandler::executeUserQuery($pdo, 'SELECT 1; SELECT 2');

        $this->assertFalse($nonSelect['success']);
        $this->assertSame('Es sind nur SELECT-Abfragen erlaubt.', $nonSelect['technical_message']);
        $this->assertNotEmpty($nonSelect['message']);
        $this->assertNotEmpty($nonSelect['hint']);

        $this->assertFalse($multiple['success']);
        $this->assertSame('Es ist nur eine einzelne SELECT-Abfrage erlaubt.', $multiple['technical_message']);
    }

    public function test_it_rejects_delay_file_system_schema_and_locking_constructs(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $queries = [
            'SELECT SLEEP(5)' => 'Diese SQL-Funktion ist in Uebungsabfragen nicht erlaubt.',
            "SELECT LOAD_FILE('/etc/passwd')" => 'Diese SQL-Funktion ist in Uebungsabfragen nicht erlaubt.',
            'SELECT * FROM information_schema.tables' => 'Der Zugriff auf Systemdatenbanken ist nicht erlaubt.',
            "SELECT 'data' INTO OUTFILE '/tmp/result.txt'" => 'Dateizugriffe sind in Uebungsabfragen nicht erlaubt.',
            'SELECT 1 FOR UPDATE' => 'Sperrende SELECT-Abfragen sind nicht erlaubt.',
            'SELECT @@version' => 'Der Zugriff auf Server- oder Sitzungsvariablen ist nicht erlaubt.',
            'SELECT @answer := 42' => 'Der Zugriff auf Server- oder Sitzungsvariablen ist nicht erlaubt.',
            'SELECT /*!50000 SLEEP(5) */ 1' => 'Ausfuehrbare SQL-Kommentare sind nicht erlaubt.',
            'SELECT /*M! SLEEP(5) */ 1' => 'Ausfuehrbare SQL-Kommentare sind nicht erlaubt.',
        ];

        foreach ($queries as $sql => $expectedError) {
            $result = QueryHandler::executeUserQuery($pdo, $sql);

            $this->assertFalse($result['success'], $sql);
            $this->assertSame($expectedError, $result['technical_message'], $sql);
        }
    }

    public function test_it_does_not_treat_forbidden_text_inside_strings_or_comments_as_executable_sql(): void
    {
        $pdo = new PDO('sqlite::memory:');

        $result = QueryHandler::executeUserQuery(
            $pdo,
            "/* SLEEP(5) */ SELECT 'information_schema.tables; SLEEP(5)' AS example;",
        );

        $this->assertTrue($result['success']);
        $this->assertSame(
            [['example' => 'information_schema.tables; SLEEP(5)']],
            $result['rows'],
        );
    }

    public function test_it_limits_returned_rows(): void
    {
        config(['exercises.sql.query.row_limit' => 2]);
        $pdo = new PDO('sqlite::memory:');
        $pdo->exec('CREATE TABLE items (id INTEGER)');
        $pdo->exec('INSERT INTO items VALUES (1), (2), (3)');

        $result = QueryHandler::executeUserQuery($pdo, 'SELECT id FROM items ORDER BY id');

        $this->assertTrue($result['success']);
        $this->assertSame([['id' => 1], ['id' => 2]], $result['rows']);
        $this->assertSame('Die Ausgabe wurde auf die ersten 2 Zeilen begrenzt.', $result['message']);
    }

    public function test_database_errors_return_the_sanitized_exception_message_and_a_hint(): void
    {
        $pdo = new PDO('sqlite::memory:');

        $result = QueryHandler::executeUserQuery($pdo, 'SELECT secret_column FROM missing_table');

        $this->assertFalse($result['success']);
        $this->assertSame(
            'Deine SQL-Abfrage konnte nicht ausgeführt werden.',
            $result['message'],
        );
        $this->assertStringContainsString('missing_table', $result['technical_message']);
        $this->assertNotEmpty($result['hint']);
    }

    public function test_database_error_details_are_redacted_before_display(): void
    {
        $pdo = new class extends PDO
        {
            public function __construct() {}

            public function getAttribute(int $attribute): mixed
            {
                return $attribute === PDO::ATTR_DRIVER_NAME ? 'sqlite' : null;
            }

            public function query(
                string $query,
                ?int $fetchMode = null,
                mixed ...$fetchModeArgs,
            ): PDOStatement|false {
                throw new RuntimeException(
                    "SQLSTATE[42S22]: Unknown column 'kunde.name'; password=secret host=127.0.0.1 "
                    ."in C:\\xampp\\htdocs\\pruefungstrainer\\secret.php\nStack trace:\n#0 internal",
                );
            }
        };

        $result = QueryHandler::executeUserQuery($pdo, 'SELECT kunde.name FROM kunde');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('SQLSTATE[42S22]', $result['technical_message']);
        $this->assertStringContainsString("Unknown column 'kunde.name'", $result['technical_message']);
        $this->assertStringNotContainsString('secret', $result['technical_message']);
        $this->assertStringNotContainsString('127.0.0.1', $result['technical_message']);
        $this->assertStringNotContainsString('C:\\xampp', $result['technical_message']);
        $this->assertStringNotContainsString('Stack trace', $result['technical_message']);
    }
}
