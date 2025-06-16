<?php

declare(strict_types=1);

namespace Tests\Unit\Database;

use Orchestra\Testbench\TestCase;
use Wink\ModelGenerator\Database\MySqlSchemaReader;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use PDO;

class MySqlSchemaReaderTest extends TestCase
{
    private MySqlSchemaReader $reader;
    private string $connection = 'testing';

    protected function getEnvironmentSetUp($app)
    {
        // Setup default database to use mysql
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'port' => '3306',
            'database' => 'model_generator_test',
            'username' => 'root',
            'password' => 'root',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => 'InnoDB',
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Skip MySQL tests if MySQL connection is not available
        try {
            $pdo = new PDO('mysql:host=127.0.0.1;port=3306', 'root', 'root', [
                PDO::ATTR_TIMEOUT => 2,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
        } catch (\PDOException $e) {
            $this->markTestSkipped('MySQL server is not available: ' . $e->getMessage());
        }

        $this->reader = new MySqlSchemaReader();

        // Create database if it doesn't exist
        $pdo->exec('DROP DATABASE IF EXISTS model_generator_test');
        $pdo->exec('CREATE DATABASE model_generator_test');

        // Create test tables
        $this->createTestTables();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    private function createTestTables(): void
    {
        DB::connection($this->connection)->unprepared('SET TRANSACTION READ WRITE');
        
        // Drop all tables first
        Schema::connection($this->connection)->dropIfExists('comments');
        Schema::connection($this->connection)->dropIfExists('post_tags');
        Schema::connection($this->connection)->dropIfExists('posts');
        Schema::connection($this->connection)->dropIfExists('tags');
        Schema::connection($this->connection)->dropIfExists('users');
        Schema::connection($this->connection)->dropIfExists('categories');

        Schema::connection($this->connection)->create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('email')->unique();
            $table->string('password');
            $table->boolean('is_active')->default(true);
            $table->enum('role', ['admin', 'user', 'guest'])->default('user');
            $table->json('settings')->nullable();
            $table->decimal('balance', 10, 2)->default(0.00);
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::connection($this->connection)->create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->string('slug', 60)->unique();
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('display_order')->default(0);
            $table->timestamps();
        });

        Schema::connection($this->connection)->create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name', 30)->unique();
            $table->timestamps();
        });

        Schema::connection($this->connection)->create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->string('title', 200);
            $table->text('content');
            $table->string('status', 20)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->fullText(['title', 'content']);
        });

        Schema::connection($this->connection)->create('post_tags', function (Blueprint $table) {
            $table->foreignId('post_id')->constrained()->onDelete('cascade');
            $table->foreignId('tag_id')->constrained()->onDelete('cascade');
            $table->primary(['post_id', 'tag_id']);
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
        
        $this->assertCount(6, $tableNames);
        $this->assertContains('users', $tableNames);
        $this->assertContains('categories', $tableNames);
        $this->assertContains('tags', $tableNames);
        $this->assertContains('posts', $tableNames);
        $this->assertContains('post_tags', $tableNames);
        $this->assertContains('comments', $tableNames);
    }

    public function testGetTableColumns(): void
    {
        // Test users table columns
        $columns = $this->reader->getTableColumns($this->connection, 'users');

        $columnNames = array_map(fn($column) => $column->name, $columns);
        
        $this->assertCount(12, $columnNames);
        $this->assertContains('id', $columnNames);
        $this->assertContains('name', $columnNames);
        $this->assertContains('email', $columnNames);
        $this->assertContains('password', $columnNames);
        $this->assertContains('is_active', $columnNames);
        $this->assertContains('role', $columnNames);
        $this->assertContains('settings', $columnNames);
        $this->assertContains('balance', $columnNames);
        $this->assertContains('last_login_at', $columnNames);
        $this->assertContains('created_at', $columnNames);
        $this->assertContains('updated_at', $columnNames);
        $this->assertContains('deleted_at', $columnNames);
    }

    public function testGetForeignKeys(): void
    {
        // Test comments table foreign keys
        $foreignKeys = $this->reader->getForeignKeys($this->connection, 'comments');

        $this->assertCount(2, $foreignKeys);

        // Check post_id foreign key
        $postIdFk = array_values(array_filter($foreignKeys, fn($fk) => $fk->from === 'post_id'))[0] ?? null;
        $this->assertNotNull($postIdFk);
        $this->assertEquals('posts', $postIdFk->table);
        $this->assertEquals('id', $postIdFk->to);
        $this->assertEquals('CASCADE', $postIdFk->on_delete);

        // Check user_id foreign key
        $userIdFk = array_values(array_filter($foreignKeys, fn($fk) => $fk->from === 'user_id'))[0] ?? null;
        $this->assertNotNull($userIdFk);
        $this->assertEquals('users', $userIdFk->table);
        $this->assertEquals('id', $userIdFk->to);
        $this->assertEquals('CASCADE', $userIdFk->on_delete);
    }

    public function testGetTableIndexes(): void
    {
        // Test users table indexes
        $indexes = $this->reader->getTableIndexes($this->connection, 'users');

        // Should have PRIMARY and email UNIQUE indexes
        $this->assertCount(2, $indexes);

        // Check primary key
        $primaryKey = array_values(array_filter($indexes, fn($idx) => $idx->type === 'PRIMARY'))[0] ?? null;
        $this->assertNotNull($primaryKey);
        $this->assertEquals(['id'], $primaryKey->columns);

        // Check email unique index
        $emailIndex = array_values(array_filter($indexes, fn($idx) => $idx->type === 'UNIQUE'))[0] ?? null;
        $this->assertNotNull($emailIndex);
        $this->assertEquals(['email'], $emailIndex->columns);
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

    public function testTableWithoutIndexes(): void
    {
        $indexes = $this->reader->getTableIndexes($this->connection, 'categories');
        $this->assertCount(2, $indexes); // PRIMARY key and unique slug index
    }
}
