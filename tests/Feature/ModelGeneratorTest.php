<?php

namespace Wink\ModelGenerator\Tests\Feature;

use Illuminate\Support\Facades\Schema;
use Wink\ModelGenerator\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ModelGeneratorTest extends TestCase
{
    protected $outputPath;

    protected function setUp(): void
    {
        parent::setUp();

        // Set output path to a test directory within the package
        $this->outputPath = __DIR__ . '/../../test-output/Models/Test';

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

    #[Test]
    public function it_can_generate_model_from_sqlite_table()
    {
        $this->assertTrue(Schema::hasTable('users'), 'Users table does not exist');

        $this->artisan('wink:generate-models', [
            '--connection' => 'testing',
            '--directory' => $this->outputPath,
        ])
        ->assertSuccessful();

        $this->assertFileExists($this->outputPath . '/User.php');
        
        // Verify the content of the generated file
        $content = file_get_contents($this->outputPath . '/User.php');
        $this->assertStringContainsString('class User extends Model', $content);
        $this->assertStringContainsString('protected $connection = \'testing\'', $content);
    }

    protected function tearDown(): void
    {
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