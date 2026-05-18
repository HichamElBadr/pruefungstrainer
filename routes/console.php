<?php

use App\Services\DatabaseManager;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('sql-exercises:cleanup', function () {
    $count = app(DatabaseManager::class)->cleanOldDatabases();
    $this->info("Dropped {$count} stale SQL exercise databases.");
})->purpose('Drop stale temporary SQL exercise databases');

Schedule::command('sql-exercises:cleanup')->hourly();
Schedule::call(function () {
    \App\Models\Exercise::where('created_at', '<', now()->subDay())->delete();
})->daily();
