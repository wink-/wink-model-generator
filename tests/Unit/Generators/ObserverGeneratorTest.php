<?php

declare(strict_types=1);

namespace Tests\Unit\Generators;

use Tests\TestCase;
use Wink\ModelGenerator\Config\GeneratorConfig;
use Wink\ModelGenerator\Generators\ObserverGenerator;

class ObserverGeneratorTest extends TestCase
{
    private ObserverGenerator $observerGenerator;

    protected function setUp(): void
    {
        parent::setUp();
        
        $config = new GeneratorConfig([
            'observer_namespace' => 'App\\Observers',
            'observer_properties' => [
                'generate_observers' => true,
                'observer_events' => ['creating', 'created', 'updating', 'updated'],
                'exclude_events' => [],
                'include_retrieved' => false,
                'include_booted' => false,
                'observer_method_stubs' => true,
                'observer_connection_based' => true,
            ],
        ]);
        
        $this->observerGenerator = new ObserverGenerator($config);
    }

    public function test_generates_observer_class(): void
    {
        $observerContent = $this->observerGenerator->generate(
            'User',
            'App\\Models\\GeneratedModels\\Sqlite',
            'sqlite',
            [],
            false
        );

        $this->assertStringContainsString('namespace App\\Observers\\Sqlite;', $observerContent);
        $this->assertStringContainsString('class UserObserver', $observerContent);
        $this->assertStringContainsString('use App\\Models\\GeneratedModels\\Sqlite\\User;', $observerContent);
        $this->assertStringContainsString('public function creating(User $user): void', $observerContent);
        $this->assertStringContainsString('public function created(User $user): void', $observerContent);
        $this->assertStringContainsString('public function updating(User $user): void', $observerContent);
        $this->assertStringContainsString('public function updated(User $user): void', $observerContent);
        $this->assertStringContainsString('// Validate business constraints', $observerContent);
    }

    public function test_includes_soft_delete_events_when_enabled(): void
    {
        $observerContent = $this->observerGenerator->generate(
            'User',
            'App\\Models\\GeneratedModels\\Sqlite',
            'sqlite',
            [],
            true // has soft deletes
        );

        $this->assertStringContainsString('public function restoring(User $user): void', $observerContent);
        $this->assertStringContainsString('public function restored(User $user): void', $observerContent);
    }

    public function test_excludes_events_when_configured(): void
    {
        $config = new GeneratorConfig([
            'observer_namespace' => 'App\\Observers',
            'observer_properties' => [
                'generate_observers' => true,
                'observer_events' => ['creating', 'created', 'updating', 'updated', 'deleting', 'deleted'],
                'exclude_events' => ['deleting', 'deleted'],
                'observer_method_stubs' => true,
                'observer_connection_based' => true,
            ],
        ]);
        
        $observerGenerator = new ObserverGenerator($config);
        
        $observerContent = $observerGenerator->generate(
            'User',
            'App\\Models\\GeneratedModels\\Sqlite',
            'sqlite',
            [],
            false
        );

        $this->assertStringContainsString('public function creating(User $user): void', $observerContent);
        $this->assertStringContainsString('public function created(User $user): void', $observerContent);
        $this->assertStringContainsString('public function updating(User $user): void', $observerContent);
        $this->assertStringContainsString('public function updated(User $user): void', $observerContent);
        $this->assertStringNotContainsString('public function deleting(User $user): void', $observerContent);
        $this->assertStringNotContainsString('public function deleted(User $user): void', $observerContent);
    }
}