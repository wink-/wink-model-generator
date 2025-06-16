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

    /*
    |--------------------------------------------------------------------------
    | Model Property Generation Options
    |--------------------------------------------------------------------------
    |
    | Configure which Laravel model properties should be automatically generated
    |
    */
    'model_properties' => [
        // Auto-detect primary key from schema instead of hardcoding 'id'
        'auto_detect_primary_key' => true,

        // Generate $hidden array for sensitive fields (password, token, etc.)
        'auto_hidden_fields' => true,
        'hidden_field_patterns' => ['password', 'token', 'secret', 'key', 'hash'],

        // Generate $guarded array (alternative to $fillable)
        'use_guarded_instead_of_fillable' => false,
        'guarded_fields' => ['id', 'created_at', 'updated_at'],

        // Pagination settings
        'per_page' => null, // Set to integer to override default pagination

        // Date format customization
        'date_format' => 'Y-m-d H:i:s', // Laravel default

        // Auto-generate $attributes for default values based on schema
        'auto_default_attributes' => true,

        // Auto-generate $with for common relationships
        'auto_eager_load' => false,
        'eager_load_relationships' => [], // e.g., ['user', 'category']

        // Generate $appends for accessor attributes
        'auto_appends' => false,

        // Generate $touches for related model timestamps
        'auto_touches' => false,

        // Soft delete detection
        'auto_detect_soft_deletes' => true,

        // Generate $visible array (alternative to $hidden)
        'use_visible_instead_of_hidden' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Field Type Mappings
    |--------------------------------------------------------------------------
    |
    | Customize how database types are cast in models
    |
    */
    'custom_casts' => [
        // 'json' => 'array',
        // 'enum' => 'string',
        // Add custom cast mappings here
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation Rules Generation
    |--------------------------------------------------------------------------
    |
    | Configure automatic validation rule generation
    |
    */
    'validation' => [
        'generate_rules' => true,
        'strict_rules' => false, // Generate stricter validation rules
        'include_unique_rules' => true, // Add unique rules for indexed fields
    ],
];
