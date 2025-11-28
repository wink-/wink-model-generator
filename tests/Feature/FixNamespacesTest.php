<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class FixNamespacesTest extends TestCase
{
    private string $testDir;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test directory structure
        $this->testDir = $this->app->basePath('app/Models/GeneratedModels/sqlite');
        File::makeDirectory($this->testDir, 0755, true, true);
    }

    protected function tearDown(): void
    {
        // Cleanup test directories
        if (File::isDirectory($this->app->basePath('app/Models'))) {
            File::deleteDirectory($this->app->basePath('app/Models'));
        }
        if (File::isDirectory($this->app->basePath('app/Observers'))) {
            File::deleteDirectory($this->app->basePath('app/Observers'));
        }
        if (File::isDirectory($this->app->basePath('database/factories'))) {
            File::deleteDirectory($this->app->basePath('database/factories'));
        }

        parent::tearDown();
    }

    /** @test */
    public function it_fixes_model_namespaces_in_default_path(): void
    {
        // Create a model with incorrect namespace
        $modelContent = <<<'MODEL'
<?php

declare(strict_types=1);

namespace WrongNamespace;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $table = 'users';
}
MODEL;
        File::put($this->testDir.'/User.php', $modelContent);

        $this->artisan('wink:fix-namespaces', [
            'path' => $this->testDir,
            '--type' => 'models',
        ])
            ->expectsOutput('Scanning directory: '.$this->testDir)
            ->assertExitCode(0);

        // Verify the namespace was fixed
        $content = File::get($this->testDir.'/User.php');
        $this->assertStringContainsString(
            'namespace App\Models\GeneratedModels\Sqlite;',
            $content
        );
    }

    /** @test */
    public function it_handles_moved_directory_with_correct_namespace_inference(): void
    {
        // Create a custom directory structure (simulating moved files)
        $customDir = $this->app->basePath('app/Models/FooBar');
        File::makeDirectory($customDir, 0755, true, true);

        $modelContent = <<<'MODEL'
<?php

namespace OldNamespace;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $table = 'products';
}
MODEL;
        File::put($customDir.'/Product.php', $modelContent);

        $this->artisan('wink:fix-namespaces', [
            'path' => $customDir,
            '--type' => 'models',
        ])
            ->assertExitCode(0);

        // Verify the namespace was updated to match directory structure
        $content = File::get($customDir.'/Product.php');
        $this->assertStringContainsString(
            'namespace App\Models\FooBar;',
            $content
        );

        // Cleanup
        File::deleteDirectory($customDir);
    }

    /** @test */
    public function it_inserts_connection_segment_after_generated_namespace(): void
    {
        // Create a model without connection segment in its path
        $connectionDir = $this->app->basePath('app/Models/GeneratedModels');
        File::makeDirectory($connectionDir, 0755, true, true);

        $modelContent = <<<'MODEL'
<?php

namespace App\Models\GeneratedModels;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table = 'orders';
}
MODEL;
        File::put($connectionDir.'/Order.php', $modelContent);

        $this->artisan('wink:fix-namespaces', [
            'path' => $connectionDir,
            '--type' => 'models',
            '--connection' => 'mysql',
        ])
            ->assertExitCode(0);

        // Verify the connection segment was added
        $content = File::get($connectionDir.'/Order.php');
        $this->assertStringContainsString(
            'namespace App\Models\GeneratedModels\Mysql;',
            $content
        );
    }

    /** @test */
    public function it_does_not_write_changes_in_dry_run_mode(): void
    {
        $modelContent = <<<'MODEL'
<?php

namespace WrongNamespace;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $table = 'items';
}
MODEL;
        File::put($this->testDir.'/Item.php', $modelContent);

        $this->artisan('wink:fix-namespaces', [
            'path' => $this->testDir,
            '--type' => 'models',
            '--dry-run' => true,
        ])
            ->expectsOutput('Running in dry-run mode - no files will be modified')
            ->assertExitCode(0);

        // Verify the file was NOT modified
        $content = File::get($this->testDir.'/Item.php');
        $this->assertStringContainsString(
            'namespace WrongNamespace;',
            $content
        );
    }

    /** @test */
    public function it_handles_files_without_namespace(): void
    {
        $modelContent = <<<'MODEL'
<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;

class NoNamespaceModel extends Model
{
    protected $table = 'items';
}
MODEL;
        File::put($this->testDir.'/NoNamespaceModel.php', $modelContent);

        $this->artisan('wink:fix-namespaces', [
            'path' => $this->testDir,
            '--type' => 'models',
        ])
            ->assertExitCode(0);

        // Verify namespace was inserted
        $content = File::get($this->testDir.'/NoNamespaceModel.php');
        $this->assertStringContainsString(
            'namespace App\Models\GeneratedModels\Sqlite;',
            $content
        );
    }

    /** @test */
    public function it_handles_factory_files(): void
    {
        $factoryDir = $this->app->basePath('database/factories/GeneratedFactories/mysql');
        File::makeDirectory($factoryDir, 0755, true, true);

        $factoryContent = <<<'FACTORY'
<?php

namespace WrongFactoryNamespace;

use Illuminate\Database\Eloquent\Factories\Factory;

class UserFactory extends Factory
{
    public function definition(): array
    {
        return [];
    }
}
FACTORY;
        File::put($factoryDir.'/UserFactory.php', $factoryContent);

        $this->artisan('wink:fix-namespaces', [
            'path' => $factoryDir,
            '--type' => 'factories',
        ])
            ->assertExitCode(0);

        // Verify the namespace was fixed
        $content = File::get($factoryDir.'/UserFactory.php');
        $this->assertStringContainsString(
            'namespace Database\Factories\GeneratedFactories\Mysql;',
            $content
        );
    }

    /** @test */
    public function it_handles_observer_files(): void
    {
        $observerDir = $this->app->basePath('app/Observers/GeneratedObservers/testing');
        File::makeDirectory($observerDir, 0755, true, true);

        $observerContent = <<<'OBSERVER'
<?php

namespace WrongObserverNamespace;

class UserObserver
{
    public function created($model): void
    {
    }
}
OBSERVER;
        File::put($observerDir.'/UserObserver.php', $observerContent);

        $this->artisan('wink:fix-namespaces', [
            'path' => $observerDir,
            '--type' => 'observers',
        ])
            ->assertExitCode(0);

        // Verify the namespace was fixed
        $content = File::get($observerDir.'/UserObserver.php');
        $this->assertStringContainsString(
            'namespace App\Observers\GeneratedObservers\Testing;',
            $content
        );
    }

    /** @test */
    public function it_handles_invalid_type_option(): void
    {
        $this->artisan('wink:fix-namespaces', [
            '--type' => 'invalid',
        ])
            ->expectsOutput("Invalid type 'invalid'. Must be one of: models, factories, observers, any")
            ->assertExitCode(1);
    }

    /** @test */
    public function it_handles_nonexistent_directory(): void
    {
        $nonexistentDir = $this->app->basePath('nonexistent/path');

        $this->artisan('wink:fix-namespaces', [
            'path' => $nonexistentDir,
        ])
            ->expectsOutput("Directory not found: {$nonexistentDir}")
            ->assertExitCode(1);
    }

    /** @test */
    public function it_skips_files_with_correct_namespace(): void
    {
        $modelContent = <<<'MODEL'
<?php

declare(strict_types=1);

namespace App\Models\GeneratedModels\Sqlite;

use Illuminate\Database\Eloquent\Model;

class CorrectModel extends Model
{
    protected $table = 'correct';
}
MODEL;
        File::put($this->testDir.'/CorrectModel.php', $modelContent);

        $this->artisan('wink:fix-namespaces', [
            'path' => $this->testDir,
            '--type' => 'models',
            '--verbose' => true,
        ])
            ->expectsOutput('OK: '.$this->testDir.'/CorrectModel.php')
            ->assertExitCode(0);

        // Verify the file was not modified
        $content = File::get($this->testDir.'/CorrectModel.php');
        $this->assertStringContainsString(
            'namespace App\Models\GeneratedModels\Sqlite;',
            $content
        );
    }
}
