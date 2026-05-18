<?php

return [
    'sql' => [
        'database_prefix' => env('SQL_EXERCISE_DATABASE_PREFIX', 'sql_exercise_'),
        'max_age_seconds' => (int) env('SQL_EXERCISE_MAX_AGE_SECONDS', 2000),
        'charset' => env('SQL_EXERCISE_CHARSET', 'utf8mb4'),
        'collation' => env('SQL_EXERCISE_COLLATION', 'utf8mb4_unicode_ci'),

        'admin' => [
            'host' => env('SQL_EXERCISE_ADMIN_HOST', env('DB_HOST', '127.0.0.1')),
            'port' => env('SQL_EXERCISE_ADMIN_PORT', env('DB_PORT', '3306')),
            'username' => env('SQL_EXERCISE_ADMIN_USERNAME', env('DB_USERNAME', 'root')),
            'password' => env('SQL_EXERCISE_ADMIN_PASSWORD', env('DB_PASSWORD', '')),
        ],

        'runtime' => [
            'host' => env('SQL_EXERCISE_RUNTIME_HOST', env('DB_HOST', '127.0.0.1')),
            'port' => env('SQL_EXERCISE_RUNTIME_PORT', env('DB_PORT', '3306')),
            'username' => env('SQL_EXERCISE_RUNTIME_USERNAME', env('DB_USERNAME', 'root')),
            'password' => env('SQL_EXERCISE_RUNTIME_PASSWORD', env('DB_PASSWORD', '')),
            'grant_host' => env('SQL_EXERCISE_RUNTIME_GRANT_HOST', 'localhost'),
        ],
    ],
];
