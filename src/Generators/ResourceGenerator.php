<?php

declare(strict_types=1);

namespace Wink\ModelGenerator\Generators;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Wink\ModelGenerator\Config\GeneratorConfig;

class ResourceGenerator
{
    private GeneratorConfig $config;

    public function __construct(GeneratorConfig $config)
    {
        $this->config = $config;
    }

    public function generate(
        string $modelClass,
        array $columns,
        array $relationships = [],
        bool $isCollection = false
    ): string {
        $template = $isCollection ?
            File::get(__DIR__ . '/../Templates/collection.stub') :
            File::get(__DIR__ . '/../Templates/resource.stub');

        $className = class_basename($modelClass) . ($isCollection ? 'Collection' : 'Resource');

        $replacements = [
            '{{ namespace }}' => 'App\\Http\\Resources',
            '{{ class }}' => $className,
            '{{ use_statements }}' => $this->generateUseStatements($modelClass, $relationships),
            '{{ fields }}' => $this->generateFields($columns),
            '{{ relationships }}' => $this->generateRelationships($relationships)
        ];

        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $template
        );
    }

    private function generateFields(array $columns): string
    {
        $fields = [];
        $seen = [];

        foreach ($columns as $column) {
            $columnName = is_object($column) ? $column->name : $column;
            
            if (isset($seen[$columnName])) {
                continue;
            }
            $seen[$columnName] = true;

            if ($columnName === 'created_at' || $columnName === 'updated_at') {
                $fields[] = "'{$columnName}' => \$this->{$columnName}?->toISOString()";
            } else {
                $fields[] = "'{$columnName}' => \$this->{$columnName}";
            }
        }

        return implode(",\n            ", $fields);
    }

    private function generateRelationships(array $relationships): string
    {
        $fields = [];

        foreach ($relationships as $relation) {
            $resourceClass = class_basename($relation['related_model']) . 'Resource';

            if ($relation['type'] === 'hasmany' || $relation['type'] === 'belongstomany') {
                $fields[] = "'{$relation['name']}' => {$resourceClass}::collection(\$this->whenLoaded('{$relation['name']}'))";
            } else {
                $fields[] = "'{$relation['name']}' => new {$resourceClass}(\$this->whenLoaded('{$relation['name']}'))";
            }
        }

        return !empty($fields) ? ",\n            " . implode(",\n            ", $fields) : '';
    }

    private function generateUseStatements(string $modelClass, array $relationships): string
    {
        $statements = [];

        // Always use the full model class name
        $statements[] = "use {$modelClass};";

        foreach ($relationships as $relation) {
            $statements[] = "use {$relation['related_model']};";
            $statements[] = "use App\\Http\\Resources\\" . class_basename($relation['related_model']) . "Resource;";
        }

        return implode("\n", array_unique($statements));
    }
}
