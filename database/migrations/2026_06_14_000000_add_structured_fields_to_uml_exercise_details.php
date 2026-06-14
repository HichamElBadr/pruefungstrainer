<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('uml_exercise_details', function (Blueprint $table) {
            $table->longText('scenario')->nullable()->after('diagram_type');
            $table->json('requirements')->nullable()->after('scenario');
            $table->json('expected_elements')->nullable()->after('solution_plantuml');
        });
    }

    public function down(): void
    {
        Schema::table('uml_exercise_details', function (Blueprint $table) {
            $table->dropColumn([
                'scenario',
                'requirements',
                'expected_elements',
            ]);
        });
    }
};
