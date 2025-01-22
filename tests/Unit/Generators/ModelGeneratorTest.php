<?php

declare(strict_types=1);

namespace Tests\Unit\Generators;

use Orchestra\Testbench\TestCase;
use Wink\ModelGenerator\Config\GeneratorConfig;
use Wink\ModelGenerator\Generators\ModelGenerator;
use Wink\ModelGenerator\ModelGeneratorServiceProvider;
use Illuminate\Support\Facades\Config;
use stdClass;

class ModelGeneratorTest extends TestCase
{
    private ModelGenerator $generator;
    private GeneratorConfig $config;

    protected function setUp(): void
    {
        parent::setUp();

        // Set up config values
        Config::set('model-generator.model_namespace', 'App\\Models\\Generated');
        Config::set('model-generator.factory_namespace', 'Database\\Factories\\Generated');
        Config::set('model-generator.model_path', '/path/to/models');
        Config::set('model-generator.factory_path', '/path/to/factories');

        $this->config = new GeneratorConfig();
        $this->generator = new ModelGenerator($this->config);

        // Create the templates directory and model.stub file
        $templateDir = __DIR__ . '/../../../src/Templates';
        if (!file_exists($templateDir)) {
            mkdir($templateDir, 0777, true);
        }

        // Copy the model.stub template
        copy(
            __DIR__ . '/../../../src/Templates/model.stub',
            $templateDir . '/model.stub'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            ModelGeneratorServiceProvider::class,
        ];
    }

    public function testGenerateBasicModel(): void
    {
        // Prepare test data
        $columns = [
            $this->createColumn('id', 'integer'),
            $this->createColumn('name', 'string'),
            $this->createColumn('email', 'string'),
            $this->createColumn('created_at', 'timestamp'),
            $this->createColumn('updated_at', 'timestamp'),
        ];

        $modelContent = $this->generator->generate(
            'User',
            'users',
            'mysql',
            $columns,
            [],
            false,
            false
        );

        // Assert basic model structure
        $this->assertStringContainsString('namespace App\\Models\\Generated;', $modelContent);
        $this->assertStringContainsString('class User extends Model', $modelContent);
        $this->assertStringContainsString('protected $table = \'users\';', $modelContent);
        $this->assertStringContainsString('protected $connection = \'mysql\';', $modelContent);
        
        // Assert fillable attributes
        $this->assertStringContainsString("'name'", $modelContent);
        $this->assertStringContainsString("'email'", $modelContent);
        
        // Assert timestamps
        $this->assertStringContainsString('public $timestamps = true;', $modelContent);
    }

    public function testGenerateModelWithRelationships(): void
    {
        // Prepare test data
        $columns = [
            $this->createColumn('id', 'integer'),
            $this->createColumn('post_id', 'integer'),
            $this->createColumn('content', 'text'),
        ];

        $foreignKeys = [
            $this->createForeignKey('post_id', 'posts', 'id'),
        ];

        $modelContent = $this->generator->generate(
            'Comment',
            'comments',
            'mysql',
            $columns,
            $foreignKeys,
            true,
            false
        );

        // Assert relationship method is generated
        $this->assertStringContainsString('public function id()', $modelContent);
        $this->assertStringContainsString('return $this->belongsTo(\\App\\Models\\Post::class,', $modelContent);
    }

    public function testGenerateModelWithValidationRules(): void
    {
        // Prepare test data
        $columns = [
            $this->createColumn('id', 'integer'),
            $this->createColumn('email', 'string', false), // not null
            $this->createColumn('age', 'integer', true),   // nullable
        ];

        $modelContent = $this->generator->generate(
            'User',
            'users',
            'mysql',
            $columns,
            [],
            false,
            true
        );

        // Assert validation rules are generated
        $this->assertStringContainsString("'email' => ['required'", $modelContent);
        $this->assertStringContainsString("'age' => ['integer'", $modelContent);
    }

    private function createColumn(string $name, string $type, bool $nullable = true): stdClass
    {
        $column = new stdClass();
        $column->name = $name;
        $column->type = $type;
        $column->notnull = !$nullable;
        return $column;
    }

    private function createForeignKey(string $from, string $table, string $to): stdClass
    {
        $fk = new stdClass();
        $fk->from = $from;
        $fk->table = $table;
        $fk->to = $to;
        return $fk;
    }
}
