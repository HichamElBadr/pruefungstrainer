<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Console\Scheduling\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

 function schedule(Schedule $schedule)
{
    $schedule->call(function () {
        \App\Models\Exercise::where('created_at', '<', now()->subDay())->delete();
    })->daily();
}