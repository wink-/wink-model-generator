<?php

namespace Wink\ModelGenerator\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Wink\ModelGenerator\ModelGeneratorServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app)
    {
        return [
            ModelGeneratorServiceProvider::class,
        ];
    }
} 