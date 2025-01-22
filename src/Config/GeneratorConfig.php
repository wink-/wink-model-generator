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

    public function __construct()
    {
        $this->excludedTables = Config::get('model-generator.excluded_tables', [
            'migrations',
            'failed_jobs',
            'password_reset_tokens',
            'personal_access_tokens',
            'sessions',
            'cache',
            'jobs',
            'cache_locks',
            'job_batches'
        ]);

        $this->modelNamespace = Config::get('model-generator.model_namespace', 'App\\Models\\GeneratedModels');
        $this->factoryNamespace = Config::get('model-generator.factory_namespace', 'Database\\Factories\\GeneratedFactories');
        $this->modelPath = Config::get('model-generator.model_path', app_path('Models/GeneratedModels'));
        $this->factoryPath = Config::get('model-generator.factory_path', database_path('factories/GeneratedFactories'));
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
}
