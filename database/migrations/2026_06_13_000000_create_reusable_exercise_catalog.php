<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->preserveLegacyTable('exercises', 'legacy_exercises', 'external_id');
        $this->preserveLegacyTable('categories', 'legacy_categories', 'slug');

        if (! Schema::hasTable('categories')) {
            Schema::create('categories', function (Blueprint $table) {
                $table->id();
                $table->string('slug')->unique();
                $table->string('name')->unique();
                $table->text('description')->nullable();
                $table->integer('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        $this->assertCatalogTable('categories', 'slug');

        if (! Schema::hasTable('exercises')) {
            Schema::create('exercises', function (Blueprint $table) {
                $table->id();
                $table->string('external_id')->unique();
                $table->foreignId('category_id')->nullable()->index();
                $table->string('type')->index();
                $table->string('topic')->nullable()->index();
                $table->string('difficulty')->nullable()->index();
                $table->string('title');
                $table->longText('task');
                $table->longText('explanation')->nullable();
                $table->string('source')->default('json');
                $table->string('status')->default('published')->index();
                $table->timestamps();

                $table->foreign('category_id', 'catalog_exercises_category_fk')
                    ->references('id')
                    ->on('categories')
                    ->nullOnDelete();
            });
        }

        $this->assertCatalogTable('exercises', 'external_id');

        if (! Schema::hasTable('sql_exercise_details')) {
            Schema::create('sql_exercise_details', function (Blueprint $table) {
                $table->foreignId('exercise_id')->primary();
                $table->longText('setup_sql');
                $table->longText('starter_sql')->nullable();
                $table->longText('solution_sql');
                $table->timestamps();

                $table->foreign('exercise_id', 'sql_exercise_details_exercise_fk')
                    ->references('id')
                    ->on('exercises')
                    ->cascadeOnDelete();
            });
        }

        $this->assertCatalogTable('sql_exercise_details', 'setup_sql');

        if (! Schema::hasTable('calculation_exercise_details')) {
            Schema::create('calculation_exercise_details', function (Blueprint $table) {
                $table->foreignId('exercise_id')->primary();
                $table->decimal('expected_value', 20, 6)->nullable();
                $table->decimal('tolerance', 20, 6)->nullable()->default(0);
                $table->string('unit')->nullable();
                $table->longText('solution_steps')->nullable();
                $table->timestamps();

                $table->foreign('exercise_id', 'calculation_exercise_details_exercise_fk')
                    ->references('id')
                    ->on('exercises')
                    ->cascadeOnDelete();
            });
        }

        $this->assertCatalogTable('calculation_exercise_details', 'expected_value');

        if (! Schema::hasTable('uml_exercise_details')) {
            Schema::create('uml_exercise_details', function (Blueprint $table) {
                $table->foreignId('exercise_id')->primary();
                $table->string('diagram_type')->nullable();
                $table->longText('starter_plantuml')->nullable();
                $table->longText('solution_plantuml');
                $table->timestamps();

                $table->foreign('exercise_id', 'uml_exercise_details_exercise_fk')
                    ->references('id')
                    ->on('exercises')
                    ->cascadeOnDelete();
            });
        }

        $this->assertCatalogTable('uml_exercise_details', 'solution_plantuml');
    }

    public function down(): void
    {
        Schema::dropIfExists('uml_exercise_details');
        Schema::dropIfExists('calculation_exercise_details');
        Schema::dropIfExists('sql_exercise_details');
        Schema::dropIfExists('exercises');
        Schema::dropIfExists('categories');

        if (Schema::hasTable('legacy_categories') && ! Schema::hasTable('categories')) {
            Schema::rename('legacy_categories', 'categories');
        }

        if (Schema::hasTable('legacy_exercises') && ! Schema::hasTable('exercises')) {
            Schema::rename('legacy_exercises', 'exercises');
        }
    }

    private function preserveLegacyTable(string $current, string $legacy, string $catalogMarker): void
    {
        if (Schema::hasTable($legacy)) {
            return;
        }

        if (! Schema::hasTable($current)) {
            throw new RuntimeException(
                "Cannot create the exercise catalog: neither {$current} nor {$legacy} exists.",
            );
        }

        if (Schema::hasColumn($current, $catalogMarker)) {
            throw new RuntimeException(
                "Cannot preserve legacy {$current}: the current table already looks like the new catalog.",
            );
        }

        Schema::rename($current, $legacy);
    }

    private function assertCatalogTable(string $table, string $requiredColumn): void
    {
        if (! Schema::hasColumn($table, $requiredColumn)) {
            throw new RuntimeException(
                "The partially created {$table} table is missing required column {$requiredColumn}.",
            );
        }
    }
};
