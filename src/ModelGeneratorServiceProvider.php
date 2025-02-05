<?php

declare(strict_types=1);

namespace Wink\ModelGenerator;

use Illuminate\Support\ServiceProvider;
use Wink\ModelGenerator\Commands\GenerateModels;
use Wink\ModelGenerator\Commands\ValidateModelNamespaces;
use Wink\ModelGenerator\Config\GeneratorConfig;
use Wink\ModelGenerator\Database\SchemaReader;
use Wink\ModelGenerator\Database\SqliteSchemaReader;
use Wink\ModelGenerator\Database\MySqlSchemaReader;
use Wink\ModelGenerator\Services\FileService;
use Wink\ModelGenerator\Services\ModelService;

class ModelGeneratorServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Merge config
        $this->mergeConfigFrom(
            __DIR__ . '/../config/model-generator.php',
            'model-generator'
        );

        $this->app->singleton(GeneratorConfig::class, function ($app) {
            return new GeneratorConfig();
        });

        // Bind SchemaReader to the appropriate implementation based on the default database connection
        $this->app->bind(SchemaReader::class, function ($app) {
            $connection = config('database.default');
            $driver = config("database.connections.{$connection}.driver");

            return match ($driver) {
                'sqlite' => new SqliteSchemaReader(),
                'mysql' => new MySqlSchemaReader(),
                default => throw new \RuntimeException("Unsupported database driver: {$driver}")
            };
        });

        // Register FileService
        $this->app->singleton(FileService::class, function ($app) {
            return new FileService();
        });

        // Register ModelService
        $this->app->singleton(ModelService::class, function ($app) {
            return new ModelService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                GenerateModels::class,
                ValidateModelNamespaces::class,
            ]);

            // Optional: Publish configuration
            $this->publishes([
                __DIR__ . '/../config/model-generator.php' => config_path('model-generator.php'),
            ], 'config');
        }

        // Load migrations for testing
        if ($this->app->environment('testing')) {
            $this->loadMigrationsFrom(__DIR__ . '/../tests/database/migrations');
        }
    }
}
