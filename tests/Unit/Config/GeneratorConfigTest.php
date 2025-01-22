<?php

declare(strict_types=1);

namespace Tests\Unit\Config;

use Orchestra\Testbench\TestCase;
use Wink\ModelGenerator\Config\GeneratorConfig;
use Illuminate\Support\Facades\Config;
use Wink\ModelGenerator\ModelGeneratorServiceProvider;

class GeneratorConfigTest extends TestCase
{
    private GeneratorConfig $config;

    protected function setUp(): void
    {
        parent::setUp();

        // Set up config values
        Config::set('model-generator.excluded_tables', ['migrations', 'failed_jobs']);
        Config::set('model-generator.model_namespace', 'App\\Models\\Generated');
        Config::set('model-generator.factory_namespace', 'Database\\Factories\\Generated');
        Config::set('model-generator.model_path', '/path/to/models');
        Config::set('model-generator.factory_path', '/path/to/factories');
        
        $this->config = new GeneratorConfig();
    }

    protected function getPackageProviders($app)
    {
        return [
            ModelGeneratorServiceProvider::class,
        ];
    }

    public function testGetExcludedTables(): void
    {
        $excludedTables = $this->config->getExcludedTables();
        
        $this->assertIsArray($excludedTables);
        $this->assertContains('migrations', $excludedTables);
        $this->assertContains('failed_jobs', $excludedTables);
    }

    public function testGetModelNamespace(): void
    {
        $namespace = $this->config->getModelNamespace();
        
        $this->assertEquals('App\\Models\\Generated', $namespace);
    }

    public function testGetFactoryNamespace(): void
    {
        $namespace = $this->config->getFactoryNamespace();
        
        $this->assertEquals('Database\\Factories\\Generated', $namespace);
    }

    public function testGetModelPath(): void
    {
        $path = $this->config->getModelPath();
        
        $this->assertEquals('/path/to/models', $path);
    }

    public function testGetFactoryPath(): void
    {
        $path = $this->config->getFactoryPath();
        
        $this->assertEquals('/path/to/factories', $path);
    }
}
