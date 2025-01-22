<?php

declare(strict_types=1);

namespace Tests\Unit\Database;

use Orchestra\Testbench\TestCase;
use Wink\ModelGenerator\Database\SqliteSchemaReader;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class SqliteSchemaReaderTest extends TestCase
{
    private SqliteSchemaReader $reader;
    private string $connection = 'testing';

    protected function setUp(): void
    {
        parent::setUp();

        // Configure the testing database connection
        config(['database.connections.testing' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]]);

        $this->reader = new SqliteSchemaReader();

        // Create test tables
        $this->createTestTables();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    private function createTestTables(): void
    {
        DB::connection($this->connection)->statement('PRAGMA query_only = 0');

        // Drop all tables first
        Schema::connection($this->connection)->dropIfExists('comments');
        Schema::connection($this->connection)->dropIfExists('posts');
        Schema::connection($this->connection)->dropIfExists('users');
        Schema::connection($this->connection)->dropIfExists('categories');

        Schema::connection($this->connection)->create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        Schema::connection($this->connection)->create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::connection($this->connection)->create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('content');
            $table->string('status')->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::connection($this->connection)->create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('content');
            $table->timestamps();
        });
    }

    public function testGetTables(): void
    {
        $tables = $this->reader->getTables($this->connection, []);

        $tableNames = array_map(fn($table) => $table->name, $tables);
        
        $this->assertCount(4, $tableNames);
        $this->assertContains('users', $tableNames);
        $this->assertContains('categories', $tableNames);
        $this->assertContains('posts', $tableNames);
        $this->assertContains('comments', $tableNames);
    }

    public function testGetTableColumns(): void
    {
        $columns = $this->reader->getTableColumns($this->connection, 'users');
        
        $columnNames = array_map(fn($column) => $column->name, $columns);
        
        $this->assertCount(8, $columnNames);
        $this->assertContains('id', $columnNames);
        $this->assertContains('name', $columnNames);
        $this->assertContains('email', $columnNames);
        $this->assertContains('password', $columnNames);
        $this->assertContains('is_active', $columnNames);
        $this->assertContains('settings', $columnNames);
        $this->assertContains('created_at', $columnNames);
        $this->assertContains('updated_at', $columnNames);
    }

    public function testGetForeignKeys(): void
    {
        $foreignKeys = $this->reader->getForeignKeys($this->connection, 'comments');
        
        $this->assertCount(2, $foreignKeys);
        
        // Check post_id foreign key
        $postIdFk = array_values(array_filter($foreignKeys, fn($fk) => $fk->from === 'post_id'))[0] ?? null;
        $this->assertNotNull($postIdFk);
        $this->assertEquals('posts', $postIdFk->table);
        $this->assertEquals('id', $postIdFk->to);
        
        // Check user_id foreign key
        $userIdFk = array_values(array_filter($foreignKeys, fn($fk) => $fk->from === 'user_id'))[0] ?? null;
        $this->assertNotNull($userIdFk);
        $this->assertEquals('users', $userIdFk->table);
        $this->assertEquals('id', $userIdFk->to);
    }

    public function testNonExistentTable(): void
    {
        $columns = $this->reader->getTableColumns($this->connection, 'non_existent_table');
        $this->assertEmpty($columns);
    }

    public function testTableWithoutForeignKeys(): void
    {
        $foreignKeys = $this->reader->getForeignKeys($this->connection, 'categories');
        $this->assertEmpty($foreignKeys);
    }
}
