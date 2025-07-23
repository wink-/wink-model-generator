<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Wink\ModelGenerator\Config\GeneratorConfig;
use Wink\ModelGenerator\Generators\ModelGenerator;
use Wink\ModelGenerator\Services\FileService;

class ModelGeneratorScopeIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private ModelGenerator $modelGenerator;
    private FileService $fileService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $config = new GeneratorConfig([
            'model_properties' => [
                'auto_generate_scopes' => true,
                'auto_generate_timestamp_scopes' => true,
            ]
        ]);
        
        $this->fileService = new FileService();
        $this->modelGenerator = new ModelGenerator($config, $this->fileService);
    }

    public function test_model_generator_includes_scopes_in_generated_model()
    {
        $columns = [
            (object) [
                'name' => 'id',
                'type' => 'bigint',
                'primary' => true,
                'notnull' => 1,
                'default' => null,
            ],
            (object) [
                'name' => 'title',
                'type' => 'varchar',
                'primary' => false,
                'notnull' => 1,
                'default' => null,
            ],
            (object) [
                'name' => 'is_published',
                'type' => 'tinyint',
                'type_extra' => '1',
                'primary' => false,
                'notnull' => 1,
                'default' => '0',
            ],
            (object) [
                'name' => 'status',
                'type' => 'enum',
                'type_extra' => "enum('draft','published','archived')",
                'primary' => false,
                'notnull' => 1,
                'default' => 'draft',
            ],
            (object) [
                'name' => 'created_at',
                'type' => 'timestamp',
                'primary' => false,
                'notnull' => 0,
                'default' => null,
            ],
            (object) [
                'name' => 'updated_at',
                'type' => 'timestamp',
                'primary' => false,
                'notnull' => 0,
                'default' => null,
            ],
        ];

        $modelContent = $this->modelGenerator->generate(
            'Article',
            'articles',
            'testing',
            $columns,
            [],
            false,
            false
        );

        // Check that scopes are included
        $this->assertStringContainsString('scopePublished', $modelContent);
        $this->assertStringContainsString('scopeUnpublished', $modelContent);
        $this->assertStringContainsString('scopeDraft', $modelContent);
        $this->assertStringContainsString('scopeArchived', $modelContent);
        $this->assertStringContainsString('scopeSearchTitle', $modelContent);
        $this->assertStringContainsString('scopeCreatedRecently', $modelContent);
        $this->assertStringContainsString('scopeLatest', $modelContent);
    }

    public function test_model_generator_excludes_scopes_when_disabled()
    {
        $config = new GeneratorConfig([
            'model_properties' => [
                'auto_generate_scopes' => false,
                'auto_generate_timestamp_scopes' => false,
            ]
        ]);
        
        $modelGenerator = new ModelGenerator($config, $this->fileService);
        
        $columns = [
            (object) [
                'name' => 'id',
                'type' => 'bigint',
                'primary' => true,
                'notnull' => 1,
                'default' => null,
            ],
            (object) [
                'name' => 'is_published',
                'type' => 'tinyint',
                'type_extra' => '1',
                'primary' => false,
                'notnull' => 1,
                'default' => '0',
            ],
            (object) [
                'name' => 'created_at',
                'type' => 'timestamp',
                'primary' => false,
                'notnull' => 0,
                'default' => null,
            ],
            (object) [
                'name' => 'updated_at',
                'type' => 'timestamp',
                'primary' => false,
                'notnull' => 0,
                'default' => null,
            ],
        ];

        $modelContent = $modelGenerator->generate(
            'Article',
            'articles',
            'testing',
            $columns,
            [],
            false,
            false
        );

        // Check that no scopes are included
        $this->assertStringNotContainsString('scopePublished', $modelContent);
        $this->assertStringNotContainsString('scopeCreatedRecently', $modelContent);
    }

    public function test_generated_model_has_proper_scope_structure()
    {
        $columns = [
            (object) [
                'name' => 'id',
                'type' => 'bigint',
                'primary' => true,
                'notnull' => 1,
                'default' => null,
            ],
            (object) [
                'name' => 'is_active',
                'type' => 'tinyint',
                'type_extra' => '1',
                'primary' => false,
                'notnull' => 1,
                'default' => '1',
            ],
            (object) [
                'name' => 'created_at',
                'type' => 'timestamp',
                'primary' => false,
                'notnull' => 0,
                'default' => null,
            ],
            (object) [
                'name' => 'updated_at',
                'type' => 'timestamp',
                'primary' => false,
                'notnull' => 0,
                'default' => null,
            ],
        ];

        $modelContent = $this->modelGenerator->generate(
            'Post',
            'posts',
            'testing',
            $columns,
            [],
            false,
            false
        );

        // Check that scopes are properly formatted
        $this->assertStringContainsString('public function scopeActive($query)', $modelContent);
        $this->assertStringContainsString('public function scopeInactive($query)', $modelContent);
        $this->assertStringContainsString('public function scopeCreatedRecently($query', $modelContent);
        $this->assertStringContainsString('public function scopeLatest($query)', $modelContent);
        
        // Check that scopes have proper PHP doc comments
        $this->assertStringContainsString('/**', $modelContent);
        $this->assertStringContainsString('* Scope a query to only include', $modelContent);
        
        // Check that scopes are placed correctly in the model structure
        $this->assertStringContainsString('public static function rules(): array', $modelContent);
        
        // Ensure scopes appear before relationships section
        $scopePosition = strpos($modelContent, 'scopeActive');
        $rulesPosition = strpos($modelContent, 'public static function rules()');
        $this->assertGreaterThan($rulesPosition, $scopePosition);
    }

    public function test_model_with_complex_column_types_generates_appropriate_scopes()
    {
        $columns = [
            (object) [
                'name' => 'id',
                'type' => 'bigint',
                'primary' => true,
                'notnull' => 1,
                'default' => null,
            ],
            (object) [
                'name' => 'email',
                'type' => 'varchar',
                'primary' => false,
                'notnull' => 1,
                'default' => null,
                'length' => 255,
            ],
            (object) [
                'name' => 'email_verified_at',
                'type' => 'timestamp',
                'primary' => false,
                'notnull' => 0,
                'default' => null,
            ],
            (object) [
                'name' => 'role',
                'type' => 'enum',
                'type_extra' => "enum('admin','user','moderator')",
                'primary' => false,
                'notnull' => 1,
                'default' => 'user',
            ],
            (object) [
                'name' => 'login_count',
                'type' => 'integer',
                'primary' => false,
                'notnull' => 1,
                'default' => '0',
            ],
            (object) [
                'name' => 'is_verified',
                'type' => 'tinyint',
                'type_extra' => '1',
                'primary' => false,
                'notnull' => 1,
                'default' => '0',
            ],
            (object) [
                'name' => 'created_at',
                'type' => 'timestamp',
                'primary' => false,
                'notnull' => 0,
                'default' => null,
            ],
            (object) [
                'name' => 'updated_at',
                'type' => 'timestamp',
                'primary' => false,
                'notnull' => 0,
                'default' => null,
            ],
        ];

        $modelContent = $this->modelGenerator->generate(
            'User',
            'users',
            'testing',
            $columns,
            [],
            false,
            false
        );

        // Check boolean scopes
        $this->assertStringContainsString('scopeVerified', $modelContent);
        $this->assertStringContainsString('scopeUnverified', $modelContent);
        
        // Check enum scopes
        $this->assertStringContainsString('scopeAdmin', $modelContent);
        $this->assertStringContainsString('scopeUser', $modelContent);
        $this->assertStringContainsString('scopeModerator', $modelContent);
        
        // Check search scopes
        $this->assertStringContainsString('scopeSearchEmail', $modelContent);
        
        // Check numeric scopes
        $this->assertStringContainsString('scopeLoginCountGreaterThan', $modelContent);
        $this->assertStringContainsString('scopeLoginCountLessThan', $modelContent);
        $this->assertStringContainsString('scopeLoginCountBetween', $modelContent);
        
        // Check date scopes
        $this->assertStringContainsString('scopeEmailVerifiedAtBetween', $modelContent);
        $this->assertStringContainsString('scopeEmailVerifiedAtAfter', $modelContent);
        $this->assertStringContainsString('scopeEmailVerifiedAtBefore', $modelContent);
        
        // Check timestamp scopes
        $this->assertStringContainsString('scopeCreatedRecently', $modelContent);
        $this->assertStringContainsString('scopeLatest', $modelContent);
    }
}