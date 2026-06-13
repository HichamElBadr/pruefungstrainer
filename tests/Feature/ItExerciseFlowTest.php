<?php

namespace Tests\Feature;

use App\Contracts\ExerciseProvider;
use App\Exceptions\ExerciseSourceException;
use App\Models\Exercise;
use App\Models\User;
use App\Services\DatabaseManager;
use App\Services\PlantUmlService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Mockery;
use PDO;
use Tests\TestCase;

class ItExerciseFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_users_are_redirected_from_it_exercises(): void
    {
        $this->get(route('sql-uebung'))->assertRedirect(route('login'));
        $this->get(route('calculation-exercises.index'))->assertRedirect(route('login'));
        $this->get(route('uml.form'))->assertRedirect(route('login'));
    }

    public function test_sql_difficulty_selection_page_shows_options(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('sql-uebung'))
            ->assertOk()
            ->assertSeeText('SQL-Aufgaben')
            ->assertSeeText('Einfach')
            ->assertSeeText('Mittel')
            ->assertSeeText('Schwer')
            ->assertSee(route('sql-uebung.generate', 'easy'), false)
            ->assertSee(route('sql-uebung.generate', 'medium'), false)
            ->assertSee(route('sql-uebung.generate', 'hard'), false);
    }

    public function test_easy_sql_exercise_loads_from_json(): void
    {
        $this->assertSqlGenerationForDifficulty('easy', 'Einfach');
    }

    public function test_medium_sql_exercise_loads_from_json(): void
    {
        $this->assertSqlGenerationForDifficulty('medium', 'Mittel');
    }

    public function test_hard_sql_exercise_loads_from_json(): void
    {
        $this->assertSqlGenerationForDifficulty('hard', 'Schwer');
    }

    public function test_sql_exercise_execution_uses_current_json_exercise(): void
    {
        $user = User::factory()->create();
        $pdo = new PDO('sqlite::memory:');
        $pdo->exec('CREATE TABLE products (name TEXT, price REAL)');
        $pdo->exec("INSERT INTO products VALUES ('Maus', 24.50)");

        $provider = Mockery::mock(ExerciseProvider::class);
        $provider->shouldReceive('random')
            ->once()
            ->with('sql', 'easy')
            ->andReturn($this->sqlFixture());
        $this->app->instance(ExerciseProvider::class, $provider);

        $dbManager = Mockery::mock(DatabaseManager::class);
        $dbManager->shouldReceive('createTemporaryDatabase')->once()->andReturn('sql_exercise_test');
        $dbManager->shouldReceive('createMySqlExercise')->once();
        $dbManager->shouldReceive('getTables')->twice()->andReturn([
            'products' => [
                ['name' => 'Maus', 'price' => 24.50],
            ],
        ]);
        $dbManager->shouldReceive('connectToDatabase')
            ->once()
            ->with('sql_exercise_test')
            ->andReturn($pdo);
        $this->app->instance(DatabaseManager::class, $dbManager);

        $response = $this->actingAs($user)->post(route('sql-uebung.generate', 'easy'));
        $exercise = Exercise::firstOrFail();

        $response
            ->assertOk()
            ->assertSeeText('Gib alle Produkte aus.')
            ->assertSeeText('Beispielaufgabe')
            ->assertSeeText('Schwierigkeit: Einfach')
            ->assertSessionHas('sql_temp_db', 'sql_exercise_test')
            ->assertSessionHas('sql_exercise_id', $exercise->id);

        $this->assertSame('json', $exercise->source);
        $this->assertStringContainsString('"id":"sql-easy-test"', $exercise->prompt);

        $this->actingAs($user)
            ->post(route('sql-uebung'), ['sql_input' => 'SELECT name, price FROM products'])
            ->assertOk()
            ->assertSeeText('Deine Ausgabe')
            ->assertSeeText('Erwartete Ausgabe')
            ->assertSeeText('Maus')
            ->assertSeeText('WHERE filtert die Produkte.');
    }

    public function test_sql_source_errors_are_shown_on_the_selection_page(): void
    {
        $user = User::factory()->create();
        $provider = Mockery::mock(ExerciseProvider::class);
        $provider->shouldReceive('random')
            ->once()
            ->andThrow(new ExerciseSourceException('Ungueltiges JSON in broken.json.'));
        $this->app->instance(ExerciseProvider::class, $provider);

        $this->actingAs($user)
            ->post(route('sql-uebung.generate', 'easy'))
            ->assertRedirect(route('sql-uebung'))
            ->assertSessionHasErrors('exercise_source');

        $this->actingAs($user)
            ->get(route('sql-uebung'))
            ->assertOk()
            ->assertSeeText('Ungueltiges JSON in broken.json.');
    }

    public function test_calculation_overview_supports_all_difficulties(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('calculation-exercises.index', ['difficulty' => 'hard']))
            ->assertOk()
            ->assertSeeText('Prozentrechnung')
            ->assertSeeText('Dreisatz')
            ->assertSeeText('Speichergrößen')
            ->assertSeeText('Schwierigkeit: Schwer');
    }

    public function test_calculation_exercise_loads_from_json_and_checks_solution(): void
    {
        $user = User::factory()->create();
        $provider = Mockery::mock(ExerciseProvider::class);
        $provider->shouldReceive('random')
            ->once()
            ->with('calculation', 'hard', ['topic' => 'hardwarekosten'])
            ->andReturn($this->calculationFixture());
        $this->app->instance(ExerciseProvider::class, $provider);

        $response = $this->actingAs($user)
            ->post(route('calculation-exercises.generate', 'hardwarekosten'), [
                'difficulty' => 'hard',
            ]);
        $exercise = Exercise::firstOrFail();

        $response
            ->assertOk()
            ->assertSeeText('Beispielaufgabe')
            ->assertSeeText('Schwierigkeit: Schwer')
            ->assertSeeText('Server-Rack kalkulieren')
            ->assertSeeText('Einheit: Euro')
            ->assertSessionHas('calculation_exercise_id', $exercise->id);

        $this->assertSame('hard', $exercise->difficulty);
        $this->assertSame('json', $exercise->source);
        $this->assertSame('2400', $exercise->solution);
        $this->assertStringContainsString('"topic":"hardwarekosten"', $exercise->prompt);

        $this->actingAs($user)
            ->post(route('calculation-exercises.check'), ['user_solution' => '2400'])
            ->assertOk()
            ->assertSeeText('Deine Lösung ist korrekt.')
            ->assertSee('value="2400"', false)
            ->assertSeeText('2400 Euro')
            ->assertSeeText('Die Einzelkosten werden addiert.');
    }

    public function test_uml_page_loads_a_json_exercise_for_the_selected_difficulty(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('uml.form', ['difficulty' => 'easy']))
            ->assertOk()
            ->assertSeeText('Beispielaufgabe')
            ->assertSeeText('Schwierigkeit: Einfach')
            ->assertSeeText('UML-Eingabe')
            ->assertSeeText('Musterloesung anzeigen');
    }

    public function test_uml_exercise_still_renders_simplified_input_with_plantuml(): void
    {
        $user = User::factory()->create();
        $pngPath = storage_path('framework/testing/uml.png');

        if (! is_dir(dirname($pngPath))) {
            mkdir(dirname($pngPath), 0755, true);
        }

        file_put_contents(
            $pngPath,
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/aQ0AAAAASUVORK5CYII='),
        );

        $plantUml = Mockery::mock(PlantUmlService::class);
        $plantUml->shouldReceive('generate')
            ->once()
            ->withArgs(fn (string $uml): bool => str_contains($uml, 'class Person {')
                && str_contains($uml, 'Person -> Hund : besitzt'))
            ->andReturn($pngPath);
        $this->app->instance(PlantUmlService::class, $plantUml);

        $this->actingAs($user)
            ->post(route('uml.render'), [
                'difficulty' => 'medium',
                'uml_text' => implode("\n", [
                    'class Person',
                    '- name : String',
                    '',
                    'class Hund',
                    '+ bellen() : void',
                    '',
                    'Person -> Hund : besitzt',
                ]),
            ])
            ->assertOk()
            ->assertSee('data:image/png;base64,', false);
    }

    public function test_normal_exercise_loading_sends_no_http_requests(): void
    {
        Http::fake();
        $user = User::factory()->create();
        $dbManager = Mockery::mock(DatabaseManager::class);
        $dbManager->shouldReceive('createTemporaryDatabase')->once()->andReturn('sql_exercise_no_http');
        $dbManager->shouldReceive('createMySqlExercise')->once();
        $dbManager->shouldReceive('getTables')->once()->andReturn([]);
        $this->app->instance(DatabaseManager::class, $dbManager);

        $this->actingAs($user)->post(route('sql-uebung.generate', 'easy'))->assertOk();
        $this->actingAs($user)
            ->post(route('calculation-exercises.generate', 'prozentrechnung'), ['difficulty' => 'medium'])
            ->assertOk();
        $this->actingAs($user)->get(route('uml.form'))->assertOk();

        Http::assertNothingSent();
    }

    public function test_invalid_difficulty_values_are_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('sql-uebung.generate', 'expert'))
            ->assertNotFound();
        $this->actingAs($user)
            ->get(route('calculation-exercises.index', ['difficulty' => 'expert']))
            ->assertNotFound();
        $this->actingAs($user)
            ->get(route('uml.form', ['difficulty' => 'expert']))
            ->assertNotFound();
    }

    private function assertSqlGenerationForDifficulty(string $difficulty, string $label): void
    {
        $user = User::factory()->create();
        $dbName = 'sql_exercise_test_'.$difficulty;
        $dbManager = Mockery::mock(DatabaseManager::class);
        $dbManager->shouldReceive('createTemporaryDatabase')->once()->andReturn($dbName);
        $dbManager->shouldReceive('createMySqlExercise')
            ->once()
            ->withArgs(fn (string $sql, string $database): bool => $database === $dbName
                && str_contains($sql, 'CREATE TABLE'));
        $dbManager->shouldReceive('getTables')->once()->with($dbName)->andReturn([]);
        $this->app->instance(DatabaseManager::class, $dbManager);

        $response = $this->actingAs($user)
            ->post(route('sql-uebung.generate', $difficulty));
        $exercise = Exercise::query()->latest('id')->firstOrFail();

        $response
            ->assertOk()
            ->assertSeeText('Beispielaufgabe')
            ->assertSeeText('Schwierigkeit: '.$label)
            ->assertSessionHas('sql_temp_db', $dbName)
            ->assertSessionHas('sql_exercise_id', $exercise->id);

        $this->assertSame($difficulty, $exercise->difficulty);
        $this->assertSame('json', $exercise->source);
    }

    /**
     * @return array<string, mixed>
     */
    private function sqlFixture(): array
    {
        return [
            'id' => 'sql-easy-test',
            'type' => 'sql',
            'difficulty' => 'easy',
            'topic' => 'select',
            'title' => 'Produkte filtern',
            'task' => 'Gib alle Produkte aus.',
            'setup_sql' => 'CREATE TABLE products (name VARCHAR(80), price DECIMAL(10,2));',
            'solution' => 'SELECT name, price FROM products;',
            'explanation' => 'WHERE filtert die Produkte.',
            'tags' => ['SQL'],
            'source' => 'json',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function calculationFixture(): array
    {
        return [
            'id' => 'calc-hard-test',
            'type' => 'calculation',
            'difficulty' => 'hard',
            'topic' => 'hardwarekosten',
            'title' => 'Server-Rack kalkulieren',
            'task' => 'Berechne die Gesamtkosten eines Server-Racks.',
            'expected_result' => '2400',
            'unit' => 'Euro',
            'solution_steps' => '1200 + 800 + 400 = 2400 Euro.',
            'explanation' => 'Die Einzelkosten werden addiert.',
            'tags' => ['Rechnen'],
            'source' => 'json',
        ];
    }
}
