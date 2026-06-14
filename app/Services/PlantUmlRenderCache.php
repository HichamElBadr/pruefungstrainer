<?php

namespace App\Services;

use Illuminate\Filesystem\FilesystemManager;
use InvalidArgumentException;
use RuntimeException;

class PlantUmlRenderCache
{
    private const DIRECTORY = 'plantuml-cache';

    public function __construct(
        private readonly PlantUmlService $plantUmlService,
        private readonly FilesystemManager $filesystems,
    ) {}

    /**
     * @return array{hash: string, path: string, rendered: bool}
     */
    public function cache(string $plantUml): array
    {
        if (trim($plantUml) === '') {
            throw new InvalidArgumentException('PlantUML-Code darf nicht leer sein.');
        }

        $hash = hash('sha256', $plantUml);
        $path = self::DIRECTORY.'/'.$hash.'.png';
        $disk = $this->filesystems->disk('public');

        if ($disk->exists($path)) {
            return $this->result($hash, $path, false);
        }

        $generatedPath = $this->plantUmlService->generate($plantUml);

        try {
            $contents = file_get_contents($generatedPath);

            if ($contents === false) {
                throw new RuntimeException('Das gerenderte PlantUML-Bild konnte nicht gelesen werden.');
            }

            if (! $disk->put($path, $contents)) {
                throw new RuntimeException('Das gerenderte PlantUML-Bild konnte nicht gespeichert werden.');
            }
        } finally {
            if (is_file($generatedPath)) {
                unlink($generatedPath);
            }
        }

        return $this->result($hash, $path, true);
    }

    /**
     * @return array{hash: string, path: string, rendered: bool}
     */
    private function result(string $hash, string $path, bool $rendered): array
    {
        return [
            'hash' => $hash,
            'path' => $path,
            'rendered' => $rendered,
        ];
    }
}
