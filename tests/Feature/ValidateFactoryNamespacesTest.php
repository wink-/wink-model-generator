<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\File;

class ValidateFactoryNamespacesTest extends TestCase
{
    private string $testFactoryDir;
    private string $validFactory = <<<'FACTORY'
<?php

namespace Database\Factories\Admin;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Admin\Inventory;

class InventoryFactory extends Factory
{
    protected $model = Inventory::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->word,
            'quantity' => $this->faker->numberBetween(0, 100),
        ];
    }
}
FACTORY;

    private string $invalidFactory = <<<'FACTORY'
<?php

namespace Database\Factories\GeneratedFactories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Admin\Inventory;

class InventoryFactory extends Factory
{
    protected $model = Inventory::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->word,
            'quantity' => $this->faker->numberBetween(0, 100),
        ];
    }
}
FACTORY;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test directory structure
        $this->testFactoryDir = $this->app->basePath('database/factories/Admin');
        File::makeDirectory($this->testFactoryDir, 0755, true, true);
    }

    protected function tearDown(): void
    {
        // Cleanup test directories
        if (File::isDirectory($this->app->basePath('database/factories'))) {
            File::deleteDirectory($this->app->basePath('database/factories'));
        }

        parent::tearDown();
    }

    /** @test */
    public function it_detects_correct_factory_namespaces()
    {
        // Create a factory with correct namespace
        File::put($this->testFactoryDir . '/InventoryFactory.php', $this->validFactory);

        $this->artisan('wink:validate-factory-namespaces')
            ->expectsOutput('All factory namespaces are correct!')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_detects_incorrect_factory_namespaces()
    {
        // Create a factory with incorrect namespace
        File::put($this->testFactoryDir . '/InventoryFactory.php', $this->invalidFactory);

        $this->artisan('wink:validate-factory-namespaces')
            ->expectsOutput('Namespace mismatch in: ' . str_replace('\\', '/', $this->testFactoryDir . '/InventoryFactory.php'))
            ->expectsOutput('Current:  Database\Factories\GeneratedFactories')
            ->expectsOutput('Expected: Database\Factories\Admin')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_fixes_incorrect_factory_namespaces_when_fix_option_is_used()
    {
        // Create a factory with incorrect namespace
        $factoryPath = $this->testFactoryDir . '/InventoryFactory.php';
        File::put($factoryPath, $this->invalidFactory);

        $this->artisan('wink:validate-factory-namespaces --fix')
            ->expectsOutput('Fixed namespace in: ' . str_replace('\\', '/', $factoryPath))
            ->assertExitCode(0);

        // Verify the namespace was fixed
        $this->assertStringContainsString(
            'namespace Database\Factories\Admin;',
            File::get($factoryPath)
        );
    }

    /** @test */
    public function it_creates_backup_before_fixing_factory_namespace()
    {
        // Create a factory with incorrect namespace
        $factoryPath = $this->testFactoryDir . '/InventoryFactory.php';
        File::put($factoryPath, $this->invalidFactory);

        $this->artisan('wink:validate-factory-namespaces --fix');

        // Verify backup file exists and contains original content
        $this->assertTrue(File::exists($factoryPath . '.bak'));
        $this->assertEquals(
            $this->invalidFactory,
            File::get($factoryPath . '.bak')
        );
    }

    /** @test */
    public function it_handles_invalid_directory_gracefully()
    {
        $nonexistentDir = database_path('NonexistentFactories');

        $this->artisan('wink:validate-factory-namespaces', [
            '--directory' => $nonexistentDir
        ])
            ->expectsOutput('Error: Directory not found: ' . $nonexistentDir)
            ->assertExitCode(1);
    }
}
