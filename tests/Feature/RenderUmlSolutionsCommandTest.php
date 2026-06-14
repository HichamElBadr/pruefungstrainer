<?php

namespace Tests\Feature;

use App\Services\Exercises\JsonExerciseProvider;
use App\Services\PlantUmlRenderCache;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class RenderUmlSolutionsCommandTest extends TestCase
{
    public function test_command_reports_rendered_and_reused_diagrams(): void
    {
        $provider = Mockery::mock(JsonExerciseProvider::class);
        $provider->shouldReceive('all')->once()->with('uml', 'easy')->andReturn([
            $this->exercise('uml-easy-001', 'first'),
        ]);
        $provider->shouldReceive('all')->once()->with('uml', 'medium')->andReturn([
            $this->exercise('uml-medium-001', 'second'),
        ]);
        $provider->shouldReceive('all')->once()->with('uml', 'hard')->andReturn([]);
        $this->app->instance(JsonExerciseProvider::class, $provider);

        $cache = Mockery::mock(PlantUmlRenderCache::class);
        $cache->shouldReceive('cache')->once()->with('first')->andReturn($this->cacheResult(true));
        $cache->shouldReceive('cache')->once()->with('second')->andReturn($this->cacheResult(false));
        $this->app->instance(PlantUmlRenderCache::class, $cache);

        $this->artisan('exercises:render-uml-solutions')
            ->expectsOutput('UML solution cache: 1 rendered, 1 reused, 0 failed.')
            ->assertExitCode(0);
    }

    public function test_command_continues_after_an_invalid_solution_and_prints_its_id(): void
    {
        $provider = Mockery::mock(JsonExerciseProvider::class);
        $provider->shouldReceive('all')->once()->with('uml', 'easy')->andReturn([
            $this->exercise('uml-invalid', 'invalid'),
            $this->exercise('uml-valid', 'valid'),
        ]);
        $provider->shouldReceive('all')->once()->with('uml', 'medium')->andReturn([]);
        $provider->shouldReceive('all')->once()->with('uml', 'hard')->andReturn([]);
        $this->app->instance(JsonExerciseProvider::class, $provider);

        $cache = Mockery::mock(PlantUmlRenderCache::class);
        $cache->shouldReceive('cache')
            ->once()
            ->with('invalid')
            ->andThrow(new RuntimeException('PlantUML syntax error'));
        $cache->shouldReceive('cache')->once()->with('valid')->andReturn($this->cacheResult(true));
        $this->app->instance(PlantUmlRenderCache::class, $cache);

        $this->artisan('exercises:render-uml-solutions')
            ->expectsOutput('[uml-invalid] PlantUML syntax error')
            ->expectsOutput('UML solution cache: 1 rendered, 0 reused, 1 failed.')
            ->assertExitCode(1);
    }

    /**
     * @return array<string, mixed>
     */
    private function exercise(string $id, string $solution): array
    {
        return [
            'id' => $id,
            'solution_plantuml' => $solution,
        ];
    }

    /**
     * @return array{hash: string, path: string, rendered: bool}
     */
    private function cacheResult(bool $rendered): array
    {
        return [
            'hash' => 'hash',
            'path' => 'plantuml-cache/hash.png',
            'rendered' => $rendered,
        ];
    }
}
