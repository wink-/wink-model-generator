<?php

declare(strict_types=1);

namespace Tests\Unit;

use Orchestra\Testbench\TestCase;
use Wink\ModelGenerator\Config\GeneratorConfig;
use Wink\ModelGenerator\Generators\EventsGenerator;
use Wink\ModelGenerator\ModelGeneratorServiceProvider;

class EventsGeneratorTest extends TestCase
{
    private EventsGenerator $eventsGenerator;
    private GeneratorConfig $config;

    protected function getPackageProviders($app)
    {
        return [ModelGeneratorServiceProvider::class];
    }

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->config = new GeneratorConfig([
            'model_properties' => [
                'generate_event_methods' => true,
                'generate_boot_method' => true,
                'model_events' => ['creating', 'created', 'updating', 'updated'],
                'event_method_stubs' => true,
            ]
        ]);
        
        $this->eventsGenerator = new EventsGenerator($this->config);
    }

    public function test_generates_event_methods(): void
    {
        $methods = $this->eventsGenerator->generateEventMethods('User');
        
        $this->assertCount(4, $methods);
        $this->assertStringContainsString('public function handleCreating(): void', $methods[0]);
        $this->assertStringContainsString('public function handleCreated(): void', $methods[1]);
        $this->assertStringContainsString('TODO: Implement creating event logic', $methods[0]);
    }

    public function test_generates_boot_method(): void
    {
        $bootMethod = $this->eventsGenerator->generateBootMethod('User');
        
        $this->assertStringContainsString('protected static function boot(): void', $bootMethod);
        $this->assertStringContainsString('parent::boot();', $bootMethod);
        $this->assertStringContainsString('static::creating(function ($model) {', $bootMethod);
        $this->assertStringContainsString('$model->handleCreating();', $bootMethod);
    }

    public function test_generates_event_methods_with_soft_deletes(): void
    {
        $methods = $this->eventsGenerator->generateEventMethods('User', true);
        
        $this->assertCount(6, $methods); // 4 + 2 soft delete events
        $methodNames = array_map(function($method) {
            preg_match('/function (\w+)\(\)/', $method, $matches);
            return $matches[1] ?? '';
        }, $methods);
        
        $this->assertContains('handleRestoring', $methodNames);
        $this->assertContains('handleRestored', $methodNames);
    }

    public function test_empty_event_methods_when_no_events_configured(): void
    {
        $config = new GeneratorConfig([
            'model_properties' => [
                'model_events' => [],
            ]
        ]);
        
        $eventsGenerator = new EventsGenerator($config);
        $methods = $eventsGenerator->generateEventMethods('User');
        
        $this->assertEmpty($methods);
    }

    public function test_empty_boot_method_when_no_events_configured(): void
    {
        $config = new GeneratorConfig([
            'model_properties' => [
                'model_events' => [],
            ]
        ]);
        
        $eventsGenerator = new EventsGenerator($config);
        $bootMethod = $eventsGenerator->generateBootMethod('User');
        
        $this->assertEmpty($bootMethod);
    }
}