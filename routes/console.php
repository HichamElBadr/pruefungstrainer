<?php

use App\Services\DatabaseManager;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('sql-exercises:cleanup', function () {
    $result = app(DatabaseManager::class)->cleanOldDatabases();
    $this->info(
        "Dropped {$result['databases']} stale SQL exercise databases "
        ."and revoked {$result['grants']} orphaned runtime grants.",
    );
})->purpose('Drop stale SQL exercise databases and revoke orphaned grants');

Schedule::command('sql-exercises:cleanup')->hourly();
