<?php

declare(strict_types=1);

namespace Wink\ModelGenerator\Generators;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Wink\ModelGenerator\Config\GeneratorConfig;

class ModelGenerator
{
    private GeneratorConfig $config;

    public function __construct(GeneratorConfig $config)
    {
        $this->config = $config;
    }

    public function generate(
        string $modelName,
        string $tableName,
        string $connection,
        array $columns,
        array $foreignKeys = [],
        bool $withRelationships = false,
        bool $withRules = false
    ): string {
        $fillable = [];
        $properties = [];
        $timestamps = false;
        $relationships = [];
        $casts = [];
        $rules = [];

        if ($withRelationships) {
            foreach ($foreignKeys as $fk) {
                $relationships[] = $this->generateRelationship($fk);
            }
        }

        foreach ($columns as $column) {
            if ($column->name === 'created_at' || $column->name === 'updated_at') {
                $timestamps = true;
                continue;
            }
            if ($column->name !== 'id') {
                $fillable[] = "'{$column->name}'";
                $phpType = $this->mapSqliteTypeToPhp($column->type);
                $properties[] = " * @property {$phpType} \${$column->name}";
            }

            // Add casts for specific types
            if (str_contains($column->type, 'json')) {
                $casts[] = "'{$column->name}' => 'array'";
            } elseif (str_contains($column->name, '_at')) {
                $casts[] = "'{$column->name}' => 'datetime'";
            }

            // Generate validation rules
            if ($withRules) {
                $rules[] = $this->generateValidationRule($column);
            }
        }

        return $this->generateModelContent(
            $this->config->getModelNamespace(),
            $modelName,
            $tableName,
            $connection,
            $timestamps ? 'true' : 'false',
            implode(",\n        ", $fillable),
            implode("\n", $properties),
            implode("\n\n", $relationships),
            implode(",\n        ", $casts),
            implode(",\n            ", array_filter($rules))
        );
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

    private function generateRelationship($foreignKey): string
    {
        $relatedModel = Str::studly(Str::singular($foreignKey->table));
        return <<<EOT
    public function {$foreignKey->to}()
    {
        return \$this->belongsTo(\\App\\Models\\{$relatedModel}::class, '{$foreignKey->from}');
    }
EOT;
    }

    private function generateValidationRule($column): string
    {
        $rules = [];
        
        if ($column->notnull) {
            $rules[] = 'required';
        }
        
        if (str_contains($column->type, 'int')) {
            $rules[] = 'integer';
        }
        
        return "'{$column->name}' => ['" . implode("', '", $rules) . "']";
    }

    private function generateModelContent(
        string $namespace,
        string $modelName,
        string $tableName,
        string $connection,
        string $timestampsValue,
        string $fillableString,
        string $propertiesString,
        string $relationshipsString,
        string $castsString,
        string $rulesString
    ): string {
        $template = File::get(__DIR__ . '/../Templates/model.stub');
        
        $replacements = [
            '{{ namespace }}' => $namespace,
            '{{ modelName }}' => $modelName,
            '{{ tableName }}' => $tableName,
            '{{ connection }}' => $connection,
            '{{ timestampsValue }}' => $timestampsValue,
            '{{ fillableString }}' => $fillableString,
            '{{ propertiesString }}' => $propertiesString,
            '{{ relationshipsString }}' => $relationshipsString,
            '{{ castsString }}' => $castsString,
            '{{ rulesString }}' => $rulesString,
        ];

        return array_reduce(
            array_keys($replacements),
            fn(string $content, string $key) => Str::replace($key, $replacements[$key], $content),
            $template
        );
    }
}
