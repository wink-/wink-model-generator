<?php

declare(strict_types=1);

namespace Tests\Unit\Generators;

use Orchestra\Testbench\TestCase;
use Wink\ModelGenerator\Generators\PolicyGenerator;
use Illuminate\Filesystem\Filesystem;

class PolicyGeneratorTest extends TestCase
{
    private PolicyGenerator $generator;
    private Filesystem $filesystem;
    private string $tempPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempPath = __DIR__ . '/tmp/policies';
        $this->filesystem = new Filesystem();

        if ($this->filesystem->exists(__DIR__ . '/tmp')) {
            $this->filesystem->deleteDirectory(__DIR__ . '/tmp');
        }

        $this->generator = new PolicyGenerator(
            $this->filesystem,
            __DIR__ . '/../../../src/Templates',
            'App\\Policies',
            'App\\Models',
            $this->tempPath
        );
    }

    public function testGeneratePolicy(): void
    {
        $modelName = 'User';
        $policy = $this->generator->generate($modelName);

        $this->assertStringContainsString('namespace App\\Policies;', $policy);
        $this->assertStringContainsString('class UserPolicy', $policy);
        $this->assertStringContainsString('use App\\Models\\User;', $policy);
    }

    public function testGeneratePolicyWithCustomNamespace(): void
    {
        $customGenerator = new PolicyGenerator(
            $this->filesystem,
            __DIR__ . '/../../../src/Templates',
            'Custom\\Policies',
            'Custom\\Models',
            $this->tempPath
        );

        $modelName = 'Post';
        $policy = $customGenerator->generate($modelName);

        $this->assertStringContainsString('namespace Custom\\Policies;', $policy);
        $this->assertStringContainsString('use Custom\\Models\\Post;', $policy);
    }

    public function testPolicyIncludesCrudMethods(): void
    {
        $modelName = 'Product';
        $policy = $this->generator->generate($modelName);

        $this->assertStringContainsString('public function viewAny', $policy);
        $this->assertStringContainsString('public function view', $policy);
        $this->assertStringContainsString('public function create', $policy);
        $this->assertStringContainsString('public function update', $policy);
        $this->assertStringContainsString('public function delete', $policy);
    }
}