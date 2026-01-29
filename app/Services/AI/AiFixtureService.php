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
    public function load(string $path): array
    {
        if (!Storage::disk('ai_fixtures')->exists($path)) {
            throw new RuntimeException("Fixture not found: {$path}");
        }

        $raw = Storage::disk('ai_fixtures')->get($path);
        $data = json_decode($raw, true);

        if (!is_array($data)) {
            throw new RuntimeException("Invalid JSON fixture: {$path}");
        }

        return $data;
    }
}
