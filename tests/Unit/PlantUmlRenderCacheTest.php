<?php

namespace Tests\Unit;

use App\Services\PlantUmlRenderCache;
use App\Services\PlantUmlService;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class PlantUmlRenderCacheTest extends TestCase
{
    public function test_rendered_images_are_stored_by_source_hash_and_reused(): void
    {
        Storage::fake('public');
        $source = "@startuml\nclass Kunde\n@enduml";
        $hash = hash('sha256', $source);
        $generatedPath = storage_path('framework/testing/plantuml-cache-source.png');
        $this->writePng($generatedPath);

        $renderer = Mockery::mock(PlantUmlService::class);
        $renderer->shouldReceive('generate')
            ->once()
            ->with($source)
            ->andReturn($generatedPath);

        $cache = new PlantUmlRenderCache(
            $renderer,
            app(FilesystemManager::class),
        );

        $first = $cache->cache($source);
        $second = $cache->cache($source);

        $this->assertTrue($first['rendered']);
        $this->assertFalse($second['rendered']);
        $this->assertSame($hash, $first['hash']);
        $this->assertSame("plantuml-cache/{$hash}.png", $first['path']);
        $this->assertSame($first['path'], $second['path']);
        Storage::disk('public')->assertExists($first['path']);
        $this->assertFileDoesNotExist($generatedPath);
    }

    public function test_changed_plantuml_source_uses_a_different_cache_path(): void
    {
        Storage::fake('public');
        $firstSource = "@startuml\nclass Kunde\n@enduml";
        $secondSource = "@startuml\nclass Bestellung\n@enduml";
        $firstGeneratedPath = storage_path('framework/testing/plantuml-cache-first.png');
        $secondGeneratedPath = storage_path('framework/testing/plantuml-cache-second.png');
        $this->writePng($firstGeneratedPath);
        $this->writePng($secondGeneratedPath);

        $renderer = Mockery::mock(PlantUmlService::class);
        $renderer->shouldReceive('generate')->once()->with($firstSource)->andReturn($firstGeneratedPath);
        $renderer->shouldReceive('generate')->once()->with($secondSource)->andReturn($secondGeneratedPath);

        $cache = new PlantUmlRenderCache(
            $renderer,
            app(FilesystemManager::class),
        );

        $first = $cache->cache($firstSource);
        $second = $cache->cache($secondSource);

        $this->assertNotSame($first['hash'], $second['hash']);
        $this->assertNotSame($first['path'], $second['path']);
        Storage::disk('public')->assertExists([
            $first['path'],
            $second['path'],
        ]);
    }

    private function writePng(string $path): void
    {
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        file_put_contents(
            $path,
            base64_decode(
                'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/aQ0AAAAASUVORK5CYII=',
            ),
        );
    }
}
