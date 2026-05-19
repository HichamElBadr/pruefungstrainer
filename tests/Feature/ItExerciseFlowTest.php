<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Exercise;
use App\Models\User;
use App\Services\AI\AiResponseProvider;
use App\Services\DatabaseManager;
use App\Services\PlantUmlService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PDO;
use Tests\TestCase;

class ItExerciseFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.ai.mode' => 'fixtures']);
    }

    public function test_guest_users_are_redirected_from_it_exercises(): void
    {
        $this->get(route('sql-uebung'))->assertRedirect(route('login'));
        $this->get(route('calculation-exercises.index'))->assertRedirect(route('login'));
        $this->get(route('uml.form'))->assertRedirect(route('login'));
    }

    public function test_rechenaufgaben_navigation_link_is_visible_and_works(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSeeText('Prüfungstrainer')
            ->assertSeeText('IT-Aufgaben üben')
            ->assertSeeText($user->name)
            ->assertSeeText('Profil')
            ->assertSeeText('Abmelden')
            ->assertSee(route('profile.edit'), false)
            ->assertSeeText('Rechenaufgaben')
            ->assertSee(route('calculation-exercises.index'), false);

        $this->actingAs($user)
            ->get(route('calculation-exercises.index'))
            ->assertOk()
            ->assertSeeText('Thema auswählen');
    }

    public function test_sql_difficulty_selection_page_shows_options(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('sql-uebung'))
            ->assertOk()
            ->assertSeeText('SQL-Aufgaben')
            ->assertSeeText('Übungsstufe auswählen')
            ->assertSeeText('Grundlagen mit SELECT, WHERE und ORDER BY.')
            ->assertSeeText('Abfragen mit JOINs, mehreren Tabellen und Bedingungen.')
            ->assertSeeText('Komplexere Aufgaben mit GROUP BY, HAVING und Aggregationen.')
            ->assertSeeText('Einfach')
            ->assertSeeText('Mittel')
            ->assertSeeText('Schwer')
            ->assertSee(route('sql-uebung.generate', 'easy'), false)
            ->assertSee(route('sql-uebung.generate', 'medium'), false)
            ->assertSee(route('sql-uebung.generate', 'hard'), false);
    }

    public function test_generating_easy_sql_exercise_creates_matching_exercise(): void
    {
        $this->assertSqlGenerationForDifficulty('easy', 'Einfach');
    }

    public function test_generating_medium_sql_exercise_creates_matching_exercise(): void
    {
        $this->assertSqlGenerationForDifficulty('medium', 'Mittel');
    }

    public function test_generating_hard_sql_exercise_creates_matching_exercise(): void
    {
        $this->assertSqlGenerationForDifficulty('hard', 'Schwer');
    }

    public function test_ai_generated_sql_exercise_shows_source_badge(): void
    {
        Category::create(['name' => 'SQL']);
        $user = User::factory()->create();

        $ai = Mockery::mock(AiResponseProvider::class);
        $ai->shouldReceive('getSql')
            ->once()
            ->andReturn([
                'source' => 'generated',
                'task' => 'Erstelle eine SQL-Abfrage, die alle Produkte ausgibt.',
                'mysqlstatement' => 'CREATE TABLE produkte (id INT PRIMARY KEY, name VARCHAR(80));',
                'solution' => 'SELECT name FROM produkte;',
            ]);
        $this->app->instance(AiResponseProvider::class, $ai);

        $dbManager = Mockery::mock(DatabaseManager::class);
        $dbManager->shouldReceive('createTemporaryDatabase')->once()->andReturn('sql_exercise_generated');
        $dbManager->shouldReceive('createMySqlExercise')->once();
        $dbManager->shouldReceive('getTables')->once()->andReturn([
            'produkte' => [
                ['id' => 1, 'name' => 'Monitor'],
            ],
        ]);
        $this->app->instance(DatabaseManager::class, $dbManager);

        $response = $this->actingAs($user)
            ->post(route('sql-uebung.generate', 'easy'));

        $exercise = Exercise::firstOrFail();

        $response
            ->assertOk()
            ->assertSeeText('KI-generiert');

        $this->assertSame('generated', $exercise->source);
    }

    public function test_invalid_sql_difficulty_values_are_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('sql-uebung.generate', 'expert'))
            ->assertNotFound();
    }

    public function test_sql_exercise_execution_uses_current_fixture_exercise(): void
    {
        Category::create(['name' => 'SQL']);
        $user = User::factory()->create();
        $pdo = new PDO('sqlite::memory:');
        $pdo->exec('CREATE TABLE produkte (name TEXT, preis REAL)');
        $pdo->exec("INSERT INTO produkte VALUES ('Maus', 24.50)");

        $dbManager = Mockery::mock(DatabaseManager::class);
        $dbManager->shouldReceive('createTemporaryDatabase')->once()->andReturn('sql_exercise_test');
        $dbManager->shouldReceive('createMySqlExercise')->once();
        $dbManager->shouldReceive('getTables')->andReturn([
            'produkte' => [
                ['name' => 'Maus', 'preis' => 24.50],
            ],
        ]);
        $dbManager->shouldReceive('connectToDatabase')->once()->with('sql_exercise_test')->andReturn($pdo);
        $this->app->instance(DatabaseManager::class, $dbManager);

        $response = $this->actingAs($user)->post(route('sql-uebung.generate', 'easy'));

        $exercise = Exercise::firstOrFail();

        $response
            ->assertOk()
            ->assertSeeText('Erstelle eine SQL-Abfrage')
            ->assertSeeText('Beispielaufgabe')
            ->assertSeeText('Schwierigkeit: Einfach')
            ->assertSessionHas('sql_temp_db', 'sql_exercise_test')
            ->assertSessionHas('sql_exercise_id', $exercise->id);

        $this->assertSame($user->id, $exercise->user_id);
        $this->assertSame('easy', $exercise->difficulty);
        $this->assertSame('fixture', $exercise->source);

        $this->actingAs($user)
            ->post(route('sql-uebung'), ['sql_input' => 'SELECT name, preis FROM produkte'])
            ->assertOk()
            ->assertSeeText('Beispielaufgabe')
            ->assertSeeText('Schwierigkeit: Einfach')
            ->assertSeeText('name')
            ->assertSeeText('Maus');
    }

    public function test_calculation_exercise_topic_overview_is_visible(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('calculation-exercises.index'))
            ->assertOk()
            ->assertSeeText('Prozentrechnung')
            ->assertSeeText('Rabatte, Preisänderungen und Prozentwerte berechnen.')
            ->assertSeeText('Dreisatz')
            ->assertSeeText('Verhältnisse und proportionale Zusammenhänge lösen.')
            ->assertSeeText('Multiplikation')
            ->assertSeeText('Zahlen sicher multiplizieren und typische IT-Rechenwege üben.')
            ->assertSeeText('Division')
            ->assertSeeText('Teilungen, Anteile und einfache Verteilungen berechnen.')
            ->assertSeeText('Speichergrößen')
            ->assertSeeText('Byte, KB, MB, GB und TB sicher umrechnen.')
            ->assertSeeText('Stromverbrauch')
            ->assertSeeText('Leistung, Laufzeit, Energieverbrauch und Kosten berechnen.')
            ->assertSeeText('Hardwarekosten')
            ->assertSeeText('Komponentenpreise, Gesamtkosten und Budgets berechnen.');
    }

    public function test_clicking_calculation_topic_generates_exercise_and_checks_current_solution(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('calculation-exercises.generate', 'prozentrechnung'));

        $exercise = Exercise::firstOrFail();

        $response
            ->assertOk()
            ->assertSeeText('Beispielaufgabe')
            ->assertSeeText('Prozentrechnung: Mehrwertsteuer berechnen')
            ->assertSeeText('Ein Server kostet netto 960 Euro. Darauf werden 19 % Mehrwertsteuer berechnet.')
            ->assertSeeText('Einheit: Euro')
            ->assertSessionHas('calculation_exercise_id', $exercise->id);

        $this->assertSame($user->id, $exercise->user_id);
        $this->assertSame('Prozentrechnung: Mehrwertsteuer berechnen', $exercise->title);
        $this->assertSame('medium', $exercise->difficulty);
        $this->assertSame('fixture', $exercise->source);
        $this->assertSame('182.40', $exercise->solution);
        $this->assertSame('Euro', $exercise->expected_unit);
        $this->assertStringContainsString('960 * 19 / 100 = 182.40 Euro.', $exercise->sample_solution);
        $this->assertDatabaseHas('categories', ['name' => 'Calculation']);
        $this->assertStringContainsString('"topic_slug":"prozentrechnung"', $exercise->prompt);
        $this->assertStringContainsString('"topic":"Prozentrechnung"', $exercise->prompt);

        $this->actingAs($user)
            ->post(route('calculation-exercises.check'), ['user_solution' => '182.40'])
            ->assertOk()
            ->assertSeeText('Beispielaufgabe')
            ->assertSeeText('Deine Lösung ist korrekt.')
            ->assertSeeText('Erwartetes Ergebnis')
            ->assertSeeText('182.40 Euro')
            ->assertSeeText('Musterlösung');
    }

    public function test_ai_generated_calculation_exercise_shows_source_badge(): void
    {
        $user = User::factory()->create();

        $ai = Mockery::mock(AiResponseProvider::class);
        $ai->shouldReceive('getCalculation')
            ->once()
            ->andReturn([
                'source' => 'generated',
                'title' => 'Dreisatz: Lizenzen berechnen',
                'task' => '4 Lizenzen kosten 120 Euro. Wie viel Euro kosten 9 gleich teure Lizenzen?',
                'expected_result' => '270',
                'expected_unit' => 'Euro',
                'sample_solution' => '1. 120 / 4 = 30 Euro.\n2. 9 * 30 = 270 Euro.',
            ]);
        $this->app->instance(AiResponseProvider::class, $ai);

        $response = $this->actingAs($user)
            ->post(route('calculation-exercises.generate', 'dreisatz'));

        $exercise = Exercise::firstOrFail();

        $response
            ->assertOk()
            ->assertSeeText('KI-generiert');

        $this->assertSame('generated', $exercise->source);
    }

    public function test_invalid_calculation_topics_are_rejected_cleanly(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('calculation-exercises.generate', 'unbekanntes-thema'))
            ->assertNotFound();
    }

    public function test_uml_exercise_renders_simplified_input(): void
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
            ->withArgs(fn (string $uml) => str_contains($uml, 'class Person {')
                && str_contains($uml, 'Person -> Hund : besitzt'))
            ->andReturn($pngPath);
        $this->app->instance(PlantUmlService::class, $plantUml);

        $this->actingAs($user)
            ->post(route('uml.render'), [
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

    public function test_raw_plantuml_directives_are_disabled_by_default(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('uml.render'), [
                'uml_text' => "@startuml\nclass Person\n@enduml",
            ])
            ->assertOk()
            ->assertSeeText('Direkte PlantUML-Direktiven sind deaktiviert.');
    }

    private function assertSqlGenerationForDifficulty(string $difficulty, string $label): void
    {
        Category::create(['name' => 'SQL']);
        $user = User::factory()->create();
        $dbName = 'sql_exercise_test_'.$difficulty;

        $dbManager = Mockery::mock(DatabaseManager::class);
        $dbManager->shouldReceive('createTemporaryDatabase')->once()->andReturn($dbName);
        $dbManager->shouldReceive('createMySqlExercise')
            ->once()
            ->withArgs(fn (string $sql, string $database) => $database === $dbName
                && str_contains($sql, 'CREATE TABLE'));
        $dbManager->shouldReceive('getTables')
            ->once()
            ->with($dbName)
            ->andReturn([
                'fixture_table' => [
                    ['id' => 1],
                ],
            ]);
        $this->app->instance(DatabaseManager::class, $dbManager);

        $response = $this->actingAs($user)
            ->post(route('sql-uebung.generate', $difficulty));

        $exercise = Exercise::firstOrFail();

        $response
            ->assertOk()
            ->assertSeeText('Erstelle eine SQL-Abfrage')
            ->assertSeeText('Beispielaufgabe')
            ->assertSeeText('Schwierigkeit: '.$label)
            ->assertSessionHas('sql_temp_db', $dbName)
            ->assertSessionHas('sql_exercise_id', $exercise->id);

        $this->assertSame($user->id, $exercise->user_id);
        $this->assertSame($difficulty, $exercise->difficulty);
        $this->assertSame('fixture', $exercise->source);
        $this->assertStringContainsString('"difficulty":"'.$difficulty.'"', $exercise->prompt);
    }
}
