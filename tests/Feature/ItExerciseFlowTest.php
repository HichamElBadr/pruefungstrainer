<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Exercise;
use App\Models\User;
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
        $this->get(route('scan-uebung'))->assertRedirect(route('login'));
        $this->get(route('uml.form'))->assertRedirect(route('login'));
    }

    public function test_sql_exercise_uses_fixture_and_keeps_current_exercise_in_session(): void
    {
        Category::create(['name' => 'SQL']);
        $user = User::factory()->create();
        $pdo = new PDO('sqlite::memory:');
        $pdo->exec('CREATE TABLE answers (value INTEGER)');
        $pdo->exec('INSERT INTO answers VALUES (1)');

        $dbManager = Mockery::mock(DatabaseManager::class);
        $dbManager->shouldReceive('createTemporaryDatabase')->once()->andReturn('sql_exercise_test');
        $dbManager->shouldReceive('createMySqlExercise')->once();
        $dbManager->shouldReceive('getTables')->andReturn([
            'answers' => [
                ['value' => 1],
            ],
        ]);
        $dbManager->shouldReceive('connectToDatabase')->once()->with('sql_exercise_test')->andReturn($pdo);
        $this->app->instance(DatabaseManager::class, $dbManager);

        $response = $this->actingAs($user)->get(route('sql-uebung'));

        $exercise = Exercise::firstOrFail();

        $response
            ->assertOk()
            ->assertSeeText('Erstelle eine SQL-Abfrage')
            ->assertSessionHas('sql_temp_db', 'sql_exercise_test')
            ->assertSessionHas('sql_exercise_id', $exercise->id);

        $this->assertSame($user->id, $exercise->user_id);

        $this->actingAs($user)
            ->post(route('sql-uebung'), ['sql_input' => 'SELECT value FROM answers'])
            ->assertOk()
            ->assertSeeText('value')
            ->assertSeeText('1');
    }

    public function test_scan_exercise_uses_fixture_and_checks_current_solution(): void
    {
        Category::create(['name' => 'Scan']);
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('scan-uebung'));

        $exercise = Exercise::firstOrFail();

        $response
            ->assertOk()
            ->assertSeeText('Rechne 5+5')
            ->assertSessionHas('scan_exercise_id', $exercise->id);

        $this->assertSame($user->id, $exercise->user_id);

        $this->actingAs($user)
            ->post(route('scan-uebung'), ['user_solution' => '10'])
            ->assertOk()
            ->assertSeeText('Deine Lösung ist korrekt!');
    }

    public function test_uml_exercise_renders_simplified_input(): void
    {
        $user = User::factory()->create();
        $pngPath = storage_path('framework/testing/uml.png');

        if (!is_dir(dirname($pngPath))) {
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
}
