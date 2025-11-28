<?php

return [
    /*
    |-------------------------------------------------------------------------
    | Default Connection
    |-------------------------------------------------------------------------
    |
    | The default database connection to use when none is specified via
    | the artisan command option. This should typically mirror your
    | application's configured default connection.
    |
    */
    'default_connection' => env('DB_CONNECTION', config('database.default')),

    /*
    |-------------------------------------------------------------------------
    | Excluded Tables
    |-------------------------------------------------------------------------
    |
    | Tables that should be excluded from model generation. These cover common
    | Laravel/system tables across MySQL, PostgreSQL, and SQLite.
    |
    */
    'excluded_tables' => [
        // Laravel framework tables
        'migrations',
        'failed_jobs',
        'password_reset_tokens',
        'personal_access_tokens',
        'sessions',
        // Cache/queue tables
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        // Telescope/Horizon (if installed)
        'telescope_entries',
        'telescope_entries_tags',
        'telescope_monitoring',
        'horizon_jobs',
        'horizon_monitors',
        // Sanctum/Passport
        'oauth_access_tokens',
        'oauth_auth_codes',
        'oauth_clients',
        'oauth_personal_access_clients',
        'oauth_refresh_tokens',
    ],

    /*
    |-------------------------------------------------------------------------
    | Model Namespace
    |-------------------------------------------------------------------------
    |
    | The namespace for generated models.
    |
    */
    'model_namespace' => 'App\\Models',

    /*
    |-------------------------------------------------------------------------
    | Factory Namespace
    |-------------------------------------------------------------------------
    |
    | The namespace for generated model factories.
    |
    */
    'factory_namespace' => 'Database\\Factories',

    /*
    |-------------------------------------------------------------------------
    | Observer Namespace
    |-------------------------------------------------------------------------
    |
    | The namespace for generated model observers.
    |
    */
    'observer_namespace' => 'App\\Observers',

    /*
    |-------------------------------------------------------------------------
    | Paths
    |-------------------------------------------------------------------------
    |
    | Base paths for generated artifacts. These can be overridden via command
    | options; when not provided, a connection-based directory structure will
    | be created underneath these paths.
    |
    */
    'model_path' => app_path('Models'),
    'factory_path' => database_path('factories'),
    'observer_path' => app_path('Observers'),
    'resource_path' => app_path('Http/Resources'),

    /*
    |-------------------------------------------------------------------------
    | Model Properties
    |-------------------------------------------------------------------------
    |
    | Sensible defaults for Laravel model generation based on database schema.
    |
    */
    'model_properties' => [
        // Core detection
        'auto_detect_primary_key' => true,
        'auto_detect_soft_deletes' => true,
        // Timestamps are detected implicitly by generator (created_at/updated_at)

        // Security & visibility
        'auto_hidden_fields' => true,
        'hidden_field_patterns' => ['password', 'token', 'secret', 'key', 'hash'],
        'use_visible_instead_of_hidden' => false,

        // Mass assignment
        'use_guarded_instead_of_fillable' => false,
        'guarded_fields' => ['id', 'created_at', 'updated_at', 'deleted_at'],

        // Pagination & dates
        'per_page' => null, // keep framework default
        'date_format' => 'Y-m-d H:i:s',

        // Attributes & eager loading
        'auto_default_attributes' => true,
        'auto_eager_load' => false,
        'eager_load_relationships' => [],
        'auto_appends' => false,
        'auto_touches' => false,

        // Scope generation
        'auto_generate_scopes' => false,
        'auto_generate_timestamp_scopes' => true,

        // Column pattern helpers
        'boolean_scope_patterns' => [
            'is_active' => ['active', 'inactive'],
            'is_published' => ['published', 'unpublished'],
            'is_featured' => ['featured', 'notFeatured'],
            'is_enabled' => ['enabled', 'disabled'],
            'is_verified' => ['verified', 'unverified'],
            'is_approved' => ['approved', 'unapproved'],
            'is_visible' => ['visible', 'hidden'],
            'is_archived' => ['archived', 'notArchived'],
        ],
        'boolean_column_patterns' => [
            'is_', 'has_', 'can_', 'should_', 'will_', 'active', 'enabled',
            'published', 'featured', 'verified', 'approved', 'visible', 'archived',
        ],
        'status_column_patterns' => [
            'status', 'state', 'type', 'category', 'kind', 'mode',
        ],
        'searchable_column_patterns' => [
            'name', 'title', 'description', 'content', 'body', 'summary',
            'subject', 'message', 'comment', 'note', 'email', 'username', 'slug',
        ],
    ],

    /*
    |-------------------------------------------------------------------------
    | Validation Rules
    |-------------------------------------------------------------------------
    |
    | Controls generation of validation rules inferred from the schema.
    |
    */
    'validation' => [
        'generate_rules' => true,
        'strict_rules' => false,
        'include_unique_rules' => true,
    ],

    /*
    |-------------------------------------------------------------------------
    | Observer Properties
    |-------------------------------------------------------------------------
    |
    | Defaults for observer generation. Observers are off by default to keep
    | the "simple tool" experience minimal, but can be enabled via the command
    | options.
    |
    */
    'observer_properties' => [
        'generate_observers' => false,
        'observer_events' => ['creating', 'created', 'updating', 'updated', 'deleting', 'deleted', 'saving', 'saved'],
        'exclude_events' => [],
        'include_retrieved' => false,
        'include_booted' => false,
        'auto_register_observers' => true,
        'observer_method_stubs' => true,
        'observer_connection_based' => true,
    ],
];