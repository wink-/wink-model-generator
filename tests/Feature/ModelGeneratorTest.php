<?php

namespace Wink\ModelGenerator\Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Wink\ModelGenerator\Tests\TestCase;

class ModelGeneratorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

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
    }

    /** @test */
    public function it_can_run_a_basic_test()
    {
        $this->assertTrue(true);
    }

    /** @test */
    public function it_can_generate_model_from_sqlite_table()
    {
        // Use the correct command name from your GenerateModels class
        $this->artisan('app:generate-models', [
            '--connection' => 'sqlite',
            '--directory' => 'Test'
        ])->assertSuccessful();

        // Assert the model file was created in the correct directory
        $this->assertDirectoryExists(app_path('Models/GeneratedModels/Test'));
    }

    protected function tearDown(): void
    {
        // Clean up the test table
        Schema::dropIfExists('users');
        parent::tearDown();
    }
} 