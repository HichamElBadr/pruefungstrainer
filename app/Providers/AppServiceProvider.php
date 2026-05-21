<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $maxExecutionTime = (int) config('app.max_execution_time', 0);

        if ($maxExecutionTime > 0) {
            ini_set('max_execution_time', (string) $maxExecutionTime);

            if (function_exists('set_time_limit')) {
                set_time_limit($maxExecutionTime);
            }
        }
    }
}
