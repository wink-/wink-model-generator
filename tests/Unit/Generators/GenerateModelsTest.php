<?php

namespace Tests\Unit\Generators;

use Tests\TestCase;
use Wink\ModelGenerator\Config\GeneratorConfig;
use Wink\ModelGenerator\Services\FileService;
use Wink\ModelGenerator\Generators\ModelGenerator;
use Wink\ModelGenerator\Exceptions\InvalidInputException;

class GenerateModelsTest extends TestCase
{
    private ModelGenerator $generator;
    private GeneratorConfig $config;
    private FileService $fileService;

    protected function setUp(): void
    {
        parent::setUp();

        // Set up mock FileService
        $this->fileService = $this->createMock(FileService::class);
        $this->fileService
            ->expects($this->any())
            ->method('get')
            ->willReturn($this->getModelStub());

        // Set up config
        $this->config = new GeneratorConfig([
            'model_namespace' => 'App\\Models',
            'model_path' => __DIR__ . '/../../test-output/models',
        ]);

        // Initialize ModelGenerator
        $this->generator = new ModelGenerator($this->config, $this->fileService);
    }

    public function testGenerateBasicModel(): void
    {
        $columns = [
            (object)['name' => 'id', 'type' => 'integer'],
            (object)['name' => 'name', 'type' => 'string'],
            (object)['name' => 'email', 'type' => 'string'],
        ];

        $content = $this->generator->generate(
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
    }

    public function testGenerateModelWithRelationships(): void
    {
        $columns = [
            (object)['name' => 'id', 'type' => 'integer'],
            (object)['name' => 'post_id', 'type' => 'integer'],
        ];

        $foreignKeys = [
            (object)[
                'table' => 'posts',
                'from' => 'post_id',
                'to' => 'post'
            ]
        ];

        $content = $this->generator->generate(
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

    public function testGenerateModelWithValidationRules(): void
    {
        $columns = [
            (object)['name' => 'name', 'type' => 'string'],
            (object)['name' => 'email', 'type' => 'string'],
            (object)['name' => 'age', 'type' => 'integer'],
        ];

        $content = $this->generator->generate(
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

    private function getModelStub(): string
    {
        return <<<'EOT'
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
EOT;
    }
}
