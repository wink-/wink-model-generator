<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ModelGeneratorTest extends TestCase
{
    protected $outputPath;

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

        // Set up output path
        $this->outputPath = __DIR__ . '/../../test-output/models';
        if (!File::isDirectory($this->outputPath)) {
            File::makeDirectory($this->outputPath, 0755, true);
        }
    }

    public function test_it_can_generate_model()
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

    protected function tearDown(): void
    {
        // Set database to writable mode for cleanup
        DB::connection('testing')->statement('PRAGMA query_only = 0');
        
        // Clean up
        if (File::isDirectory($this->outputPath)) {
            File::deleteDirectory($this->outputPath);
        }

        Schema::dropIfExists('users');

        parent::tearDown();
    }
}