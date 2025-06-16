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
        ];

        $this->modelProperties = $config['model_properties'] ?? Config::get('model-generator.model_properties', $defaultModelProperties);
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
}
