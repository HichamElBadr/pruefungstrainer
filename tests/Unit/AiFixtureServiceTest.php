<?php

namespace Tests\Unit;

use App\Services\AI\AiFixtureService;
use App\Services\CalculationExerciseTopicCatalog;
use App\Services\QueryHandler;
use PDO;
use Tests\TestCase;

class AiFixtureServiceTest extends TestCase
{
    public function test_sql_fixtures_can_be_loaded_by_difficulty(): void
    {
        $service = new AiFixtureService();
        $fixtures = $service->loadAll('sql');

        $this->assertGreaterThanOrEqual(9, count($fixtures));

        foreach (['easy', 'medium', 'hard'] as $difficulty) {
            $matching = array_values(array_filter(
                $fixtures,
                fn (array $fixture) => $fixture['difficulty'] === $difficulty,
            ));

            $this->assertGreaterThanOrEqual(3, count($matching), "Missing SQL fixtures for {$difficulty}.");

            $selected = $service->loadMatching('sql', ['difficulty' => $difficulty]);
            $this->assertSame($difficulty, $selected['difficulty']);
        }

        foreach ($fixtures as $fixture) {
            foreach (['title', 'difficulty', 'task', 'mysqlstatement', 'solution'] as $field) {
                $this->assertArrayHasKey($field, $fixture);
                $this->assertIsString($fixture[$field]);
                $this->assertNotSame('', trim($fixture[$field]));
            }
        }
    }

    public function test_sql_fixture_setup_sql_and_solutions_are_executable(): void
    {
        $service = new AiFixtureService();

        foreach ($service->loadAll('sql') as $fixture) {
            $pdo = new PDO('sqlite::memory:');
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            foreach (array_filter(array_map('trim', explode(';', $fixture['mysqlstatement']))) as $statement) {
                $pdo->exec($statement);
            }

            $result = QueryHandler::executeUserQuery($pdo, $fixture['solution']);

            $this->assertNull($result['error'], "SQL fixture failed: {$fixture['title']}");
            $this->assertNotEmpty($result['columns'], "SQL fixture returned no columns: {$fixture['title']}");
        }
    }

    public function test_calculation_fixtures_cover_each_topic_with_numeric_results(): void
    {
        $service = new AiFixtureService();
        $topics = new CalculationExerciseTopicCatalog();
        $fixtures = $service->loadAll('calculation');

        foreach ($topics->all() as $topic) {
            $matching = array_values(array_filter(
                $fixtures,
                fn (array $fixture) => $fixture['topic'] === $topic['slug'],
            ));

            $this->assertGreaterThanOrEqual(2, count($matching), "Missing calculation fixtures for {$topic['slug']}.");
        }

        foreach ($fixtures as $fixture) {
            foreach (['title', 'topic', 'difficulty', 'task', 'expected_result', 'expected_unit', 'sample_solution'] as $field) {
                $this->assertArrayHasKey($field, $fixture);
                $this->assertIsString($fixture[$field]);
                $this->assertNotSame('', trim($fixture[$field]));
            }

            $this->assertTrue(is_numeric($fixture['expected_result']), "Non-numeric result in {$fixture['title']}.");
            $this->assertStringContainsString($fixture['expected_result'], $fixture['sample_solution']);
        }
    }

    public function test_calculation_fixture_matching_uses_topic_and_difficulty(): void
    {
        $service = new AiFixtureService();
        $fixture = $service->loadMatching('calculation', [
            'topic' => 'prozentrechnung',
            'difficulty' => 'medium',
        ]);

        $this->assertSame('Prozentrechnung: Mehrwertsteuer berechnen', $fixture['title']);
        $this->assertSame('182.40', $fixture['expected_result']);
        $this->assertSame('Euro', $fixture['expected_unit']);
    }
}
