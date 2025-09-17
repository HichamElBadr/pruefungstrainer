<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class OllamaController extends Controller
{
    public function showForm()
    {
        return view('sql-uebung');
    }

    public function generateTask(Request $request)
    {
        $model = env('OLLAMA_MODEL');
        $apiUrl = env('OLLAMA_API_URL');

        $prompt = "Erstelle eine MySQL-Übungsaufgabe für Fachinformatiker für Anwendungsentwicklung,
        der Azubi muss Joins nutzen müssen .";

        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            "model" => $model,
            "prompt" => $prompt
        ]));

        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);
        $output = $data['response'] ?? 'Keine Antwort vom Modell.';

        return view('sql-uebung', compact('output'));
    }
}
