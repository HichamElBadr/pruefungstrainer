<?php

namespace Tests\Feature;

use App\Contracts\ExerciseProvider;
use App\Models\Category;
use App\Models\Exercise;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ImportExercisesCommandTest extends TestCase
{
    use RefreshDatabase;

    private ?string $temporaryRoot = null;

    protected function tearDown(): void
    {
        if ($this->temporaryRoot !== null) {
            File::deleteDirectory($this->temporaryRoot);
        }

        parent::tearDown();
    }

    public function test_migration_preserves_legacy_tables_and_creates_catalog_schema(): void
    {
        $this->assertTrue(Schema::hasTable('legacy_exercises'));
        $this->assertTrue(Schema::hasTable('legacy_categories'));
        $this->assertTrue(Schema::hasTable('exercises'));
        $this->assertTrue(Schema::hasTable('categories'));
        $this->assertTrue(Schema::hasTable('sql_exercise_details'));
        $this->assertTrue(Schema::hasTable('calculation_exercise_details'));
        $this->assertTrue(Schema::hasTable('uml_exercise_details'));
    }

    public function test_catalog_migration_can_resume_after_its_tables_already_exist(): void
    {
        $migration = require database_path(
            'migrations/2026_06_13_000000_create_reusable_exercise_catalog.php',
        );

        $migration->up();

        $this->assertTrue(Schema::hasColumn('categories', 'slug'));
        $this->assertTrue(Schema::hasColumn('exercises', 'external_id'));
        $this->assertTrue(Schema::hasTable('sql_exercise_details'));
        $this->assertTrue(Schema::hasTable('calculation_exercise_details'));
        $this->assertTrue(Schema::hasTable('uml_exercise_details'));
    }

    public function test_command_imports_all_fixtures_into_type_specific_tables(): void
    {
        $this->artisan('exercises:import')
            ->expectsOutput('Imported 39 exercises (39 created, 0 updated).')
            ->assertExitCode(0);

        $this->assertDatabaseCount('exercises', 39);
        $this->assertDatabaseCount('sql_exercise_details', 9);
        $this->assertDatabaseCount('calculation_exercise_details', 21);
        $this->assertDatabaseCount('uml_exercise_details', 9);
        $this->assertDatabaseHas('categories', [
            'slug' => 'sql',
            'name' => 'SQL',
            'is_active' => true,
        ]);

        $exercise = Exercise::query()
            ->with('sqlDetail')
            ->where('external_id', 'sql-easy-001')
            ->firstOrFail();

        $this->assertSame('sql', $exercise->type);
        $this->assertNotNull($exercise->sqlDetail);
        $this->assertStringContainsString('CREATE TABLE', $exercise->sqlDetail->setup_sql);
    }

    public function test_repeated_import_updates_existing_records_without_duplicates(): void
    {
        $this->artisan('exercises:import')->assertExitCode(0);
        $this->artisan('exercises:import')
            ->expectsOutput('Imported 39 exercises (0 created, 39 updated).')
            ->assertExitCode(0);

        $this->assertDatabaseCount('exercises', 39);
        $this->assertSame(
            39,
            Exercise::query()->distinct()->count('external_id'),
        );
    }

    public function test_import_updates_changed_fixture_content_by_external_id(): void
    {
        $this->artisan('exercises:import')->assertExitCode(0);
        $this->temporaryRoot = storage_path('framework/testing/import-exercises-'.uniqid());
        File::copyDirectory(resource_path('exercises'), $this->temporaryRoot);
        $path = $this->temporaryRoot.'/sql/easy/exercises.json';
        $fixtures = json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        $fixtures[0]['title'] = 'Aktualisierter SQL-Titel';
        file_put_contents($path, json_encode($fixtures, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        config(['exercises.path' => $this->temporaryRoot]);

        $this->artisan('exercises:import')->assertExitCode(0);

        $this->assertDatabaseCount('exercises', 39);
        $this->assertDatabaseHas('exercises', [
            'external_id' => 'sql-easy-001',
            'title' => 'Aktualisierter SQL-Titel',
        ]);
    }

    public function test_database_provider_returns_imported_detail_fields(): void
    {
        $this->artisan('exercises:import')->assertExitCode(0);

        $sql = app(ExerciseProvider::class)->all('sql', 'easy');
        $calculation = app(ExerciseProvider::class)->all('calculation', 'hard', [
            'topic' => 'hardwarekosten',
        ]);
        $uml = app(ExerciseProvider::class)->all('uml', 'easy');

        $this->assertArrayHasKey('database_id', $sql[0]);
        $this->assertArrayHasKey('setup_sql', $sql[0]);
        $this->assertArrayHasKey('solution', $sql[0]);
        $this->assertSame('17139.6', $calculation[0]['expected_result']);
        $this->assertSame('class', $uml[0]['diagram_type']);
        $this->assertStringContainsString('@startuml', $uml[0]['solution_plantuml']);
    }

    public function test_category_boolean_cast_is_applied(): void
    {
        $this->artisan('exercises:import')->assertExitCode(0);

        $category = Category::query()->where('slug', 'sql')->firstOrFail();

        $this->assertTrue($category->is_active);
        $this->assertIsInt($category->sort_order);
    }

    public function test_database_seeder_populates_the_reusable_catalog(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('exercises', 39);
        $this->assertDatabaseCount('categories', 9);
        $this->assertDatabaseHas('users', ['email' => 'gh@gmail.com']);
    }
}
