<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\File;

class ValidateModelNamespacesTest extends TestCase
{
    private string $testModelDir;
    private string $validModel = <<<'MODEL'
<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    protected $table = 'inventory';
}
MODEL;

    private string $invalidModel = <<<'MODEL'
<?php

namespace App\Models\Users;

use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    protected $table = 'inventory';
}
MODEL;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test directory structure
        $this->testModelDir = $this->app->basePath('app/Models/Admin');
        File::makeDirectory($this->testModelDir, 0755, true, true);
    }

    protected function tearDown(): void
    {
        // Cleanup test directories
        if (File::isDirectory($this->app->basePath('app/Models'))) {
            File::deleteDirectory($this->app->basePath('app/Models'));
        }

        parent::tearDown();
    }

    /** @test */
    public function it_detects_correct_namespaces()
    {
        // Create a model with correct namespace
        File::put($this->testModelDir . '/Inventory.php', $this->validModel);

        $this->artisan('wink:validate-model-namespaces')
            ->expectsOutput('All model namespaces are correct!')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_detects_incorrect_namespaces()
    {
        // Create a model with incorrect namespace
        File::put($this->testModelDir . '/Inventory.php', $this->invalidModel);

        $this->artisan('wink:validate-model-namespaces')
            ->expectsOutput('Namespace mismatch in: ' . str_replace('\\', '/', $this->testModelDir . '/Inventory.php'))
            ->expectsOutput('Current:  App\Models\Users')
            ->expectsOutput('Expected: App\Models\Admin')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_fixes_incorrect_namespaces_when_fix_option_is_used()
    {
        // Create a model with incorrect namespace
        $modelPath = $this->testModelDir . '/Inventory.php';
        File::put($modelPath, $this->invalidModel);

        $this->artisan('wink:validate-model-namespaces --fix')
            ->expectsOutput('Fixed namespace in: ' . str_replace('\\', '/', $modelPath))
            ->assertExitCode(0);

        // Verify the namespace was fixed
        $this->assertStringContainsString(
            'namespace App\Models\Admin;',
            File::get($modelPath)
        );
    }

    /** @test */
    public function it_creates_backup_before_fixing_namespace()
    {
        // Create a model with incorrect namespace
        $modelPath = $this->testModelDir . '/Inventory.php';
        File::put($modelPath, $this->invalidModel);

        $this->artisan('wink:validate-model-namespaces --fix');

        // Verify backup file exists and contains original content
        $this->assertTrue(File::exists($modelPath . '.bak'));
        $this->assertEquals(
            $this->invalidModel,
            File::get($modelPath . '.bak')
        );
    }

    /** @test */
    public function it_handles_invalid_directory_gracefully()
    {
        $nonexistentDir = app_path('NonexistentModels');

        $this->artisan('wink:validate-model-namespaces', [
            '--directory' => $nonexistentDir
        ])
            ->expectsOutput('Error: Directory not found: ' . $nonexistentDir)
            ->assertExitCode(1);
    }
}
