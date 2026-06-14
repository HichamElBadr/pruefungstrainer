<?php

namespace App\Services\Exercises;

class ExerciseHintNormalizer
{
    /**
     * @return array<int, array{level: int, title: string, text: string}>
     */
    public function normalize(mixed $hints): array
    {
        if (! is_array($hints)) {
            return [];
        }

        $normalized = [];

        foreach ($hints as $hint) {
            if (! is_array($hint)) {
                continue;
            }

            $level = $hint['level'] ?? null;
            $title = $hint['title'] ?? null;
            $text = $hint['text'] ?? null;

            if (
                ! is_int($level)
                || ! in_array($level, [1, 2, 3], true)
                || ! is_string($title)
                || trim($title) === ''
                || ! is_string($text)
                || trim($text) === ''
                || isset($normalized[$level])
            ) {
                continue;
            }

            $normalized[$level] = [
                'level' => $level,
                'title' => trim($title),
                'text' => trim($text),
            ];
        }

        ksort($normalized);

        return array_slice(array_values($normalized), 0, 3);
    }
}
