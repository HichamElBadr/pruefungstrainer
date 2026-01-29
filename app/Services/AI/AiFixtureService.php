<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Summary of AiFixtureService
 * 
 * decode the fixture json and return an array.
 */
class AiFixtureService
{
    public function load(string $fixtureName): array
    {
        $path = resource_path("ai-fixtures/{$fixtureName}.json");

        if (!file_exists($path)) {
            throw new RuntimeException("Fixture not found: {$path}");
        }

        $raw = file_get_contents($path);
        $data = json_decode($raw, true);

        if (!is_array($data)) {
            throw new RuntimeException("Invalid JSON fixture: {$fixtureName}");
        }

        return $data;
    }
}

