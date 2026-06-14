<?php

namespace Tests\Unit;

use App\Exceptions\ExerciseSourceException;
use App\Services\Exercises\SqlSetupValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SqlSetupValidatorTest extends TestCase
{
    public function test_it_accepts_create_table_and_insert_values_with_semicolons_in_strings(): void
    {
        $statements = SqlSetupValidator::statements(
            'CREATE TABLE notes (id INT PRIMARY KEY, body VARCHAR(100));'
            ."INSERT INTO notes VALUES (1, 'Text; mit Semikolon');",
        );

        $this->assertCount(2, $statements);
        $this->assertStringStartsWith('CREATE TABLE', $statements[0]);
        $this->assertStringStartsWith('INSERT INTO', $statements[1]);
    }

    #[DataProvider('unsafeSetupSql')]
    public function test_it_rejects_setup_sql_outside_the_sandbox_allowlist(string $sql): void
    {
        $this->expectException(ExerciseSourceException::class);

        SqlSetupValidator::statements($sql);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function unsafeSetupSql(): array
    {
        return [
            'drop database' => ['DROP DATABASE pruefungstrainer;'],
            'qualified insert' => ['INSERT INTO pruefungstrainer.users VALUES (1);'],
            'create from another schema' => [
                'CREATE TABLE copied (id INT) AS SELECT id FROM pruefungstrainer.users;',
            ],
            'insert select' => ['INSERT INTO copied SELECT id FROM users;'],
            'federated table' => [
                "CREATE TABLE remote_data (id INT) ENGINE=FEDERATED CONNECTION='mysql://server/db/table';",
            ],
            'comments' => ['CREATE TABLE items (id INT); /* hidden */ DROP TABLE items;'],
            'delay function' => [
                'CREATE TABLE items (id INT); INSERT INTO items VALUES (SLEEP(10));',
            ],
            'server variable' => [
                'CREATE TABLE items (value VARCHAR(100)); INSERT INTO items VALUES (@@version);',
            ],
        ];
    }
}
