<?php

declare(strict_types=1);

namespace Tests\Feature;

use Orchestra\Testbench\TestCase;
use Illuminate\Filesystem\Filesystem;
use Wink\ModelGenerator\ModelGeneratorServiceProvider;

class PolicyGeneratorCommandTest extends TestCase
{
    private Filesystem $filesystem;
    private string $tempPath;

    protected function getPackageProviders($app)
    {
        return [ModelGeneratorServiceProvider::class];
    }

    protected function defineEnvironment($app)
    {
        $this->tempPath = __DIR__ . '/tmp';

        $app['config']->set('model-generator.model_namespace', 'App\\Models');
        $app['config']->set('model-generator.policy_namespace', 'App\\Policies');
        $app['config']->set('model-generator.policy_path', $this->tempPath . '/policies');
        $app['config']->set('model-generator.model_path', $this->tempPath . '/models');

        // Configure database
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->filesystem = new Filesystem();

        // Create a test users table
        $this->app['db']->connection()->statement(
            'CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, email TEXT)'
        );
    }

    protected function tearDown(): void
    {
        if ($this->filesystem->exists(__DIR__ . '/tmp')) {
            $this->filesystem->deleteDirectory(__DIR__ . '/tmp');
        }

        parent::tearDown();
    }

    public function testPolicyGenerationThroughCommand(): void
    {
        $this->artisan('wink:generate-models', [
            '--with-policies' => true
        ])->assertSuccessful();

        $policyPath = $this->tempPath . '/policies/UserPolicy.php';
        $this->assertTrue($this->filesystem->exists($policyPath));
        
        $content = $this->filesystem->get($policyPath);
        $this->assertStringContainsString('namespace App\\Policies;', $content);
        $this->assertStringContainsString('use App\\Models\\User;', $content);
        $this->assertStringContainsString('class UserPolicy', $content);
    }

    public function testPolicyGenerationWithCustomPath(): void
    {
        $customPath = __DIR__ . '/tmp/custom/policies';
        $this->app['config']->set('model-generator.policy_path', $customPath);

        $this->artisan('wink:generate-models', [
            '--with-policies' => true
        ])->assertSuccessful();

        $policyPath = $customPath . '/UserPolicy.php';
        $this->assertTrue($this->filesystem->exists($policyPath));
        
        $content = $this->filesystem->get($policyPath);
        $this->assertStringContainsString('namespace App\\Policies;', $content);
        $this->assertStringContainsString('use App\\Models\\User;', $content);
    }

    public function testPolicyGenerationWithCustomNamespace(): void
    {
        $this->app['config']->set('model-generator.policy_namespace', 'Custom\\Policies');
        $this->app['config']->set('model-generator.model_namespace', 'Custom\\Models');

        $this->artisan('wink:generate-models', [
            '--with-policies' => true
        ])->assertSuccessful();

        $policyPath = $this->tempPath . '/policies/UserPolicy.php';
        $this->assertTrue($this->filesystem->exists($policyPath));
        
        $content = $this->filesystem->get($policyPath);
        $this->assertStringContainsString('namespace Custom\\Policies;', $content);
        $this->assertStringContainsString('use Custom\\Models\\User;', $content);
        $this->assertStringContainsString('class UserPolicy', $content);
    }
}