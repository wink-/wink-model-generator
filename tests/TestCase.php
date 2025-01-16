<?php

namespace Wink\ModelGenerator\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Wink\ModelGenerator\ModelGeneratorServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            ModelGeneratorServiceProvider::class,
        ];
    }

    protected function defineDatabaseMigrations()
    {
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
    }
}
