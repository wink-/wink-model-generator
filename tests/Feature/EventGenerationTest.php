<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Orchestra\Testbench\TestCase;
use Wink\ModelGenerator\Config\GeneratorConfig;
use Wink\ModelGenerator\Generators\ModelGenerator;
use Wink\ModelGenerator\ModelGeneratorServiceProvider;
use Wink\ModelGenerator\Services\FileService;

class EventGenerationTest extends TestCase
{
    private ModelGenerator $modelGenerator;
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
        
        $this->modelGenerator = new ModelGenerator($this->config, new FileService());
    }

    public function test_generates_model_with_event_methods(): void
    {
        $columns = [
            (object) ['name' => 'id', 'type' => 'integer', 'primary' => true],
            (object) ['name' => 'name', 'type' => 'varchar', 'length' => 255],
            (object) ['name' => 'email', 'type' => 'varchar', 'length' => 255],
            (object) ['name' => 'created_at', 'type' => 'datetime'],
            (object) ['name' => 'updated_at', 'type' => 'datetime'],
        ];

        $modelContent = $this->modelGenerator->generate(
            modelName: 'User',
            tableName: 'users',
            connection: 'sqlite',
            columns: $columns,
            foreignKeys: [],
            withRelationships: false,
            withRules: false,
            withEvents: true
        );

        // Check that event methods are generated
        $this->assertStringContainsString('public function handleCreating(): void', $modelContent);
        $this->assertStringContainsString('public function handleCreated(): void', $modelContent);
        $this->assertStringContainsString('public function handleUpdating(): void', $modelContent);
        $this->assertStringContainsString('public function handleUpdated(): void', $modelContent);
        
        // Check that boot method is generated
        $this->assertStringContainsString('protected static function boot(): void', $modelContent);
        $this->assertStringContainsString('parent::boot();', $modelContent);
        $this->assertStringContainsString('static::creating(function ($model) {', $modelContent);
        $this->assertStringContainsString('$model->handleCreating();', $modelContent);
        
        // Check event method comments
        $this->assertStringContainsString('Handle the model "creating" event.', $modelContent);
        $this->assertStringContainsString('TODO: Implement creating event logic', $modelContent);
    }

    public function test_generates_model_with_soft_delete_events(): void
    {
        $columns = [
            (object) ['name' => 'id', 'type' => 'integer', 'primary' => true],
            (object) ['name' => 'name', 'type' => 'varchar', 'length' => 255],
            (object) ['name' => 'deleted_at', 'type' => 'datetime'],
            (object) ['name' => 'created_at', 'type' => 'datetime'],
            (object) ['name' => 'updated_at', 'type' => 'datetime'],
        ];

        $modelContent = $this->modelGenerator->generate(
            modelName: 'User',
            tableName: 'users',
            connection: 'sqlite',
            columns: $columns,
            foreignKeys: [],
            withRelationships: false,
            withRules: false,
            withEvents: true
        );

        // Check that soft delete event methods are generated
        $this->assertStringContainsString('public function handleRestoring(): void', $modelContent);
        $this->assertStringContainsString('public function handleRestored(): void', $modelContent);
        
        // Check that boot method includes soft delete events
        $this->assertStringContainsString('static::restoring(function ($model) {', $modelContent);
        $this->assertStringContainsString('$model->handleRestoring();', $modelContent);
        
        // Check soft delete import and trait
        $this->assertStringContainsString('use Illuminate\Database\Eloquent\SoftDeletes;', $modelContent);
        $this->assertStringContainsString('use SoftDeletes;', $modelContent);
    }

    public function test_generates_model_without_events_when_disabled(): void
    {
        // Create a config with events disabled
        $configWithoutEvents = new GeneratorConfig([
            'model_properties' => [
                'generate_event_methods' => false,
                'generate_boot_method' => false,
            ]
        ]);
        
        $modelGenerator = new ModelGenerator($configWithoutEvents, new FileService());
        
        $columns = [
            (object) ['name' => 'id', 'type' => 'integer', 'primary' => true],
            (object) ['name' => 'name', 'type' => 'varchar', 'length' => 255],
        ];

        $modelContent = $modelGenerator->generate(
            modelName: 'User',
            tableName: 'users',
            connection: 'sqlite',
            columns: $columns,
            foreignKeys: [],
            withRelationships: false,
            withRules: false,
            withEvents: false
        );

        // Check that no event methods are generated
        $this->assertStringNotContainsString('public function handleCreating(): void', $modelContent);
        $this->assertStringNotContainsString('protected static function boot(): void', $modelContent);
    }
}