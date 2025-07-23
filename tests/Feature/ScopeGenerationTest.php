<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;
use Wink\ModelGenerator\Config\GeneratorConfig;
use Wink\ModelGenerator\Generators\ScopeGenerator;

class ScopeGenerationTest extends TestCase
{
    use RefreshDatabase;

    private ScopeGenerator $scopeGenerator;

    protected function setUp(): void
    {
        parent::setUp();
        
        $config = new GeneratorConfig([
            'model_properties' => [
                'auto_generate_scopes' => true,
                'auto_generate_timestamp_scopes' => true,
            ]
        ]);
        
        $this->scopeGenerator = new ScopeGenerator($config);
    }

    public function test_generates_boolean_scopes_from_is_active_column()
    {
        $columns = [
            (object) [
                'name' => 'is_active',
                'type' => 'tinyint',
                'type_extra' => '1',
            ]
        ];

        $scopes = $this->scopeGenerator->generateScopes($columns);

        $this->assertCount(2, $scopes);
        $this->assertStringContainsString('scopeActive', $scopes[0]);
        $this->assertStringContainsString('scopeInactive', $scopes[1]);
        $this->assertStringContainsString("where('is_active', true)", $scopes[0]);
        $this->assertStringContainsString("where('is_active', false)", $scopes[1]);
    }

    public function test_generates_status_scopes_from_status_column()
    {
        $columns = [
            (object) [
                'name' => 'status',
                'type' => 'varchar',
                'type_extra' => null,
            ]
        ];

        $scopes = $this->scopeGenerator->generateScopes($columns);

        $this->assertCount(1, $scopes);
        $this->assertStringContainsString('scopeByStatus', $scopes[0]);
        $this->assertStringContainsString("where('status', \$status)", $scopes[0]);
    }

    public function test_generates_enum_scopes()
    {
        $columns = [
            (object) [
                'name' => 'priority',
                'type' => 'enum',
                'type_extra' => "enum('low','medium','high')",
            ]
        ];

        $scopes = $this->scopeGenerator->generateScopes($columns);

        $this->assertCount(3, $scopes);
        $this->assertStringContainsString('scopeLow', $scopes[0]);
        $this->assertStringContainsString('scopeMedium', $scopes[1]);
        $this->assertStringContainsString('scopeHigh', $scopes[2]);
    }

    public function test_generates_date_scopes()
    {
        $columns = [
            (object) [
                'name' => 'published_at',
                'type' => 'datetime',
                'type_extra' => null,
            ]
        ];

        $scopes = $this->scopeGenerator->generateScopes($columns);

        $this->assertCount(4, $scopes);
        $this->assertStringContainsString('scopeRecent', $scopes[0]);
        $this->assertStringContainsString('scopePublishedAtBetween', $scopes[1]);
        $this->assertStringContainsString('scopePublishedAtAfter', $scopes[2]);
        $this->assertStringContainsString('scopePublishedAtBefore', $scopes[3]);
    }

    public function test_generates_foreign_key_scopes()
    {
        $columns = [
            (object) [
                'name' => 'user_id',
                'type' => 'bigint',
                'type_extra' => null,
            ]
        ];

        $scopes = $this->scopeGenerator->generateScopes($columns);

        $this->assertCount(1, $scopes);
        $this->assertStringContainsString('scopeByUser', $scopes[0]);
        $this->assertStringContainsString("where('user_id', \$id)", $scopes[0]);
    }

    public function test_generates_search_scopes()
    {
        $columns = [
            (object) [
                'name' => 'title',
                'type' => 'varchar',
                'type_extra' => null,
            ]
        ];

        $scopes = $this->scopeGenerator->generateScopes($columns);

        $this->assertCount(1, $scopes);
        $this->assertStringContainsString('scopeSearchTitle', $scopes[0]);
        $this->assertStringContainsString("where('title', 'LIKE', '%' . \$search . '%')", $scopes[0]);
    }

    public function test_generates_numeric_scopes()
    {
        $columns = [
            (object) [
                'name' => 'price',
                'type' => 'decimal',
                'type_extra' => null,
            ]
        ];

        $scopes = $this->scopeGenerator->generateScopes($columns);

        $this->assertCount(3, $scopes);
        $this->assertStringContainsString('scopePriceGreaterThan', $scopes[0]);
        $this->assertStringContainsString('scopePriceLessThan', $scopes[1]);
        $this->assertStringContainsString('scopePriceBetween', $scopes[2]);
    }

    public function test_generates_timestamp_scopes()
    {
        $scopes = $this->scopeGenerator->generateTimestampScopes();

        $this->assertCount(5, $scopes);
        $this->assertStringContainsString('scopeCreatedRecently', $scopes[0]);
        $this->assertStringContainsString('scopeUpdatedRecently', $scopes[1]);
        $this->assertStringContainsString('scopeCreatedToday', $scopes[2]);
        $this->assertStringContainsString('scopeLatest', $scopes[3]);
        $this->assertStringContainsString('scopeOldest', $scopes[4]);
    }

    public function test_scope_generation_can_be_disabled()
    {
        $config = new GeneratorConfig([
            'model_properties' => [
                'auto_generate_scopes' => false,
            ]
        ]);
        
        $scopeGenerator = new ScopeGenerator($config);
        
        $columns = [
            (object) [
                'name' => 'is_active',
                'type' => 'tinyint',
                'type_extra' => '1',
            ]
        ];

        $scopes = $scopeGenerator->generateScopes($columns);

        $this->assertEmpty($scopes);
    }

    public function test_complex_model_with_multiple_scope_types()
    {
        $columns = [
            (object) [
                'name' => 'id',
                'type' => 'bigint',
                'type_extra' => null,
            ],
            (object) [
                'name' => 'title',
                'type' => 'varchar',
                'type_extra' => null,
            ],
            (object) [
                'name' => 'is_published',
                'type' => 'tinyint',
                'type_extra' => '1',
            ],
            (object) [
                'name' => 'status',
                'type' => 'enum',
                'type_extra' => "enum('draft','published','archived')",
            ],
            (object) [
                'name' => 'user_id',
                'type' => 'bigint',
                'type_extra' => null,
            ],
            (object) [
                'name' => 'published_at',
                'type' => 'datetime',
                'type_extra' => null,
            ],
            (object) [
                'name' => 'views',
                'type' => 'integer',
                'type_extra' => null,
            ],
        ];

        $scopes = $this->scopeGenerator->generateScopes($columns);

        // Should generate multiple types of scopes
        $this->assertGreaterThan(10, count($scopes));
        
        // Check for boolean scopes (specifically for is_published column)
        $booleanScopes = array_filter($scopes, fn($scope) => 
            str_contains($scope, "where('is_published', true)") || 
            str_contains($scope, "where('is_published', false)")
        );
        $this->assertCount(2, $booleanScopes);
        
        // Check for enum scopes (all 3 should be there)
        $enumScopes = array_filter($scopes, fn($scope) => 
            str_contains($scope, 'scopeDraft') || 
            str_contains($scope, 'scopeArchived') ||
            (str_contains($scope, 'scopePublished') && str_contains($scope, "where('status', 'published')"))
        );
        $this->assertCount(3, $enumScopes);
        
        // Check for foreign key scopes
        $foreignKeyScopes = array_filter($scopes, fn($scope) => str_contains($scope, 'scopeByUser'));
        $this->assertCount(1, $foreignKeyScopes);
        
        // Check for search scopes
        $searchScopes = array_filter($scopes, fn($scope) => str_contains($scope, 'scopeSearchTitle'));
        $this->assertCount(1, $searchScopes);
        
        // Check for date scopes (including Recent scope)
        $dateScopes = array_filter($scopes, fn($scope) => 
            str_contains($scope, 'scopePublishedAt') || 
            str_contains($scope, 'scopeRecent')
        );
        $this->assertCount(4, $dateScopes);
        
        // Check for numeric scopes
        $numericScopes = array_filter($scopes, fn($scope) => str_contains($scope, 'scopeViews'));
        $this->assertCount(3, $numericScopes);
    }
}