<?php

declare(strict_types=1);

namespace Tests\Unit\Generators;

use Orchestra\Testbench\TestCase;
use Wink\ModelGenerator\Config\GeneratorConfig;
use Wink\ModelGenerator\Generators\FactoryGenerator;
use Wink\ModelGenerator\ModelGeneratorServiceProvider;
use Illuminate\Support\Facades\Config;
use stdClass;

class FactoryGeneratorTest extends TestCase
{
    private FactoryGenerator $generator;
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
        $this->generator = new FactoryGenerator($this->config);

        // Create the templates directory and factory.stub file
        $templateDir = __DIR__ . '/../../../src/Templates';
        if (!file_exists($templateDir)) {
            mkdir($templateDir, 0777, true);
        }

        // Copy the factory.stub template
        copy(
            __DIR__ . '/../../../src/Templates/factory.stub',
            $templateDir . '/factory.stub'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            ModelGeneratorServiceProvider::class,
        ];
    }

    public function testGenerateBasicFactory(): void
    {
        // Prepare test data
        $columns = [
            $this->createColumn('id', 'integer'),
            $this->createColumn('name', 'string'),
            $this->createColumn('email', 'string'),
            $this->createColumn('created_at', 'timestamp'),
            $this->createColumn('updated_at', 'timestamp'),
        ];

        $factoryContent = $this->generator->generate('User', $columns);

        // Assert basic factory structure
        $this->assertStringContainsString('namespace Database\\Factories\\Generated;', $factoryContent);
        $this->assertStringContainsString('class UserFactory extends Factory', $factoryContent);
        $this->assertStringContainsString('protected $model = User::class;', $factoryContent);
        
        // Assert faker methods
        $this->assertStringContainsString("'name' => fake()->name()", $factoryContent);
        $this->assertStringContainsString("'email' => fake()->safeEmail()", $factoryContent);
        
        // Assert timestamps are excluded
        $this->assertStringNotContainsString('created_at', $factoryContent);
        $this->assertStringNotContainsString('updated_at', $factoryContent);
    }

    public function testGenerateFactoryWithCommonColumnTypes(): void
    {
        $columns = [
            $this->createColumn('phone_number', 'string'),
            $this->createColumn('address', 'string'),
            $this->createColumn('city', 'string'),
            $this->createColumn('country', 'string'),
            $this->createColumn('zip_code', 'string'),
            $this->createColumn('password', 'string'),
            $this->createColumn('website_url', 'string'),
            $this->createColumn('description', 'text'),
            $this->createColumn('title', 'string'),
        ];

        $factoryContent = $this->generator->generate('User', $columns);

        // Assert correct Faker methods are used based on column names
        $this->assertStringContainsString("'phone_number' => fake()->phoneNumber()", $factoryContent);
        $this->assertStringContainsString("'address' => fake()->address()", $factoryContent);
        $this->assertStringContainsString("'city' => fake()->city()", $factoryContent);
        $this->assertStringContainsString("'country' => fake()->country()", $factoryContent);
        $this->assertStringContainsString("'zip_code' => fake()->postcode()", $factoryContent);
        $this->assertStringContainsString("'password' => fake()->password()", $factoryContent);
        $this->assertStringContainsString("'website_url' => fake()->url()", $factoryContent);
        $this->assertStringContainsString("'description' => fake()->text()", $factoryContent);
        $this->assertStringContainsString("'title' => fake()->sentence()", $factoryContent);
    }

    public function testGenerateFactoryWithDataTypes(): void
    {
        $columns = [
            $this->createColumn('amount', 'integer'),
            $this->createColumn('price', 'decimal'),
            $this->createColumn('is_active', 'boolean'),
            $this->createColumn('birth_date', 'date'),
            $this->createColumn('last_login', 'datetime'),
            $this->createColumn('settings', 'json'),
        ];

        $factoryContent = $this->generator->generate('Product', $columns);

        // Assert correct Faker methods are used based on data types
        $this->assertStringContainsString("'amount' => fake()->randomNumber()", $factoryContent);
        $this->assertStringContainsString("'price' => fake()->randomFloat()", $factoryContent);
        $this->assertStringContainsString("'is_active' => fake()->boolean()", $factoryContent);
        $this->assertStringContainsString("'birth_date' => fake()->date()", $factoryContent);
        $this->assertStringContainsString("'last_login' => fake()->dateTime()", $factoryContent);
        $this->assertStringContainsString("'settings' => fake()->words(3, true)", $factoryContent);
    }

    private function createColumn(string $name, string $type): stdClass
    {
        $column = new stdClass();
        $column->name = $name;
        $column->type = $type;
        return $column;
    }
}
