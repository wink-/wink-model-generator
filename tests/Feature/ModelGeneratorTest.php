<?php

namespace Wink\ModelGenerator\Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Wink\ModelGenerator\Tests\TestCase;

class ModelGeneratorTest extends TestCase
{
    protected $outputPath;

    protected function setUp(): void
    {
        parent::setUp();

        // Set output path to a test directory within the package
        $this->outputPath = __DIR__ . '/../../test-output/Models/Test';

        // Create a test SQLite database
        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite.database' => ':memory:']);

        // Create a test table
        Schema::create('users', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamps();
        });

        // Ensure the output directory exists and is writable
        if (!file_exists($this->outputPath)) {
            mkdir($this->outputPath, 0755, true);
        }
    }

    /** @test */
    public function it_can_run_a_basic_test()
    {
        $this->assertTrue(true);
    }

    /** @test */
    public function it_can_generate_model_from_sqlite_table()
    {
        // Verify the table exists
        $this->assertTrue(Schema::hasTable('users'), 'Users table does not exist');

        // Run the command with verbose output for debugging
        $this->artisan('app:generate-models', [
            '--connection' => 'sqlite',
            '--directory' => $this->outputPath,
            '--verbose' => true
        ])
        ->expectsOutput('Generating models...')
        ->assertSuccessful();

        // Assert the model file was created in the correct directory
        $this->assertDirectoryExists($this->outputPath);
        $this->assertFileExists($this->outputPath . '/User.php');
    }

    protected function tearDown(): void
    {
        // Clean up the test table
        Schema::dropIfExists('users');

        // Clean up generated files
        if (file_exists($this->outputPath)) {
            array_map('unlink', glob($this->outputPath . '/*.*'));
            rmdir($this->outputPath);
            
            // Remove parent directories if empty
            $parentDir = dirname($this->outputPath);
            while ($parentDir !== dirname(__DIR__ . '/../../test-output')) {
                @rmdir($parentDir);
                $parentDir = dirname($parentDir);
            }
            @rmdir(__DIR__ . '/../../test-output');
        }

        parent::tearDown();
    }
} 