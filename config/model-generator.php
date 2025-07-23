<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Excluded Tables
    |--------------------------------------------------------------------------
    |
    | Tables that should be excluded from model generation.
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
    | Model Namespace
    |--------------------------------------------------------------------------
    |
    | The namespace for generated models.
    |
    */
    'model_namespace' => 'App\\Models',

    /*
    |--------------------------------------------------------------------------
    | Factory Namespace
    |--------------------------------------------------------------------------
    |
    | The namespace for generated model factories.
    |
    */
    'factory_namespace' => 'Database\\Factories',

    /*
    |--------------------------------------------------------------------------
    | Model Path
    |--------------------------------------------------------------------------
    |
    | The path where models should be generated.
    |
    */
    'model_path' => app_path('Models'),

    /*
    |--------------------------------------------------------------------------
    | Factory Path
    |--------------------------------------------------------------------------
    |
    | The path where factories should be generated.
    |
    */
    'factory_path' => database_path('factories'),

    /*
    |--------------------------------------------------------------------------
    | Resource Path
    |--------------------------------------------------------------------------
    |
    | The path where API resources should be generated.
    |
    */
    'resource_path' => app_path('Http/Resources'),

    /*
    |--------------------------------------------------------------------------
    | Observer Namespace
    |--------------------------------------------------------------------------
    |
    | The namespace for generated model observers.
    |
    */
    'observer_namespace' => 'App\\Observers',

    /*
    |--------------------------------------------------------------------------
    | Observer Path
    |--------------------------------------------------------------------------
    |
    | The path where observers should be generated.
    |
    */
    'observer_path' => app_path('Observers'),

    /*
    |--------------------------------------------------------------------------
    | Model Properties
    |--------------------------------------------------------------------------
    |
    | Configuration for model generation behavior.
    |
    */
    'model_properties' => [
        'auto_detect_primary_key' => true,
        'auto_hidden_fields' => true,
        'hidden_field_patterns' => ['password', 'token', 'secret', 'key', 'hash'],
        'use_guarded_instead_of_fillable' => false,
        'guarded_fields' => ['id', 'created_at', 'updated_at'],
        'per_page' => null,
        'date_format' => 'Y-m-d H:i:s',
        'auto_default_attributes' => true,
        'auto_eager_load' => false,
        'eager_load_relationships' => [],
        'auto_appends' => false,
        'auto_touches' => false,
        'auto_detect_soft_deletes' => true,
        'use_visible_instead_of_hidden' => false,

        /*
        |--------------------------------------------------------------------------
        | Scope Generation
        |--------------------------------------------------------------------------
        |
        | Configure automatic scope generation for models.
        |
        */
        'auto_generate_scopes' => false,
        'auto_generate_timestamp_scopes' => true,

        /*
        |--------------------------------------------------------------------------
        | Boolean Scope Patterns
        |--------------------------------------------------------------------------
        |
        | Define patterns for boolean columns and their corresponding scope names.
        |
        */
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

        /*
        |--------------------------------------------------------------------------
        | Boolean Column Patterns
        |--------------------------------------------------------------------------
        |
        | Column name patterns that should be treated as boolean columns.
        |
        */
        'boolean_column_patterns' => [
            'is_', 'has_', 'can_', 'should_', 'will_', 'active', 'enabled',
            'published', 'featured', 'verified', 'approved', 'visible', 'archived',
        ],

        /*
        |--------------------------------------------------------------------------
        | Status Column Patterns
        |--------------------------------------------------------------------------
        |
        | Column name patterns that should be treated as status columns.
        |
        */
        'status_column_patterns' => [
            'status', 'state', 'type', 'category', 'kind', 'mode',
        ],

        /*
        |--------------------------------------------------------------------------
        | Searchable Column Patterns
        |--------------------------------------------------------------------------
        |
        | Column name patterns that should have search scopes generated.
        |
        */
        'searchable_column_patterns' => [
            'name', 'title', 'description', 'content', 'body', 'summary',
            'subject', 'message', 'comment', 'note', 'email', 'username', 'slug',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Observer Properties
    |--------------------------------------------------------------------------
    |
    | Configuration for observer generation behavior.
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
