<?php

namespace App\Services;

class PlantUmlInput
{
    public function wrap(string $input): string
    {
        if (preg_match('/@startuml\b/i', $input)) {
            return $input;
        }

        return "@startuml\n".trim($input)."\n@enduml";
    }
}
