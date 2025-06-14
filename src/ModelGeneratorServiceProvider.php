<?php

declare(strict_types=1);

namespace Wink\ModelGenerator;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Wink\ModelGenerator\Commands\GenerateModels;
use Wink\ModelGenerator\Commands\ValidateModelNamespaces;
use Wink\ModelGenerator\Config\GeneratorConfig;
use Wink\ModelGenerator\Database\MySqlSchemaReader;
use Wink\ModelGenerator\Database\PostgreSqlSchemaReader;
use Wink\ModelGenerator\Database\SchemaReader;
use Wink\ModelGenerator\Database\SqliteSchemaReader;
use Wink\ModelGenerator\Services\FileService;
use Wink\ModelGenerator\Services\ModelService;

class ModelGeneratorServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('wink-model-generator')
            ->hasConfigFile('model-generator')
            ->hasCommands([
                GenerateModels::class,
                ValidateModelNamespaces::class,
            ]);
    }

    public function boot(): void
    {
        parent::boot();

        $this->app->singleton(GeneratorConfig::class, function ($app) {
            return new GeneratorConfig;
        });

        // Bind SchemaReader to the appropriate implementation based on the default database connection
        $this->app->bind(SchemaReader::class, function ($app) {
            $connection = config('database.default');
            $driver = config("database.connections.{$connection}.driver");

            return match ($driver) {
                'sqlite' => new SqliteSchemaReader,
                'mysql' => new MySqlSchemaReader,
                'pgsql' => new PostgreSqlSchemaReader,
                default => throw new \RuntimeException("Unsupported database driver: {$driver}")
            };
        });

        // Register FileService
        $this->app->singleton(FileService::class, function ($app) {
            return new FileService;
        });

        // Register ModelService
        $this->app->singleton(ModelService::class, function ($app) {
            return new ModelService;
        });

        if ($this->app->environment('testing')) {
            $this->loadMigrationsFrom(__DIR__.'/../tests/database/migrations');
        }
    }
}
