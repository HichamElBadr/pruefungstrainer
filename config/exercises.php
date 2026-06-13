<?php

return [
    'source' => env('EXERCISE_SOURCE', 'database'),
    'path' => resource_path('exercises'),
    'types' => [
        'sql',
        'uml',
        'calculation',
    ],
    'difficulties' => [
        'easy',
        'medium',
        'hard',
    ],
    'minimum_per_collection' => 3,
    'required_fields' => [
        'common' => [
            'id',
            'type',
            'difficulty',
            'topic',
            'title',
            'task',
            'explanation',
            'tags',
        ],
        'sql' => [
            'setup_sql',
            'solution',
        ],
        'uml' => [
            'solution_plantuml',
        ],
        'calculation' => [
            'expected_result',
            'unit',
            'solution_steps',
        ],
    ],

    'sql' => [
        'database_prefix' => env('SQL_EXERCISE_DATABASE_PREFIX', 'sql_exercise_'),
        'max_age_seconds' => (int) env('SQL_EXERCISE_MAX_AGE_SECONDS', 2000),
        'charset' => env('SQL_EXERCISE_CHARSET', 'utf8mb4'),
        'collation' => env('SQL_EXERCISE_COLLATION', 'utf8mb4_unicode_ci'),
        'query' => [
            'timeout_ms' => (int) env('SQL_EXERCISE_QUERY_TIMEOUT_MS', 3000),
            'row_limit' => (int) env('SQL_EXERCISE_RESULT_ROW_LIMIT', 200),
            'forbidden_functions' => [
                'benchmark',
                'get_lock',
                'is_free_lock',
                'is_used_lock',
                'load_file',
                'master_pos_wait',
                'release_lock',
                'sleep',
            ],
            'forbidden_schemas' => [
                'information_schema',
                'mysql',
                'performance_schema',
                'sys',
            ],
        ],

        'admin' => [
            'host' => env('SQL_EXERCISE_ADMIN_HOST', env('DB_HOST', '127.0.0.1')),
            'port' => env('SQL_EXERCISE_ADMIN_PORT', env('DB_PORT', '3306')),
            'username' => env('SQL_EXERCISE_ADMIN_USERNAME', env('DB_USERNAME', 'root')),
            'password' => env('SQL_EXERCISE_ADMIN_PASSWORD', env('DB_PASSWORD', '')),
        ],

        'runtime' => [
            'host' => env('SQL_EXERCISE_RUNTIME_HOST', env('DB_HOST', '127.0.0.1')),
            'port' => env('SQL_EXERCISE_RUNTIME_PORT', env('DB_PORT', '3306')),
            'username' => env('SQL_EXERCISE_RUNTIME_USERNAME', 'sql_exercise_reader'),
            'password' => env('SQL_EXERCISE_RUNTIME_PASSWORD', ''),
            'grant_host' => env('SQL_EXERCISE_RUNTIME_GRANT_HOST', 'localhost'),
        ],
    ],
];
