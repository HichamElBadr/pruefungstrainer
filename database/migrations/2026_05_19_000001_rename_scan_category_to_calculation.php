<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('categories')
            ->where('name', 'Scan')
            ->update(['name' => 'Calculation']);
    }

    public function down(): void
    {
        DB::table('categories')
            ->where('name', 'Calculation')
            ->update(['name' => 'Scan']);
    }
};
