<?php

namespace Wink\ModelGenerator\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Wink\ModelGenerator\ModelGeneratorServiceProvider;

abstract class TestCase extends Orchestra
{
    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            ModelGeneratorServiceProvider::class,
        ];
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     */
    protected function defineEnvironment($app): void
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        // Set the model generator config
        $app['config']->set('model-generator.default_connection', 'testing');
        $app['config']->set('model-generator.excluded_tables', [
            'migrations',
            'failed_jobs',
        ]);
    }
} 