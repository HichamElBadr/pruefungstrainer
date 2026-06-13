<?php

namespace App\Providers;

use App\Contracts\ExerciseProvider;
use App\Services\Exercises\DatabaseExerciseProvider;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ExerciseProvider::class, function ($app): ExerciseProvider {
            return match (config('exercises.source', 'database')) {
                'database' => $app->make(DatabaseExerciseProvider::class),
                default => throw new RuntimeException(
                    'Unsupported exercise source: '.config('exercises.source'),
                ),
            };
        });
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
