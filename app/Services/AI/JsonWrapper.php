<?php

namespace App\Services\AI;

class JsonWrapper
{
    /**
     * Parse raw AI response into array.
     *
     * @param string $rawResponse
     * @return array
     * @throws \Exception
     */
    public static function parse(string $rawResponse): array
    {
        // 1. Alles zwischen ```json ... ``` extrahieren
        if (preg_match('/```json(.*?)```/s', $rawResponse, $matches)) {
            $jsonString = trim($matches[1]);
        } else {
            $jsonString = trim($rawResponse);
        }

        // 2. Entferne unsichtbare Steuerzeichen außer \n und \t
        $jsonString = preg_replace('/[\x00-\x1F\x7F]/u', '', $jsonString);

        // 3. Ersetze echte Zeilenumbrüche innerhalb von Strings durch \n
        $jsonString = preg_replace("/\r\n|\r|\n/", '\\n', $jsonString);

        // 4. Optional: einfache Anführungszeichen zu doppelten konvertieren,
        // nur innerhalb von Strings, nicht bei MySQL Code in insert Statements
        // Hier besser **nicht automatisch**, sonst zerstört es SQL
        // $jsonString = preg_replace("/'([^']*)'/", '"$1"', $jsonString);

        // 5. JSON decoden
        $data = json_decode($jsonString, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            // Extra Logging: Rohstring mitgeben
            throw new \Exception("Ungültiges JSON von der KI: " . json_last_error_msg() . "\nRaw Response:\n" . $rawResponse);
        }

        return $data;
    }
}
