<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class OllamaService
{
    private function askOllama($prompt)
    {
        $model = env('OLLAMA_MODEL');
        set_time_limit(500);

        try {
            $response = Http::timeout(500)->post('http://localhost:11434/api/generate', [
                'model' => $model,
                'prompt' => $prompt,
                'stream' => false,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['response'] ?? 'Keine Antwort vom Modell.';
            } else {
                return 'Fehler: ' . $response->status();
            }
        } catch (\Exception $e) {
            return 'Exception: ' . $e->getMessage();
        }
    }
    public function generate($prompt)
    {
        $task = $this->askOllama($prompt);
        return $task;
    }
}