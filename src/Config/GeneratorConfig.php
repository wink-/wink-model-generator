<?php

declare(strict_types=1);

namespace Wink\ModelGenerator\Config;

use Illuminate\Support\Facades\Config;

class GeneratorConfig
{
    private array $excludedTables;

    private string $modelNamespace;

    private string $factoryNamespace;

    private string $modelPath;

    private string $factoryPath;

    private string $resourcePath;

    private array $modelProperties;

    private string $observerNamespace;

    private string $observerPath;

    private array $observerProperties;

    public function __construct(array $config = [])
    {
        // Use provided config or fallback to Laravel config
        $defaultExcluded = [
            'migrations',
            'failed_jobs',
            'password_reset_tokens',
            'personal_access_tokens',
            'sessions',
            'cache',
            'jobs',
            'cache_locks',
            'job_batches',
        ];

        $this->excludedTables = $config['excluded_tables'] ?? Config::get('model-generator.excluded_tables', $defaultExcluded);

        $this->modelNamespace = $config['model_namespace'] ?? Config::get('model-generator.model_namespace', 'App\\Models');
        $this->factoryNamespace = $config['factory_namespace'] ?? Config::get('model-generator.factory_namespace', 'Database\\Factories');
        $this->modelPath = $config['model_path'] ?? Config::get('model-generator.model_path', app_path('Models'));
        $this->factoryPath = $config['factory_path'] ?? Config::get('model-generator.factory_path', database_path('factories'));
        $this->resourcePath = $config['resource_path'] ?? Config::get('model-generator.resource_path', app_path('Http/Resources'));

        $this->observerNamespace = $config['observer_namespace'] ?? Config::get('model-generator.observer_namespace', 'App\\Observers');
        $this->observerPath = $config['observer_path'] ?? Config::get('model-generator.observer_path', app_path('Observers'));

        $defaultModelProperties = [
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
            'auto_generate_scopes' => false,
            'auto_generate_timestamp_scopes' => true,
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
                'is_', 'has_', 'can_', 'should_', 'will_', 'active', 'enabled', 'published', 'featured', 'verified', 'approved', 'visible', 'archived',
            ],
            'status_column_patterns' => [
                'status', 'state', 'type', 'category', 'kind', 'mode',
            ],
            'searchable_column_patterns' => [
                'name', 'title', 'description', 'content', 'body', 'summary', 'subject', 'message', 'comment', 'note', 'email', 'username', 'slug',
            ],
            // Event generation configuration
            'generate_event_methods' => false,
            'generate_boot_method' => false,
            'model_events' => ['creating', 'created', 'updating', 'updated', 'deleting', 'deleted', 'saving', 'saved'],
            'exclude_model_events' => [],
            'include_retrieved_event' => false,
            'include_booted_event' => false,
            'event_method_stubs' => true,
            'event_method_type' => 'direct', // 'direct' or 'boot'
        ];

        $this->modelProperties = $config['model_properties'] ?? Config::get('model-generator.model_properties', $defaultModelProperties);

        $defaultObserverProperties = [
            'generate_observers' => false,
            'observer_events' => ['creating', 'created', 'updating', 'updated', 'deleting', 'deleted', 'saving', 'saved'],
            'exclude_events' => [],
            'include_retrieved' => false,
            'include_booted' => false,
            'auto_register_observers' => true,
            'observer_method_stubs' => true,
            'observer_connection_based' => true,
        ];

        $this->observerProperties = $config['observer_properties'] ?? Config::get('model-generator.observer_properties', $defaultObserverProperties);
    }

    public function getExcludedTables(): array
    {
        return $this->excludedTables;
    }

    public function getModelNamespace(): string
    {
        return $this->modelNamespace;
    }

    public function getFactoryNamespace(): string
    {
        return $this->factoryNamespace;
    }

    public function getModelPath(): string
    {
        return $this->modelPath;
    }

    public function getFactoryPath(): string
    {
        return $this->factoryPath;
    }

    public function getResourcePath(): string
    {
        return $this->resourcePath;
    }

    public function getModelProperties(): array
    {
        return $this->modelProperties;
    }

    public function getModelProperty(string $key, $default = null)
    {
        return $this->modelProperties[$key] ?? $default;
    }

    public function getObserverNamespace(): string
    {
        return $this->observerNamespace;
    }

    public function getObserverPath(): string
    {
        return $this->observerPath;
    }

    public function getObserverProperties(): array
    {
        return $this->observerProperties;
    }

    public function getObserverProperty(string $key, $default = null)
    {
        return $this->observerProperties[$key] ?? $default;
    }
}
