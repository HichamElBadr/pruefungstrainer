<?php

namespace Tests\Unit;

use App\Services\Exercises\ExerciseHintNormalizer;
use PHPUnit\Framework\TestCase;

class ExerciseHintNormalizerTest extends TestCase
{
    public function test_missing_or_non_array_hints_become_an_empty_array(): void
    {
        $normalizer = new ExerciseHintNormalizer;

        $this->assertSame([], $normalizer->normalize(null));
        $this->assertSame([], $normalizer->normalize('invalid'));
    }

    public function test_hints_are_filtered_sorted_trimmed_and_limited_to_valid_levels(): void
    {
        $hints = (new ExerciseHintNormalizer)->normalize([
            ['level' => 3, 'title' => ' Starker Hinweis ', 'text' => ' Dritter Text '],
            ['level' => '1', 'title' => 'Falscher Leveltyp', 'text' => 'Ignorieren'],
            ['level' => 1, 'title' => ' Kleiner Hinweis ', 'text' => ' Erster Text '],
            ['level' => 2, 'title' => '', 'text' => 'Unvollständig'],
            ['level' => 4, 'title' => 'Ungültiger Level', 'text' => 'Ignorieren'],
            ['level' => 2, 'title' => 'Konkreter Hinweis', 'text' => 'Zweiter Text'],
            ['level' => 1, 'title' => 'Doppelter Level', 'text' => 'Ignorieren'],
            'invalid',
        ]);

        $this->assertSame([1, 2, 3], array_column($hints, 'level'));
        $this->assertSame('Kleiner Hinweis', $hints[0]['title']);
        $this->assertSame('Erster Text', $hints[0]['text']);
        $this->assertCount(3, $hints);
    }
}
