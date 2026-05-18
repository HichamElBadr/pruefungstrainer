<?php

namespace Tests\Unit;

use App\Services\QueryHandler;
use PDO;
use PHPUnit\Framework\TestCase;

class QueryHandlerTest extends TestCase
{
    public function test_it_accepts_single_select_queries_and_returns_structured_rows(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->exec('CREATE TABLE answers (value INTEGER)');
        $pdo->exec('INSERT INTO answers VALUES (42)');

        $result = QueryHandler::executeUserQuery($pdo, 'SELECT value FROM answers;');

        $this->assertSame(['value'], $result['columns']);
        $this->assertSame([['value' => 42]], $result['rows']);
        $this->assertNull($result['error']);
    }

    public function test_it_rejects_non_select_queries(): void
    {
        $pdo = new PDO('sqlite::memory:');

        $result = QueryHandler::executeUserQuery($pdo, 'DELETE FROM answers');

        $this->assertSame('Es sind nur SELECT-Abfragen erlaubt.', $result['error']);
    }
}
