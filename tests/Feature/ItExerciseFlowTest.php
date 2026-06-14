<?php

namespace Tests\Feature;

use App\Contracts\ExerciseProvider;
use App\Exceptions\ExerciseSourceException;
use App\Models\Category;
use App\Models\Exercise;
use App\Models\User;
use App\Services\DatabaseManager;
use App\Services\Exercises\ExerciseFixtureImporter;
use App\Services\PlantUmlService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Mockery;
use PDO;
use Tests\TestCase;

class ItExerciseFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(ExerciseFixtureImporter::class)->import();
    }

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

    public function test_easy_sql_exercise_loads_from_catalog(): void
    {
        $this->assertSqlGenerationForDifficulty('easy', 'Einfach');
    }

    public function test_medium_sql_exercise_loads_from_catalog(): void
    {
        $this->assertSqlGenerationForDifficulty('medium', 'Mittel');
    }

    public function test_hard_sql_exercise_loads_from_catalog(): void
    {
        $this->assertSqlGenerationForDifficulty('hard', 'Schwer');
    }

    public function test_next_sql_exercise_stays_in_difficulty_and_rotates_to_the_first(): void
    {
        $user = User::factory()->create();
        $exercises = Exercise::query()
            ->with('sqlDetail')
            ->where('type', 'sql')
            ->where('difficulty', 'easy')
            ->orderBy('external_id')
            ->get();
        $first = $exercises->firstOrFail();
        $second = $exercises->get(1);
        $last = $exercises->last();

        $this->assertNotNull($second);

        $dbManager = Mockery::mock(DatabaseManager::class);
        $dbManager->shouldReceive('createTemporaryDatabase')
            ->twice()
            ->andReturn('sql_exercise_next_second', 'sql_exercise_next_first');
        $dbManager->shouldReceive('createMySqlExercise')
            ->once()
            ->with($second->sqlDetail->setup_sql, 'sql_exercise_next_second');
        $dbManager->shouldReceive('createMySqlExercise')
            ->once()
            ->with($first->sqlDetail->setup_sql, 'sql_exercise_next_first');
        $dbManager->shouldReceive('getTables')
            ->once()
            ->with('sql_exercise_next_second')
            ->andReturn([]);
        $dbManager->shouldReceive('getTables')
            ->once()
            ->with('sql_exercise_next_first')
            ->andReturn([]);
        $dbManager->shouldReceive('dropTemporaryDatabase')
            ->once()
            ->with('sql_exercise_next_second');
        $dbManager->shouldReceive('dropTemporaryDatabase')
            ->once()
            ->with('sql_exercise_next_first');
        $this->app->instance(DatabaseManager::class, $dbManager);

        $this->actingAs($user)
            ->post(route('sql-uebung.next', $first))
            ->assertOk()
            ->assertViewHas('exerciseId', $second->id)
            ->assertSeeText($second->task)
            ->assertSeeText('Schwierigkeit: Einfach')
            ->assertSee(route('sql-uebung.next', $second), false);

        $this->actingAs($user)
            ->post(route('sql-uebung.next', $last))
            ->assertOk()
            ->assertViewHas('exerciseId', $first->id)
            ->assertSeeText($first->task)
            ->assertSeeText('Schwierigkeit: Einfach')
            ->assertSeeText('Hinweise')
            ->assertSeeText('Tipp 1 anzeigen')
            ->assertSeeText('Benötigte Spalten')
            ->assertSee('data-hint-mode="practice"', false)
            ->assertSee(route('sql-uebung.next', $first), false);
    }

    public function test_sql_exercise_execution_uses_current_catalog_exercise(): void
    {
        $user = User::factory()->create();
        $exercise = $this->createSqlCatalogExercise();
        $pdo = new PDO('sqlite::memory:');
        $pdo->exec('CREATE TABLE products (name TEXT, price REAL)');
        $pdo->exec("INSERT INTO products VALUES ('Maus', 24.50)");

        $provider = Mockery::mock(ExerciseProvider::class);
        $provider->shouldReceive('random')
            ->once()
            ->with('sql', 'easy')
            ->andReturn($this->sqlFixture($exercise->id));
        $this->app->instance(ExerciseProvider::class, $provider);

        $dbManager = Mockery::mock(DatabaseManager::class);
        $dbManager->shouldReceive('createTemporaryDatabase')
            ->twice()
            ->andReturn('sql_exercise_preview_test', 'sql_exercise_execution_test');
        $dbManager->shouldReceive('createMySqlExercise')
            ->once()
            ->with($exercise->sqlDetail->setup_sql, 'sql_exercise_preview_test');
        $dbManager->shouldReceive('createMySqlExercise')
            ->once()
            ->with($exercise->sqlDetail->setup_sql, 'sql_exercise_execution_test');
        $dbManager->shouldReceive('getTables')
            ->once()
            ->with('sql_exercise_preview_test')
            ->andReturn([
                'products' => [
                    ['name' => 'Maus', 'price' => 24.50],
                ],
            ]);
        $dbManager->shouldReceive('getTables')
            ->once()
            ->with('sql_exercise_execution_test')
            ->andReturn([
                'products' => [
                    ['name' => 'Maus', 'price' => 24.50],
                ],
            ]);
        $dbManager->shouldReceive('connectToDatabase')
            ->once()
            ->with('sql_exercise_execution_test')
            ->andReturn($pdo);
        $dbManager->shouldReceive('dropTemporaryDatabase')
            ->once()
            ->with('sql_exercise_preview_test');
        $dbManager->shouldReceive('dropTemporaryDatabase')
            ->once()
            ->with('sql_exercise_execution_test');
        $this->app->instance(DatabaseManager::class, $dbManager);

        $exerciseCount = Exercise::count();
        $response = $this->actingAs($user)->post(route('sql-uebung.generate', 'easy'));

        $response
            ->assertOk()
            ->assertSeeText('Gib alle Produkte aus.')
            ->assertSeeText('Beispielaufgabe')
            ->assertSeeText('Schwierigkeit: Einfach')
            ->assertSee(route('sql-uebung.execute', $exercise), false)
            ->assertSessionMissing('sql_temp_db')
            ->assertSessionMissing('sql_exercise_id');

        $this->assertSame($exerciseCount, Exercise::count());

        $this->actingAs($user)
            ->post(route('sql-uebung.execute', $exercise), [
                'sql_input' => 'SELECT name, price FROM products',
            ])
            ->assertOk()
            ->assertSeeText('Deine Ausgabe')
            ->assertSeeText('Erwartete Ausgabe')
            ->assertSeeText('Maus')
            ->assertSeeText('WHERE filtert die Produkte.');
    }

    public function test_invalid_sql_is_rendered_as_a_safe_structured_error_below_the_input(): void
    {
        $user = User::factory()->create();
        $exercise = $this->createSqlCatalogExercise('sql-error-test');
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec('CREATE TABLE products (name TEXT, price REAL)');

        $dbManager = Mockery::mock(DatabaseManager::class);
        $dbManager->shouldReceive('createTemporaryDatabase')
            ->once()
            ->andReturn('sql_exercise_error_test');
        $dbManager->shouldReceive('createMySqlExercise')
            ->once()
            ->with($exercise->sqlDetail->setup_sql, 'sql_exercise_error_test');
        $dbManager->shouldReceive('connectToDatabase')
            ->once()
            ->with('sql_exercise_error_test')
            ->andReturn($pdo);
        $dbManager->shouldReceive('getTables')
            ->once()
            ->with('sql_exercise_error_test')
            ->andReturn(['products' => []]);
        $dbManager->shouldReceive('dropTemporaryDatabase')
            ->once()
            ->with('sql_exercise_error_test');
        $this->app->instance(DatabaseManager::class, $dbManager);

        $response = $this->actingAs($user)
            ->post(route('sql-uebung.execute', $exercise), [
                'sql_input' => 'SELECT missing_column FROM products',
            ]);

        $response
            ->assertOk()
            ->assertSeeInOrder([
                'SELECT missing_column FROM products',
                'SQL-Fehler',
                'Deine SQL-Abfrage konnte nicht ausgeführt werden.',
                'Originale Datenbankmeldung:',
                'no such column: missing_column',
                'Hinweis:',
            ])
            ->assertDontSee('Stack trace')
            ->assertDontSee('Erwartete Ausgabe');
    }

    public function test_sql_execution_uses_the_submitted_exercise_id_across_multiple_tabs(): void
    {
        $user = User::factory()->create();
        $firstExercise = $this->createSqlCatalogExercise('sql-tab-one');
        $firstExercise->update(['task' => 'Zeige die Produkte aus Tab eins.']);
        $firstExercise->sqlDetail()->update([
            'setup_sql' => 'CREATE TABLE first_products (name VARCHAR(80));',
            'solution_sql' => 'SELECT name FROM first_products;',
        ]);

        $secondExercise = $this->createSqlCatalogExercise('sql-tab-two');
        $secondExercise->update(['task' => 'Zeige die Produkte aus Tab zwei.']);
        $secondExercise->sqlDetail()->update([
            'setup_sql' => 'CREATE TABLE second_products (name VARCHAR(80));',
            'solution_sql' => 'SELECT name FROM second_products;',
        ]);

        $firstPdo = new PDO('sqlite::memory:');
        $firstPdo->exec('CREATE TABLE first_products (name TEXT)');
        $firstPdo->exec("INSERT INTO first_products VALUES ('Erstes Produkt')");
        $secondPdo = new PDO('sqlite::memory:');
        $secondPdo->exec('CREATE TABLE second_products (name TEXT)');
        $secondPdo->exec("INSERT INTO second_products VALUES ('Zweites Produkt')");

        $dbManager = Mockery::mock(DatabaseManager::class);
        $dbManager->shouldReceive('createTemporaryDatabase')
            ->twice()
            ->andReturn('sql_exercise_tab_one', 'sql_exercise_tab_two');
        $dbManager->shouldReceive('createMySqlExercise')
            ->once()
            ->with('CREATE TABLE first_products (name VARCHAR(80));', 'sql_exercise_tab_one');
        $dbManager->shouldReceive('createMySqlExercise')
            ->once()
            ->with('CREATE TABLE second_products (name VARCHAR(80));', 'sql_exercise_tab_two');
        $dbManager->shouldReceive('connectToDatabase')
            ->once()
            ->with('sql_exercise_tab_one')
            ->andReturn($firstPdo);
        $dbManager->shouldReceive('connectToDatabase')
            ->once()
            ->with('sql_exercise_tab_two')
            ->andReturn($secondPdo);
        $dbManager->shouldReceive('getTables')
            ->once()
            ->with('sql_exercise_tab_one')
            ->andReturn(['first_products' => [['name' => 'Erstes Produkt']]]);
        $dbManager->shouldReceive('getTables')
            ->once()
            ->with('sql_exercise_tab_two')
            ->andReturn(['second_products' => [['name' => 'Zweites Produkt']]]);
        $dbManager->shouldReceive('dropTemporaryDatabase')
            ->once()
            ->with('sql_exercise_tab_one');
        $dbManager->shouldReceive('dropTemporaryDatabase')
            ->once()
            ->with('sql_exercise_tab_two');
        $this->app->instance(DatabaseManager::class, $dbManager);

        $this->actingAs($user)
            ->withSession([
                'sql_temp_db' => 'stale_session_database',
                'sql_exercise_id' => $secondExercise->id,
            ])
            ->post(route('sql-uebung.execute', $firstExercise), [
                'sql_input' => 'SELECT name FROM first_products',
            ])
            ->assertOk()
            ->assertSeeText('Zeige die Produkte aus Tab eins.')
            ->assertSeeText('Erstes Produkt')
            ->assertDontSeeText('Zeige die Produkte aus Tab zwei.');

        $this->actingAs($user)
            ->withSession([
                'sql_temp_db' => 'another_stale_database',
                'sql_exercise_id' => $firstExercise->id,
            ])
            ->post(route('sql-uebung.execute', $secondExercise), [
                'sql_input' => 'SELECT name FROM second_products',
            ])
            ->assertOk()
            ->assertSeeText('Zeige die Produkte aus Tab zwei.')
            ->assertSeeText('Zweites Produkt')
            ->assertDontSeeText('Zeige die Produkte aus Tab eins.');
    }

    public function test_sql_source_errors_are_shown_on_the_selection_page(): void
    {
        $user = User::factory()->create();
        $provider = Mockery::mock(ExerciseProvider::class);
        $provider->shouldReceive('random')
            ->once()
            ->andThrow(new ExerciseSourceException('Keine SQL-Aufgabe im Katalog gefunden.'));
        $this->app->instance(ExerciseProvider::class, $provider);

        $this->actingAs($user)
            ->post(route('sql-uebung.generate', 'easy'))
            ->assertRedirect(route('sql-uebung'))
            ->assertSessionHasErrors('exercise_source');

        $this->actingAs($user)
            ->get(route('sql-uebung'))
            ->assertOk()
            ->assertSeeText('Keine SQL-Aufgabe im Katalog gefunden.');
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

    public function test_calculation_exercise_loads_from_catalog_and_checks_solution(): void
    {
        $user = User::factory()->create();
        $exercise = $this->createCalculationCatalogExercise();
        $provider = Mockery::mock(ExerciseProvider::class);
        $provider->shouldReceive('random')
            ->once()
            ->with('calculation', 'hard', ['topic' => 'hardwarekosten'])
            ->andReturn($this->calculationFixture($exercise->id));
        $this->app->instance(ExerciseProvider::class, $provider);

        $exerciseCount = Exercise::count();
        $response = $this->actingAs($user)
            ->post(route('calculation-exercises.generate', 'hardwarekosten'), [
                'difficulty' => 'hard',
            ]);

        $response
            ->assertOk()
            ->assertSeeText('Beispielaufgabe')
            ->assertSeeText('Schwierigkeit: Schwer')
            ->assertSeeText('Server-Rack kalkulieren')
            ->assertSeeText('Einheit: Euro')
            ->assertSeeText('Hinweise')
            ->assertSeeText('Tipp 1 anzeigen')
            ->assertSeeText('Rechenweg beginnen')
            ->assertSessionHas('calculation_exercise_id', $exercise->id);

        $this->assertSame($exerciseCount, Exercise::count());
        $this->assertSame('2400.000000', $exercise->calculationDetail->expected_value);

        $this->actingAs($user)
            ->post(route('calculation-exercises.check'), ['user_solution' => '2400'])
            ->assertOk()
            ->assertSeeText('Deine Lösung ist korrekt.')
            ->assertSee('value="2400"', false)
            ->assertSeeText('2400 Euro')
            ->assertSeeText('Rechenweg beginnen')
            ->assertSeeText('Die Einzelkosten werden addiert.');
    }

    public function test_uml_page_loads_a_catalog_exercise_for_the_selected_difficulty(): void
    {
        $user = User::factory()->create();
        $exerciseCount = Exercise::count();

        $response = $this->actingAs($user)
            ->get(route('uml.form', ['difficulty' => 'easy']))
            ->assertOk()
            ->assertSeeText('Beispielaufgabe')
            ->assertSeeText('Schwierigkeit: Einfach')
            ->assertSeeText('PlantUML-Eingabe')
            ->assertSeeText('Musterlösung anzeigen')
            ->assertSessionMissing('uml_exercise_id');

        $this->assertSame($exerciseCount, Exercise::count());
        $response->assertSee(
            'name="exercise_id" value="'.$response->viewData('exercise')['database_id'].'"',
            false,
        );
    }

    public function test_uml_page_filters_catalog_exercises_by_diagram_type(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('uml.form', ['diagram_type' => 'sequence']))
            ->assertOk()
            ->assertSeeText('Sequenzdiagramm')
            ->assertSeeText('Anmeldung prüfen')
            ->assertSeeText('Klassendiagramm')
            ->assertSeeText('ER-/Datenmodell')
            ->assertSeeText('Use-Case-Diagramm')
            ->assertSeeText('Aktivitätsdiagramm');
    }

    public function test_uml_exercise_wraps_diagram_neutral_input_with_plantuml_markers(): void
    {
        $user = User::factory()->create();
        $exercise = Exercise::query()
            ->where('external_id', 'uml-sequence-medium-001')
            ->firstOrFail();
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
            ->with("@startuml\nactor Benutzer\nBenutzer -> Anwendung : anmelden\n@enduml")
            ->andReturn($pngPath);
        $this->app->instance(PlantUmlService::class, $plantUml);

        $this->actingAs($user)
            ->post(route('uml.render'), [
                'exercise_id' => $exercise->id,
                'uml_text' => "actor Benutzer\nBenutzer -> Anwendung : anmelden",
            ])
            ->assertOk()
            ->assertSeeText('Anmeldung prüfen')
            ->assertSeeText('actor Benutzer')
            ->assertSee('data:image/png;base64,', false);
    }

    public function test_uml_rendering_errors_do_not_expose_process_details(): void
    {
        $user = User::factory()->create();
        $exercise = Exercise::query()
            ->where('external_id', 'uml-activity-hard-001')
            ->firstOrFail();
        $plantUml = Mockery::mock(PlantUmlService::class);
        $plantUml->shouldReceive('generate')
            ->once()
            ->andThrow(new \RuntimeException(
                'Java failed at C:\\tools\\plantuml.jar with password=secret',
            ));
        $this->app->instance(PlantUmlService::class, $plantUml);

        $this->actingAs($user)
            ->post(route('uml.render'), [
                'exercise_id' => $exercise->id,
                'uml_text' => "start\n:Ticket prüfen;\nstop",
            ])
            ->assertOk()
            ->assertSeeText('Das UML-Diagramm konnte nicht gerendert werden.')
            ->assertSeeText('Support-Ticket bearbeiten')
            ->assertSeeText('Ticket prüfen')
            ->assertSeeText('Hinweise')
            ->assertSeeText('Tipp 1 anzeigen')
            ->assertSeeText('Ablauf identifizieren')
            ->assertDontSee('plantuml.jar')
            ->assertDontSee('password=secret');
    }

    public function test_normal_exercise_loading_sends_no_http_requests(): void
    {
        Http::fake();
        $user = User::factory()->create();
        $dbManager = Mockery::mock(DatabaseManager::class);
        $dbManager->shouldReceive('createTemporaryDatabase')->once()->andReturn('sql_exercise_no_http');
        $dbManager->shouldReceive('createMySqlExercise')->once();
        $dbManager->shouldReceive('getTables')->once()->andReturn([]);
        $dbManager->shouldReceive('dropTemporaryDatabase')
            ->once()
            ->with('sql_exercise_no_http');
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
        $this->actingAs($user)
            ->get(route('uml.form', ['diagram_type' => 'component']))
            ->assertNotFound();
    }

    private function assertSqlGenerationForDifficulty(string $difficulty, string $label): void
    {
        $user = User::factory()->create();
        $exerciseCount = Exercise::count();
        $dbName = 'sql_exercise_test_'.$difficulty;
        $dbManager = Mockery::mock(DatabaseManager::class);
        $dbManager->shouldReceive('createTemporaryDatabase')->once()->andReturn($dbName);
        $dbManager->shouldReceive('createMySqlExercise')
            ->once()
            ->withArgs(fn (string $sql, string $database): bool => $database === $dbName
                && str_contains($sql, 'CREATE TABLE'));
        $dbManager->shouldReceive('getTables')->once()->with($dbName)->andReturn([]);
        $dbManager->shouldReceive('dropTemporaryDatabase')->once()->with($dbName);
        $this->app->instance(DatabaseManager::class, $dbManager);

        $response = $this->actingAs($user)
            ->post(route('sql-uebung.generate', $difficulty));

        $response
            ->assertOk()
            ->assertSeeText('Beispielaufgabe')
            ->assertSeeText('Schwierigkeit: '.$label)
            ->assertSeeText('Nächste Aufgabe')
            ->assertSessionMissing('sql_temp_db')
            ->assertSessionMissing('sql_exercise_id');

        $this->assertSame($exerciseCount, Exercise::count());
        $exerciseId = $response->viewData('exerciseId');
        $response->assertSee(route('sql-uebung.execute', $exerciseId), false);
        $response->assertSee(route('sql-uebung.next', $exerciseId), false);
        $this->assertDatabaseHas('exercises', [
            'id' => $exerciseId,
            'type' => 'sql',
            'difficulty' => $difficulty,
            'source' => 'json',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function sqlFixture(int $databaseId): array
    {
        return [
            'database_id' => $databaseId,
            'id' => 'sql-easy-test',
            'type' => 'sql',
            'difficulty' => 'easy',
            'topic' => 'select',
            'title' => 'Produkte filtern',
            'task' => 'Gib alle Produkte aus.',
            'setup_sql' => 'CREATE TABLE products (name VARCHAR(80), price DECIMAL(10,2));',
            'solution' => 'SELECT name, price FROM products;',
            'explanation' => 'WHERE filtert die Produkte.',
            'source' => 'json',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function calculationFixture(int $databaseId): array
    {
        return [
            'database_id' => $databaseId,
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
            'hints' => [
                [
                    'level' => 1,
                    'title' => 'Rechenweg beginnen',
                    'text' => 'Addiere die Einzelkosten.',
                ],
            ],
            'source' => 'json',
        ];
    }

    private function createSqlCatalogExercise(string $externalId = 'sql-easy-test'): Exercise
    {
        $category = Category::query()->where('slug', 'sql')->firstOrFail();
        $exercise = Exercise::updateOrCreate(
            ['external_id' => $externalId],
            [
                'category_id' => $category->id,
                'type' => 'sql',
                'topic' => 'select',
                'difficulty' => 'easy',
                'title' => 'Produkte filtern',
                'task' => 'Gib alle Produkte aus.',
                'explanation' => 'WHERE filtert die Produkte.',
                'source' => 'json',
                'status' => 'published',
            ],
        );
        $exercise->sqlDetail()->updateOrCreate([], [
            'setup_sql' => 'CREATE TABLE products (name VARCHAR(80), price DECIMAL(10,2));',
            'solution_sql' => 'SELECT name, price FROM products;',
        ]);

        return $exercise->load('sqlDetail');
    }

    private function createCalculationCatalogExercise(): Exercise
    {
        $category = Category::query()->where('slug', 'calculation')->firstOrFail();
        $exercise = Exercise::updateOrCreate(
            ['external_id' => 'calc-hard-test'],
            [
                'category_id' => $category->id,
                'type' => 'calculation',
                'topic' => 'hardwarekosten',
                'difficulty' => 'hard',
                'title' => 'Server-Rack kalkulieren',
                'task' => 'Berechne die Gesamtkosten eines Server-Racks.',
                'explanation' => 'Die Einzelkosten werden addiert.',
                'hints' => [
                    [
                        'level' => 1,
                        'title' => 'Rechenweg beginnen',
                        'text' => 'Addiere die Einzelkosten.',
                    ],
                ],
                'source' => 'json',
                'status' => 'published',
            ],
        );
        $exercise->calculationDetail()->updateOrCreate([], [
            'expected_value' => '2400',
            'tolerance' => '0',
            'unit' => 'Euro',
            'solution_steps' => '1200 + 800 + 400 = 2400 Euro.',
        ]);

        return $exercise->load('calculationDetail');
    }
}
