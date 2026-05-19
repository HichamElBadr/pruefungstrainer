<?php

namespace Tests\Unit;

use App\Services\AI\AiFixtureService;
use App\Services\AI\AiGatewayClient;
use App\Services\AI\AiResponseProvider;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class AiResponseProviderTest extends TestCase
{
    public function test_calculation_generation_falls_back_to_matching_fixture_after_gateway_failure(): void
    {
        config(['services.ai.mode' => 'ollama']);

        $gateway = Mockery::mock(AiGatewayClient::class);
        $gateway->shouldReceive('generateCalculation')
            ->once()
            ->andThrow(new RuntimeException('gateway down'));

        $fixtures = Mockery::mock(AiFixtureService::class);
        $fixtures->shouldReceive('loadMatching')
            ->once()
            ->with(
                'calculation',
                ['topic' => 'dreisatz', 'difficulty' => 'medium'],
                ['topic' => 'dreisatz'],
            )
            ->andReturn([
                'title' => 'Dreisatz: Softwarelizenzen hochrechnen',
                'task' => '5 Softwarelizenzen kosten zusammen 175 Euro. Wie viel Euro kosten 12 gleich teure Softwarelizenzen?',
                'expected_result' => '420',
                'expected_unit' => 'Euro',
                'sample_solution' => 'Das erwartete Ergebnis ist 420 Euro.',
            ]);

        $provider = new AiResponseProvider($gateway, $fixtures);
        $result = $provider->getCalculation([
            'topic_slug' => 'dreisatz',
            'topic' => 'Dreisatz',
            'difficulty' => 'medium',
        ]);

        $this->assertSame('420', $result['expected_result']);
        $this->assertSame('Euro', $result['expected_unit']);
    }
}
