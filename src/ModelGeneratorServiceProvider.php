<?php

declare(strict_types=1);

namespace Wink\ModelGenerator;

use Illuminate\Support\ServiceProvider;
use Wink\ModelGenerator\Commands\GenerateModels;
use Wink\ModelGenerator\Commands\ValidateModelNamespaces;
use Wink\ModelGenerator\Commands\ValidateFactoryNamespaces;
use Wink\ModelGenerator\Config\GeneratorConfig;

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
                ValidateFactoryNamespaces::class,
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
