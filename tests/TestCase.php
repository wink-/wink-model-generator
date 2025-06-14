<?php

namespace Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Wink\ModelGenerator\ModelGeneratorServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            ModelGeneratorServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        // Use SQLite in-memory database
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
    }
}
