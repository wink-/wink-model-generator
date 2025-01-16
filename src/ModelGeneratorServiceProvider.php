
<?php

namespace Wink\ModelGenerator;

use Illuminate\Support\ServiceProvider;
use App\Console\Commands\GenerateModels;

class ModelGeneratorServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register the command if we are using the application via CLI
        if ($this->app->runningInConsole()) {
            $this->commands([
                GenerateModels::class,
            ]);
        }
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Optional: Publish configuration
        $this->publishes([
            __DIR__.'/../config/model-generator.php' => config_path('model-generator.php'),
        ], 'model-generator-config');
    }
}