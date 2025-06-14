<?php

use Illuminate\Support\Facades\Config;

return [
    /*
    |--------------------------------------------------------------------------
    | Default Connection
    |--------------------------------------------------------------------------
    |
    | The default database connection to use for model generation
    |
    */
    'default_connection' => Config::get('database.default', 'mysql'),

    /*
    |--------------------------------------------------------------------------
    | Excluded Tables
    |--------------------------------------------------------------------------
    |
    | Tables that should be excluded from model generation
    |
    */
    'excluded_tables' => [
        'migrations',
        'failed_jobs',
        'password_reset_tokens',
        'personal_access_tokens',
        'sessions',
        'cache',
        'jobs',
        'cache_locks',
        'job_batches',
    ],
];
