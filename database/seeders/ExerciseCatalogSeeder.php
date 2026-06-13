<?php

namespace Database\Seeders;

use App\Services\Exercises\ExerciseFixtureImporter;
use Illuminate\Database\Seeder;

class ExerciseCatalogSeeder extends Seeder
{
    public function run(): void
    {
        app(ExerciseFixtureImporter::class)->import();
    }
}
