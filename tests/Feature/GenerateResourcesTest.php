<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;

class GenerateResourcesTest extends TestCase
{
    private string $testModelDir;
    private string $testResourceDir;
    protected $testModel = <<<'MODEL'
<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Inventory extends Model
{
    protected $table = "inventory";
    protected $connection = "testing";

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }
}
MODEL;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup test paths
        $this->testModelDir = __DIR__ . '/../../test-output/app/Models/Admin';
        $this->testResourceDir = __DIR__ . '/../../test-output/app/Http/Resources';

        // Clean up test directories
        $this->cleanupTestDirectories();

        // Create test directories
        File::makeDirectory($this->testModelDir, 0755, true);
        File::makeDirectory($this->testResourceDir, 0755, true);

        // Create all model files
        File::put($this->testModelDir . '/Inventory.php', $this->testModel);
        File::put($this->testModelDir . '/Category.php', $this->getCategoryModel());
        File::put($this->testModelDir . '/Item.php', $this->getItemModel());

        // Register the app directory in composer's autoloader
        $composerAutoloader = require __DIR__ . '/../../vendor/autoload.php';
        $composerAutoloader->addPsr4('App\\Models\\Admin\\', $this->testModelDir);

        // Configure the resource path
        config(['model-generator.resource_path' => $this->testResourceDir]);

        // Setup database
        $this->setupDatabase();
    }

    protected function cleanupTestDirectories(): void
    {
        // Clean up test directories
        $testOutput = __DIR__ . '/../../test-output';
        if (File::exists($testOutput)) {
            File::deleteDirectory($testOutput);
        }
        File::makeDirectory($testOutput);
    }

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        // Use an in-memory SQLite database
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'foreign_key_constraints' => true,
            'prefix' => '',
        ]);

        // Create the database connection
        $app['db']->purge();
        $app['db']->reconnect('testing');
    }

    protected function setupDatabase()
    {
        // Create tables in the correct order
        Schema::connection('testing')->create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::connection('testing')->create('inventory', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('category_id')->constrained('categories');
            $table->timestamps();
        });

        Schema::connection('testing')->create('items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('inventory_id')->constrained('inventory');
            $table->timestamps();
        });

        // Create some test data
        DB::connection('testing')->table('categories')->insert([
            'name' => 'Test Category',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::connection('testing')->table('inventory')->insert([
            'name' => 'Test Inventory',
            'description' => 'Test Description',
            'category_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::connection('testing')->table('items')->insert([
            'name' => 'Test Item',
            'inventory_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function tearDown(): void
    {
        // Clean up test directories
        $this->cleanupTestDirectories();

        parent::tearDown();
    }

    protected function defineEnvironment($app): void
    {
        // Configure database
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    private function getCategoryModel(): string
    {
        return <<<'MODEL'
<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $table = "categories";
    protected $connection = "testing";

    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }
}
MODEL;
    }

    private function getItemModel(): string
    {
        return <<<'MODEL'
<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Item extends Model
{
    protected $table = "items";
    protected $connection = "testing";

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class);
    }
}
MODEL;
    }

    protected function assertResourceExists(string $resourcePath, array $expectedContents = []): void
    {
        $this->assertTrue(
            File::exists($resourcePath),
            "Resource file does not exist: $resourcePath"
        );

        if (!empty($expectedContents)) {
            $content = File::get($resourcePath);
            foreach ($expectedContents as $expected) {
                $this->assertStringContainsString(
                    $expected,
                    $content,
                    "Resource file missing expected content: $expected"
                );
            }
        }
    }

    /** @test */
    public function it_generates_resource_for_specific_model()
    {
        // Create model files
        File::put($this->testModelDir . '/Inventory.php', $this->testModel);
        File::put($this->testModelDir . '/Category.php', $this->getCategoryModel());
        File::put($this->testModelDir . '/Item.php', $this->getItemModel());

        // Register the command in the testing environment
        $this->artisan('wink:generate-resources', [
                '--model' => 'Admin/Inventory',
                '--output' => $this->testResourceDir,
            ])
            ->expectsOutput('Starting resource generation...')
            ->expectsOutput('Model: Admin/Inventory')
            ->expectsOutput('Directory: None specified')
            ->expectsOutput('Generate Collection: No')
            ->expectsOutput('Output Directory: ' . $this->testResourceDir)
            ->assertSuccessful();

        // Verify the resource was created
        $this->assertFileExists($this->testResourceDir . '/InventoryResource.php');

        // Verify the resource contents
        $resourceContents = File::get($this->testResourceDir . '/InventoryResource.php');
        $this->assertStringContainsString('class InventoryResource extends JsonResource', $resourceContents);
        $this->assertStringContainsString("'id' => \$this->id", $resourceContents);
        $this->assertStringContainsString("'category' => new CategoryResource(\$this->whenLoaded('category'))", $resourceContents);
        $this->assertStringContainsString("'items' => ItemResource::collection(\$this->whenLoaded('items'))", $resourceContents);
    }

    /** @test */
    public function it_generates_collection_resource_when_option_is_used()
    {
        $this->artisan('wink:generate-resources', [
            '--model' => 'Admin/Inventory',
            '--collection' => true
        ])->assertSuccessful();

        $this->assertResourceExists(
            $this->testResourceDir . '/InventoryResource.php',
            [
                'namespace App\Http\Resources;',
                'class InventoryResource extends JsonResource',
                'use App\Models\Admin\Inventory;'
            ]
        );

        $this->assertResourceExists(
            $this->testResourceDir . '/InventoryCollection.php',
            [
                'namespace App\Http\Resources;',
                'class InventoryCollection extends ResourceCollection',
                'use App\Models\Admin\Inventory;',
                "'data' => \$this->collection,"
            ]
        );
    }

    /** @test */
    public function it_includes_relationships_in_resource()
    {
        $this->artisan('wink:generate-resources', [
            '--model' => 'Admin/Inventory'
        ])->assertSuccessful();

        $this->assertResourceExists(
            $this->testResourceDir . '/InventoryResource.php',
            [
                "'category' => new CategoryResource(\$this->whenLoaded('category')),",
                "'items' => ItemResource::collection(\$this->whenLoaded('items')),"
            ]
        );
    }

    /** @test */
    public function it_generates_resources_for_entire_directory()
    {
        $this->artisan('wink:generate-resources', [
            '--directory' => $this->testModelDir
        ])->assertSuccessful();

        $this->assertResourceExists(
            $this->testResourceDir . '/InventoryResource.php',
            [
                'namespace App\Http\Resources;',
                'class InventoryResource extends JsonResource'
            ]
        );
    }

    /** @test */
    public function it_generates_resources_in_custom_output_directory()
    {
        $customOutputDir = $this->app->basePath('app/Http/Resources/Custom');

        $this->artisan('wink:generate-resources', [
            '--model' => 'Admin/Inventory',
            '--output' => $customOutputDir
        ])->assertSuccessful();

        $this->assertResourceExists(
            $customOutputDir . '/InventoryResource.php',
            [
                'namespace App\Http\Resources;',
                'class InventoryResource extends JsonResource'
            ]
        );
    }

    /** @test */
    public function it_handles_invalid_model_gracefully()
    {
        $this->artisan('wink:generate-resources', [
                '--model' => 'NonExistentModel'
            ])
            ->assertFailed()
            ->expectsOutput('Error: Model not found: NonExistentModel');
    }
}
