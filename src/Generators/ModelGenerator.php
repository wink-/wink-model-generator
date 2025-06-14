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

    public function __construct(GeneratorConfig $config, FileService $fileService)
    {
        $this->config = $config;
        $this->fileService = $fileService;
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
        bool $withRules = false
    ): string {
        if (empty($modelName) || empty($tableName)) {
            throw new InvalidInputException('Model name and table name are required');
        }

        $template = $this->fileService->get(__DIR__.'/../Templates/model.stub');

        $modelDefinition = $this->buildModelDefinition(
            $modelName,
            $tableName,
            $connection,
            $columns,
            $foreignKeys,
            $withRelationships,
            $withRules
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
        bool $withRules
    ): array {
        $timestamps = false;
        $modelData = [
            'fillable' => [],
            'properties' => [],
            'casts' => [],
            'rules' => [],
            'relationships' => [],
        ];

        foreach ($columns as $column) {
            $this->processColumn($column, $modelData, $timestamps);
        }

        if ($withRelationships) {
            $modelData['relationships'] = $this->processRelationships($foreignKeys);
        }

        if ($withRules) {
            $modelData['rules'] = $this->generateValidationRules($columns);
        }

        return [
            '{{ namespace }}' => $this->config->getModelNamespace(),
            '{{ class }}' => $modelName,
            '{{ table }}' => $tableName,
            '{{ connection }}' => $connection,
            '{{ timestamps }}' => $timestamps ? 'true' : 'false',
            '{{ fillable }}' => $this->formatArrayContent($modelData['fillable']),
            '{{ properties }}' => implode("\n", $modelData['properties']),
            '{{ relationships }}' => implode("\n\n", $modelData['relationships']),
            '{{ casts }}' => $this->formatArrayContent($modelData['casts']),
            '{{ rules }}' => $this->formatArrayContent($modelData['rules']),
        ];
    }

    private function processColumn(object $column, array &$modelData, bool &$timestamps): void
    {
        if ($this->isTimestampColumn($column->name)) {
            $timestamps = true;

            return;
        }

        if ($column->name === 'id') {
            return;
        }

        $modelData['fillable'][] = "'{$column->name}'";
        $modelData['properties'][] = " * @property {$this->mapSqliteTypeToPhp($column->type)} \${$column->name}";

        $this->addColumnCasts($column, $modelData['casts']);
    }

    private function isTimestampColumn(string $columnName): bool
    {
        return in_array($columnName, ['created_at', 'updated_at']);
    }

    private function addColumnCasts(object $column, array &$casts): void
    {
        if (str_contains($column->type, 'json')) {
            $casts[] = "'{$column->name}' => 'array'";
        } elseif (str_contains($column->name, '_at')) {
            $casts[] = "'{$column->name}' => 'datetime'";
        }
    }

    private function processRelationships(array $foreignKeys): array
    {
        return array_map(function ($foreignKey) {
            $relatedModel = Str::studly(Str::singular($foreignKey->table));

            return <<<EOT
    public function {$foreignKey->to}()
    {
        return \$this->belongsTo(\\App\\Models\\{$relatedModel}::class, '{$foreignKey->from}');
    }
EOT;
        }, $foreignKeys);
    }

    private function generateValidationRules(array $columns): array
    {
        return array_map(function ($column) {
            return $this->generateValidationRule($column);
        }, array_filter($columns, fn ($col) => $col->name !== 'id'));
    }

    private function generateValidationRule(object $column): string
    {
        $rules = ['required'];

        switch (strtolower($column->type)) {
            case 'integer':
            case 'bigint':
                $rules[] = 'integer';
                break;
            case 'string':
            case 'varchar':
                $rules[] = 'string';
                $rules[] = 'max:255';
                break;
            case 'text':
                $rules[] = 'string';
                break;
            case 'boolean':
                $rules[] = 'boolean';
                break;
            case 'date':
                $rules[] = 'date';
                break;
            case 'datetime':
                $rules[] = 'date_format:Y-m-d H:i:s';
                break;
        }

        return "'{$column->name}' => '".implode('|', $rules)."'";
    }

    private function mapSqliteTypeToPhp(string $sqliteType): string
    {
        return match (strtolower($sqliteType)) {
            'integer', 'int', 'bigint', 'smallint', 'tinyint', 'mediumint' => 'int',
            'real', 'float', 'double', 'decimal', 'numeric' => 'float',
            'boolean', 'bool', 'tinyint(1)' => 'bool',
            'datetime', 'timestamp', 'date', 'time' => 'string|\\DateTime',
            'json', 'longtext', 'text', 'mediumtext' => 'string',
            'blob', 'binary', 'varbinary' => 'resource',
            default => 'string',
        };
    }

    private function formatArrayContent(array $items): string
    {
        return empty($items) ? '' : implode(",\n        ", $items);
    }
}
