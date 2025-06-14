<?php

namespace Tests\Feature\Commands;

use Illuminate\Support\Facades\File;
use Tests\TestCase;
use Wink\ModelGenerator\Config\GeneratorConfig;
use Wink\ModelGenerator\Services\FileService;
use Wink\ModelGenerator\Generators\ModelGenerator;
use Wink\ModelGenerator\Exceptions\InvalidInputException;

class GenerateModelsCommandTest extends TestCase
{
    protected $outputPath;
    protected $modelGenerator;
    protected $fileService;
    protected $config;

    protected function setUp(): void
    {
        parent::setUp();

        // Set up output paths
        $this->outputPath = __DIR__ . '/../../../test-output/models';
        
        // Create directories if they don't exist
        if (!File::isDirectory($this->outputPath)) {
            File::makeDirectory($this->outputPath, 0755, true);
        }

        // Set up mock FileService
        $this->fileService = $this->createMock(FileService::class);
        $this->fileService
            ->expects($this->any())
            ->method('get')
            ->willReturn(<<<'EOT'
<?php

namespace {{ namespace }};

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * {{ class }} Model
 *
{{ properties }}
 */
class {{ class }} extends Model
{
    use HasFactory;

    protected $connection = '{{ connection }}';
    protected $table = '{{ table }}';
    public $timestamps = {{ timestamps }};

    protected $fillable = [
        {{ fillable }}
    ];

    protected $casts = [
        {{ casts }}
    ];

    public static function rules(): array
    {
        return [
            {{ rules }}
        ];
    }

{{ relationships }}
}
EOT
            );

        // Set up config
        $this->config = new GeneratorConfig([
            'model_namespace' => 'App\\Models',
            'model_path' => $this->outputPath,
        ]);

        // Initialize ModelGenerator
        $this->modelGenerator = new ModelGenerator($this->config, $this->fileService);
    }

    public function test_it_can_generate_model_content()
    {
        $columns = [
            (object)['name' => 'id', 'type' => 'integer'],
            (object)['name' => 'name', 'type' => 'string'],
            (object)['name' => 'email', 'type' => 'string'],
            (object)['name' => 'created_at', 'type' => 'datetime'],
            (object)['name' => 'updated_at', 'type' => 'datetime']
        ];

        $content = $this->modelGenerator->generate(
            'User',
            'users',
            'testing',
            $columns,
            [],
            false,
            false
        );

        $this->assertStringContainsString('class User extends Model', $content);
        $this->assertStringContainsString('protected $fillable = [', $content);
        $this->assertStringContainsString("'name'", $content);
        $this->assertStringContainsString("'email'", $content);
        $this->assertStringContainsString('protected $casts = [', $content);
        $this->assertStringContainsString('public $timestamps = true', $content);
    }

    public function test_it_can_generate_model_with_relationships()
    {
        $columns = [
            (object)['name' => 'id', 'type' => 'integer'],
            (object)['name' => 'name', 'type' => 'string'],
            (object)['name' => 'post_id', 'type' => 'integer'],
        ];

        $foreignKeys = [
            (object)[
                'table' => 'posts',
                'from' => 'post_id',
                'to' => 'post'
            ]
        ];

        $content = $this->modelGenerator->generate(
            'User',
            'users',
            'testing',
            $columns,
            $foreignKeys,
            true,
            false
        );

        $this->assertStringContainsString('public function post()', $content);
        $this->assertStringContainsString('return $this->belongsTo(\App\Models\Post::class,', $content);
    }

    public function test_it_can_generate_model_with_validation_rules()
    {
        $columns = [
            (object)['name' => 'name', 'type' => 'string'],
            (object)['name' => 'email', 'type' => 'string'],
            (object)['name' => 'age', 'type' => 'integer'],
        ];

        $content = $this->modelGenerator->generate(
            'User',
            'users',
            'testing',
            $columns,
            [],
            false,
            true
        );

        $this->assertStringContainsString("'name' => 'required|string|max:255'", $content);
        $this->assertStringContainsString("'email' => 'required|string|max:255'", $content);
        $this->assertStringContainsString("'age' => 'required|integer'", $content);
    }

    public function test_it_throws_exception_for_invalid_input()
    {
        $this->expectException(InvalidInputException::class);
        
        $this->modelGenerator->generate(
            '',  // Empty model name
            'users',
            'testing',
            [],
            [],
            false,
            false
        );
    }

    public function test_it_handles_json_and_datetime_fields_correctly()
    {
        $columns = [
            (object)['name' => 'settings', 'type' => 'json'],
            (object)['name' => 'last_login_at', 'type' => 'datetime'],
        ];

        $content = $this->modelGenerator->generate(
            'User',
            'users',
            'testing',
            $columns,
            [],
            false,
            false
        );

        $this->assertStringContainsString("'settings' => 'array'", $content);
        $this->assertStringContainsString("'last_login_at' => 'datetime'", $content);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        
        // Clean up test directory
        if (File::isDirectory($this->outputPath)) {
            File::deleteDirectory($this->outputPath);
        }
    }
}