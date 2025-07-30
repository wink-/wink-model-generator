<?php

declare(strict_types=1);

namespace Wink\ModelGenerator\Generators;

use Illuminate\Support\Str;
use Wink\ModelGenerator\Config\GeneratorConfig;
use Wink\ModelGenerator\Exceptions\InvalidInputException;
use Wink\ModelGenerator\Services\FileService;

class ModelGenerator
{
    private GeneratorConfig $config;

    private FileService $fileService;

    private ScopeGenerator $scopeGenerator;

    private EventsGenerator $eventsGenerator;

    public function __construct(GeneratorConfig $config, FileService $fileService)
    {
        $this->config = $config;
        $this->fileService = $fileService;
        $this->scopeGenerator = new ScopeGenerator($config);
        $this->eventsGenerator = new EventsGenerator($config);
    }

    /**
     * Generate a model class with the specified configuration
     *
     * @throws InvalidInputException
     */
    public function generate(
        string $modelName,
        string $tableName,
        string $connection,
        array $columns,
        array $foreignKeys = [],
        bool $withRelationships = false,
        bool $withRules = false,
        bool $withEvents = false
    ): string {
        if (empty($modelName) || empty($tableName)) {
            throw new InvalidInputException('Model name and table name are required');
        }

        $template = $this->fileService->get(__DIR__.'/../../stubs/model.stub');

        $modelDefinition = $this->buildModelDefinition(
            $modelName,
            $tableName,
            $connection,
            $columns,
            $foreignKeys,
            $withRelationships,
            $withRules,
            $withEvents
        );

        return str_replace(
            array_keys($modelDefinition),
            array_values($modelDefinition),
            $template
        );
    }

    private function buildModelDefinition(
        string $modelName,
        string $tableName,
        string $connection,
        array $columns,
        array $foreignKeys,
        bool $withRelationships,
        bool $withRules,
        bool $withEvents
    ): array {
        $timestamps = false;
        $namespace = 'App\\Models';

        // If modelName includes a path, extract the namespace and class name
        if (strpos($modelName, '/') !== false || strpos($modelName, '\\') !== false) {
            $parts = preg_split('/[\/\\\\]/', $modelName);
            $modelName = array_pop($parts);
            $namespace = 'App\\Models\\'.implode('\\', $parts);
        }

        $modelData = [
            'fillable' => [],
            'properties' => [],
            'casts' => [],
            'rules' => [],
            'relationships' => [],
            'scopes' => [],
        ];

        // Detect primary keys before processing columns (support compound keys)
        $primaryKey = $this->detectPrimaryKey($columns);
        $allPrimaryKeys = $this->detectAllPrimaryKeys($columns);

        foreach ($columns as $column) {
            $this->processColumn($column, $modelData, $timestamps, $allPrimaryKeys);
        }

        if ($withRelationships) {
            $modelData['relationships'] = $this->processRelationships($foreignKeys);
        }

        if ($withRules) {
            $modelData['rules'] = $this->generateValidationRules($columns, $allPrimaryKeys);
        }

        // Detect soft deletes early for event generation
        $softDeletes = $this->detectSoftDeletes($columns);

        // Generate scopes
        $modelData['scopes'] = $this->scopeGenerator->generateScopes($columns);
        $modelData['scopes'] = array_merge($modelData['scopes'], $this->scopeGenerator->generateTimestampScopes($columns));

        // Generate events
        $modelData['events'] = [];
        $modelData['bootMethod'] = '';
        $shouldGenerateEvents = $withEvents || $this->config->getModelProperty('generate_event_methods', false);
        if ($shouldGenerateEvents) {
            $modelData['events'] = $this->eventsGenerator->generateEventMethods($modelName, $softDeletes);
            $modelData['bootMethod'] = $this->eventsGenerator->generateBootMethod($modelName, $softDeletes);
        }

        // Detect key properties using the already detected primary key
        $keyType = $this->detectKeyType($columns, $primaryKey);
        $incrementing = $this->detectIncrementing($columns, $primaryKey);

        // Generate model properties based on config
        $hiddenFields = $this->generateHiddenFields($columns);
        $visibleFields = $this->generateVisibleFields($columns);
        $guardedFields = $this->generateGuardedFields($columns);
        $defaultAttributes = $this->generateDefaultAttributes($columns);

        return [
            '{{ namespace }}' => $namespace,
            '{{ class }}' => $modelName,
            '{{ table }}' => $tableName,
            '{{ connection }}' => $connection,
            '{{ primary_key }}' => $primaryKey,
            '{{ key_type }}' => $keyType,
            '{{ incrementing }}' => $incrementing ? 'true' : 'false',
            '{{ timestamps }}' => $timestamps ? 'true' : 'false',
            '{{ date_format }}' => $this->config->getModelProperty('date_format', 'Y-m-d H:i:s'),
            '{{ per_page_property }}' => $this->generatePerPageProperty(),
            '{{ fillable_or_guarded }}' => $this->generateFillableOrGuarded($modelData['fillable'], $guardedFields),
            '{{ fillable }}' => $this->formatArrayContent($modelData['fillable']), // Backward compatibility
            '{{ hidden_property }}' => $this->generateArrayProperty('hidden', $hiddenFields),
            '{{ visible_property }}' => $this->generateArrayProperty('visible', $visibleFields),
            '{{ default_attributes }}' => $this->generateArrayProperty('attributes', $defaultAttributes),
            '{{ with_property }}' => $this->generateWithProperty(),
            '{{ appends_property }}' => $this->generateAppendsProperty(),
            '{{ touches_property }}' => $this->generateTouchesProperty(),
            '{{ soft_delete_import }}' => $softDeletes ? 'use Illuminate\Database\Eloquent\SoftDeletes;' : '',
            '{{ soft_delete_trait }}' => $softDeletes ? 'use SoftDeletes;' : '',
            '{{ properties }}' => implode("\n", $modelData['properties']),
            '{{ relationships }}' => implode("\n\n", $modelData['relationships']),
            '{{ casts }}' => $this->formatArrayContent($modelData['casts']),
            '{{ rules }}' => $this->formatArrayContent($modelData['rules']),
            '{{ scopes }}' => empty($modelData['scopes']) ? '' : "\n".implode("\n\n", $modelData['scopes']),
            '{{ boot_method }}' => $modelData['bootMethod'],
            '{{ event_methods }}' => empty($modelData['events']) ? '' : "\n".implode("\n\n", $modelData['events']),
            '{{ business_description }}' => $this->generateBusinessDescription($modelName, $tableName),
            '{{ package_name }}' => $this->generatePackageName(),
            '{{ created_date }}' => now()->format('Y-m-d'),
            '{{ business_rules }}' => $this->generateBusinessRules($modelName, $columns),
            '{{ api_endpoint }}' => $this->generateApiEndpoint($modelName),
            '{{ cacheable_attributes }}' => $this->generateCacheableAttributes($columns),
            '{{ searchable_fields }}' => $this->generateSearchableFields($columns),
        ];
    }

    private function processColumn(object $column, array &$modelData, bool &$timestamps, array $allPrimaryKeys): void
    {
        if ($this->isTimestampColumn($column->name)) {
            $timestamps = true;

            return;
        }

        if (in_array($column->name, $allPrimaryKeys)) {
            return;
        }

        $modelData['fillable'][] = "'{$column->name}'";
        $phpType = $this->mapDbTypeToPhpDocType($column);
        $nullable = isset($column->nullable) && $column->nullable ? '?' : '';
        $modelData['properties'][] = " * @property {$nullable}{$phpType} \${$column->name}";

        $this->addColumnCasts($column, $modelData['casts']);
    }

    private function isTimestampColumn(string $columnName): bool
    {
        return in_array($columnName, ['created_at', 'updated_at']);
    }

    private function addColumnCasts(object $column, array &$casts): void
    {
        $dbType = strtolower($column->type);
        $typeExtra = isset($column->type_extra) ? strtolower($column->type_extra) : '';

        if ($dbType === 'json') {
            $casts[] = "'{$column->name}' => 'array'";
        } elseif (($dbType === 'tinyint' && $typeExtra === '1') || 
                  str_contains($typeExtra, 'tinyint(1)') || 
                  $dbType === 'boolean' || 
                  $dbType === 'bool') {
            $casts[] = "'{$column->name}' => 'boolean'";
        } elseif (in_array($dbType, ['datetime', 'timestamp']) || (str_contains($column->name, '_at') && ! str_ends_with($column->name, '_at'))) {
            $casts[] = "'{$column->name}' => 'datetime'";
        } elseif ($dbType === 'date') {
            $casts[] = "'{$column->name}' => 'date'";
        }
        // ENUM casting can be complex (e.g., to PHP 8.1 enums).
        // For now, we don't automatically cast ENUMs beyond string representation.
        // if ($dbType === 'enum' && isset($column->type_extra)) {
        //     Consider custom cast or backed enum if a convention is established.
        // }
    }

    private function processRelationships(array $foreignKeys): array
    {
        return array_map(function ($foreignKey) {
            $tableName = $foreignKey->table;
            $relatedModel = $tableName;

            // If the table name contains a path separator, extract the model name
            if (strpos($tableName, '/') !== false || strpos($tableName, '\\') !== false) {
                $parts = preg_split('/[\/\\\\]/', $tableName);
                $relatedModel = end($parts);
            }

            $modelName = Str::studly(Str::singular($relatedModel));
            $namespace = isset($foreignKey->namespace) ? $foreignKey->namespace : '';

            $fullModelClass = $namespace ? "\\App\\Models\\{$namespace}\\{$modelName}" : "\\App\\Models\\{$modelName}";

            return <<<EOT
    public function {$foreignKey->to}()
    {
        return \$this->belongsTo({$fullModelClass}::class, '{$foreignKey->from}');
    }
EOT;
        }, $foreignKeys);
    }

    private function generateValidationRules(array $columns, array $allPrimaryKeys): array
    {
        return array_map(function ($column) {
            return $this->generateValidationRule($column);
        }, array_filter($columns, fn ($col) => !in_array($col->name, $allPrimaryKeys)));
    }

    private function generateValidationRule(object $column): string
    {
        // Determine if the column is nullable
        // $column->notnull == 1 means NOT NULL
        // $column->notnull == 0 means NULLABLE
        if (isset($column->notnull) && $column->notnull == 0) {
            $rules = ['nullable'];
        } else {
            $rules = ['required'];
        }

        $dbType = strtolower($column->type);
        $typeExtra = isset($column->type_extra) ? strtolower($column->type_extra) : '';

        if (str_contains($typeExtra, 'tinyint(1)') || $dbType === 'boolean' || $dbType === 'bool') {
            $rules[] = 'boolean';
        } else {
            $rules = array_merge($rules, match ($dbType) {
                'integer', 'int', 'bigint', 'smallint', 'tinyint', 'mediumint' => ['integer'],
                'real', 'float', 'double', 'decimal', 'numeric' => ['numeric'],
                'varchar', 'char', 'string' => $this->getStringValidationRules($column),
                'text', 'longtext', 'mediumtext', 'tinytext' => ['string'],
                'json' => ['string', 'json'],
                'date' => ['date'],
                'datetime', 'timestamp' => ['date_format:Y-m-d H:i:s'],
                'time' => ['date_format:H:i:s'],
                'enum', 'set' => $this->getEnumValidationRules($column),
                default => []
            });
        }

        return "'{$column->name}' => '".implode('|', array_unique($rules))."'";
    }

    private function mapDbTypeToPhpDocType(object $column): string
    {
        $dbType = strtolower($column->type);
        $typeExtra = isset($column->type_extra) ? strtolower($column->type_extra) : '';

        // Check if this is a boolean column based on type or name
        if (str_contains($typeExtra, 'tinyint(1)') || 
            ($dbType === 'tinyint' && $typeExtra === '1') || 
            $dbType === 'boolean' || 
            $dbType === 'bool') {
            return 'bool';
        }

        // Normalize common type variations
        if (str_contains($dbType, 'varchar')) {
            $dbType = 'varchar';
        }
        if (str_contains($dbType, 'int')) {
            $dbType = 'integer';
        } // Catches unsigned int etc. for switch

        return match ($dbType) {
            'integer', 'bigint', 'smallint', 'tinyint', 'mediumint' => 'int',
            'boolean', 'bool' => 'bool',
            'real', 'float', 'double', 'decimal', 'numeric' => 'float',
            'datetime', 'timestamp' => '\\Illuminate\\Support\\Carbon',
            'date' => '\\Illuminate\\Support\\Carbon',
            'time' => 'string',
            'json' => 'array',
            'text', 'longtext', 'mediumtext', 'tinytext', 'varchar', 'char', 'enum', 'set', 'string' => 'string',
            'blob', 'longblob', 'mediumblob', 'tinyblob', 'binary', 'varbinary' => 'string',
            default => 'mixed',
        };
    }

    private function extractMaxLength(object $column): ?int
    {
        if (isset($column->length) && is_numeric($column->length) && $column->length > 0) {
            return (int) $column->length; // MySQL: CHARACTER_MAXIMUM_LENGTH
        }
        // SQLite: type might be "VARCHAR(255)"
        if (isset($column->type) && preg_match('/(?:varchar|char)\\s*\\((\\d+)\\)/i', $column->type, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    private function extractEnumValues(string $typeExtra): array
    {
        if (preg_match('/(?:enum|set)\\s*\\((.+)\\)/i', $typeExtra, $matches)) {
            $values = explode(',', $matches[1]);

            return array_map(fn ($val) => trim(trim($val), "'"), $values);
        }

        return [];
    }

    private function getStringValidationRules(object $column): array
    {
        $rules = ['string'];
        $maxLength = $this->extractMaxLength($column);
        if ($maxLength) {
            $rules[] = 'max:'.$maxLength;
        } else {
            $rules[] = 'max:255';
        }

        return $rules;
    }

    private function getEnumValidationRules(object $column): array
    {
        $rules = ['string'];
        if (isset($column->type_extra)) {
            $enumValues = $this->extractEnumValues($column->type_extra);
            if (! empty($enumValues)) {
                $rules[] = 'in:'.implode(',', $enumValues);
            }
        }

        return $rules;
    }

    private function formatArrayContent(array $items): string
    {
        return empty($items) ? '' : implode(",\n        ", $items);
    }

    private function detectPrimaryKey(array $columns): string
    {
        if (! $this->config->getModelProperty('auto_detect_primary_key', true)) {
            return 'id';
        }

        foreach ($columns as $column) {
            if (isset($column->primary) && $column->primary) {
                return $column->name;
            }
            // SQLite uses pk field
            if (isset($column->pk) && $column->pk) {
                return $column->name;
            }
        }

        return 'id';
    }

    /**
     * Get all primary key column names (supports compound primary keys)
     */
    private function detectAllPrimaryKeys(array $columns): array
    {
        if (! $this->config->getModelProperty('auto_detect_primary_key', true)) {
            return ['id'];
        }

        $primaryKeys = [];
        foreach ($columns as $column) {
            if (isset($column->primary) && $column->primary) {
                $primaryKeys[] = $column->name;
            }
            // SQLite uses pk field
            if (isset($column->pk) && $column->pk) {
                $primaryKeys[] = $column->name;
            }
        }

        return empty($primaryKeys) ? ['id'] : $primaryKeys;
    }

    private function detectKeyType(array $columns, string $primaryKey): string
    {
        foreach ($columns as $column) {
            if ($column->name === $primaryKey) {
                $dbType = strtolower($column->type);
                if (in_array($dbType, ['varchar', 'char', 'string', 'uuid'])) {
                    return 'string';
                }

                return 'int';
            }
        }

        return 'int';
    }

    private function detectIncrementing(array $columns, string $primaryKey): bool
    {
        foreach ($columns as $column) {
            if ($column->name === $primaryKey) {
                $dbType = strtolower($column->type);
                // UUID and string primary keys are not incrementing
                if (in_array($dbType, ['varchar', 'char', 'string', 'uuid'])) {
                    return false;
                }
                // Check if column has auto_increment
                if (isset($column->extra) && strpos(strtolower($column->extra), 'auto_increment') !== false) {
                    return true;
                }

                return true; // Default to true for integer types
            }
        }

        return true;
    }

    private function generateHiddenFields(array $columns): array
    {
        if (! $this->config->getModelProperty('auto_hidden_fields', true)) {
            return [];
        }

        $patterns = $this->config->getModelProperty('hidden_field_patterns', ['password', 'token', 'secret', 'key', 'hash']);
        $hidden = [];

        foreach ($columns as $column) {
            foreach ($patterns as $pattern) {
                if (strpos(strtolower($column->name), $pattern) !== false) {
                    $hidden[] = "'{$column->name}'";
                    break;
                }
            }
        }

        return $hidden;
    }

    private function generateVisibleFields(array $columns): array
    {
        if (! $this->config->getModelProperty('use_visible_instead_of_hidden', false)) {
            return [];
        }

        // When using visible, include all non-sensitive fields
        $patterns = $this->config->getModelProperty('hidden_field_patterns', ['password', 'token', 'secret', 'key', 'hash']);
        $visible = [];

        foreach ($columns as $column) {
            $isHidden = false;
            foreach ($patterns as $pattern) {
                if (strpos(strtolower($column->name), $pattern) !== false) {
                    $isHidden = true;
                    break;
                }
            }
            if (! $isHidden) {
                $visible[] = "'{$column->name}'";
            }
        }

        return $visible;
    }

    private function generateGuardedFields(array $columns): array
    {
        if (! $this->config->getModelProperty('use_guarded_instead_of_fillable', false)) {
            return [];
        }

        return array_map(fn ($field) => "'{$field}'",
            $this->config->getModelProperty('guarded_fields', ['id', 'created_at', 'updated_at'])
        );
    }

    private function generateDefaultAttributes(array $columns): array
    {
        if (! $this->config->getModelProperty('auto_default_attributes', true)) {
            return [];
        }

        $defaults = [];
        foreach ($columns as $column) {
            if (isset($column->default) && $column->default !== null && $column->default !== '') {
                $value = is_numeric($column->default) ? $column->default : "'{$column->default}'";
                $defaults[] = "'{$column->name}' => {$value}";
            }
        }

        return $defaults;
    }

    private function detectSoftDeletes(array $columns): bool
    {
        if (! $this->config->getModelProperty('auto_detect_soft_deletes', true)) {
            return false;
        }

        foreach ($columns as $column) {
            if ($column->name === 'deleted_at') {
                return true;
            }
        }

        return false;
    }

    private function generatePerPageProperty(): string
    {
        $perPage = $this->config->getModelProperty('per_page');
        if ($perPage === null) {
            return '';
        }

        return "protected \$perPage = {$perPage};";
    }

    private function generateFillableOrGuarded(array $fillable, array $guarded): string
    {
        if ($this->config->getModelProperty('use_guarded_instead_of_fillable', false)) {
            $content = $this->formatArrayContent($guarded);

            return "/**\n     * The attributes that aren't mass assignable.\n     *\n     * @var array<string>\n     */\n    protected \$guarded = [\n        {$content}\n    ];";
        }

        $content = $this->formatArrayContent($fillable);

        return "/**\n     * The attributes that are mass assignable.\n     *\n     * @var array<string>\n     */\n    protected \$fillable = [\n        {$content}\n    ];";
    }

    private function generateArrayProperty(string $property, array $items): string
    {
        if (empty($items)) {
            return "protected \${$property} = [];";
        }

        $content = $this->formatArrayContent($items);

        return "protected \${$property} = [\n        {$content}\n    ];";
    }

    private function generateWithProperty(): string
    {
        if (! $this->config->getModelProperty('auto_eager_load', false)) {
            return 'protected $with = [];';
        }

        $relationships = $this->config->getModelProperty('eager_load_relationships', []);
        if (empty($relationships)) {
            return 'protected $with = [];';
        }

        $items = array_map(fn ($rel) => "'{$rel}'", $relationships);
        $content = $this->formatArrayContent($items);

        return "protected \$with = [\n        {$content}\n    ];";
    }

    private function generateAppendsProperty(): string
    {
        if (! $this->config->getModelProperty('auto_appends', false)) {
            return 'protected $appends = [];';
        }

        return 'protected $appends = [];';
    }

    private function generateTouchesProperty(): string
    {
        if (! $this->config->getModelProperty('auto_touches', false)) {
            return 'protected $touches = [];';
        }

        return 'protected $touches = [];';
    }

    private function generateBusinessDescription(string $modelName, string $tableName): string
    {
        $singular = Str::singular($tableName);
        $plural = Str::plural($tableName);

        return "a {$singular} entity in the {$plural} business domain";
    }

    private function generatePackageName(): string
    {
        return 'App\\Models\\Generated';
    }

    private function generateBusinessRules(string $modelName, array $columns): string
    {
        $rules = [];

        foreach ($columns as $column) {
            if ($column->name === 'status') {
                $rules[] = 'Status must be managed through proper business workflows';
            }
            if ($column->name === 'user_id') {
                $rules[] = 'Must be associated with a valid user';
            }
            if (str_contains($column->name, 'email')) {
                $rules[] = 'Email addresses must be unique and validated';
            }
            if (str_contains($column->name, 'phone')) {
                $rules[] = 'Phone numbers should follow international format';
            }
        }

        return empty($rules) ? 'Standard business validation rules apply' : implode("\n * - ", $rules);
    }

    private function generateApiEndpoint(string $modelName): string
    {
        return Str::kebab(Str::plural($modelName));
    }

    private function generateCacheableAttributes(array $columns): string
    {
        $cacheable = [];

        foreach ($columns as $column) {
            // Skip sensitive or frequently changing fields
            if (in_array($column->name, ['password', 'remember_token', 'updated_at', 'deleted_at'])) {
                continue;
            }

            // Include stable, frequently accessed fields
            if (in_array($column->name, ['id', 'name', 'title', 'slug', 'email', 'status', 'type', 'created_at'])) {
                $cacheable[] = "'{$column->name}'";
            }
        }

        return implode(', ', $cacheable);
    }

    private function generateSearchableFields(array $columns): string
    {
        $searchable = [];

        foreach ($columns as $column) {
            $type = strtolower($column->type);

            // Only include text-based fields that are commonly searched
            if (in_array($type, ['varchar', 'char', 'text', 'longtext', 'mediumtext', 'tinytext', 'string'])) {
                if (in_array($column->name, ['name', 'title', 'description', 'content', 'body', 'summary', 'email', 'username', 'slug'])) {
                    $searchable[] = "'{$column->name}'";
                }
            }
        }

        return implode(', ', $searchable);
    }
}
