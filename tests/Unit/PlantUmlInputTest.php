<?php

namespace Tests\Unit;

use App\Services\PlantUmlInput;
use PHPUnit\Framework\TestCase;

class PlantUmlInputTest extends TestCase
{
    public function test_input_without_start_marker_is_wrapped_without_diagram_specific_changes(): void
    {
        $input = "actor Kunde\nKunde -> System : Anfrage";

        $this->assertSame(
            "@startuml\n{$input}\n@enduml",
            (new PlantUmlInput)->wrap($input),
        );
    }

    public function test_input_with_start_marker_is_returned_unchanged(): void
    {
        $input = "  @startuml\nstart\nstop\n@enduml  ";

        $this->assertSame($input, (new PlantUmlInput)->wrap($input));
    }
}
