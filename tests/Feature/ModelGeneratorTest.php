<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ModelGeneratorTest extends TestCase
{
    protected $outputPath;
    protected $defaultOutputPath;
    protected $factoryOutputPath;
    protected $defaultFactoryOutputPath;

    protected function setUp(): void
    {
        parent::setUp();

        // Set database to writable mode for setup
        DB::connection('testing')->statement('PRAGMA query_only = 0');

        // Create test table
        Schema::create('users', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamps();
        });

        // Set up output paths
        $this->outputPath = __DIR__ . '/../../test-output/models';
        $this->defaultOutputPath = app_path('Models/GeneratedModels/testing');
        $this->factoryOutputPath = __DIR__ . '/../../test-output/factories';
        $this->defaultFactoryOutputPath = database_path('factories/GeneratedFactories/testing');
        
        // Create directories if they don't exist
        foreach ([
            $this->outputPath, 
            $this->defaultOutputPath,
            $this->factoryOutputPath,
            $this->defaultFactoryOutputPath
        ] as $path) {
            if (!File::isDirectory($path)) {
                File::makeDirectory($path, 0755, true);
            }
        }
    }

    public function test_it_can_generate_model_in_custom_directory()
    {
        $this->artisan('wink:generate-models', [
            '--connection' => 'testing',
            '--directory' => $this->outputPath,
        ])->assertSuccessful();

        $modelPath = $this->outputPath . '/User.php';
        $this->assertFileExists($modelPath);
        
        $content = file_get_contents($modelPath);
        $this->assertStringContainsString('class User extends Model', $content);
        $this->assertStringContainsString('protected $fillable = [', $content);
        $this->assertStringContainsString("'name'", $content);
        $this->assertStringContainsString("'email'", $content);
    }

    public function test_it_uses_connection_based_directory_by_default()
    {
        $this->artisan('wink:generate-models', [
            '--connection' => 'testing',
        ])->assertSuccessful();

        $modelPath = $this->defaultOutputPath . '/User.php';
        $this->assertFileExists($modelPath);
        
        $content = file_get_contents($modelPath);
        $this->assertStringContainsString('class User extends Model', $content);
        $this->assertStringContainsString('protected $fillable = [', $content);
        $this->assertStringContainsString("'name'", $content);
        $this->assertStringContainsString("'email'", $content);
    }

    public function test_it_can_generate_factory_in_custom_directory()
    {
        $this->artisan('wink:generate-models', [
            '--connection' => 'testing',
            '--with-factories' => true,
            '--factory-directory' => $this->factoryOutputPath,
        ])->assertSuccessful();

        $factoryPath = $this->factoryOutputPath . '/UserFactory.php';
        $this->assertFileExists($factoryPath);
        
        $content = file_get_contents($factoryPath);
        $this->assertStringContainsString('class UserFactory extends Factory', $content);
        $this->assertStringContainsString("'name' => fake()->", $content);
        $this->assertStringContainsString("'email' => fake()->", $content);
    }

    public function test_it_uses_connection_based_factory_directory_by_default()
    {
        $this->artisan('wink:generate-models', [
            '--connection' => 'testing',
            '--with-factories' => true,
        ])->assertSuccessful();

        $factoryPath = $this->defaultFactoryOutputPath . '/UserFactory.php';
        $this->assertFileExists($factoryPath);
        
        $content = file_get_contents($factoryPath);
        $this->assertStringContainsString('class UserFactory extends Factory', $content);
        $this->assertStringContainsString("'name' => fake()->", $content);
        $this->assertStringContainsString("'email' => fake()->", $content);
    }

    protected function tearDown(): void
    {
        // Set database to writable mode for cleanup
        DB::connection('testing')->statement('PRAGMA query_only = 0');
        
        // Clean up
        foreach ([
            $this->outputPath, 
            $this->defaultOutputPath,
            $this->factoryOutputPath,
            $this->defaultFactoryOutputPath
        ] as $path) {
            if (File::isDirectory($path)) {
                File::deleteDirectory($path);
            }
        }

        Schema::dropIfExists('users');

        parent::tearDown();
    }
}