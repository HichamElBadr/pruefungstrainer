<?php

namespace App\Services;

class CalculationExerciseTopicCatalog
{
    private const TOPICS = [
        'prozentrechnung' => 'Prozentrechnung',
        'dreisatz' => 'Dreisatz',
        'multiplikation' => 'Multiplikation',
        'division' => 'Division',
        'speichergroessen' => 'Speichergrößen',
        'stromverbrauch' => 'Stromverbrauch',
        'hardwarekosten' => 'Hardwarekosten',
    ];

    public function all(): array
    {
        $topics = [];

        foreach (self::TOPICS as $slug => $label) {
            $topics[] = [
                'slug' => $slug,
                'label' => $label,
            ];
        }

        return $topics;
    }

    public function find(string $slug): ?array
    {
        if (!array_key_exists($slug, self::TOPICS)) {
            return null;
        }

        return [
            'slug' => $slug,
            'label' => self::TOPICS[$slug],
        ];
    }
}
