<?php

declare(strict_types=1);

namespace Wink\ModelGenerator\Generators;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Wink\ModelGenerator\Config\GeneratorConfig;

class FactoryGenerator
{
    private GeneratorConfig $config;

    public function __construct(GeneratorConfig $config)
    {
        $this->config = $config;
    }

    public function generate(string $modelName, array $columns): string
    {
        $definitions = [];
        foreach ($columns as $column) {
            // Skip auto-incremented fields, timestamps, and common ID fields
            if ($this->shouldSkipColumn($column)) {
                continue;
            }

            $faker = $this->getFakerMethod($column);
            $definitions[] = "            '{$column->name}' => fake()->{$faker}";
        }

        $definitionsString = implode(",\n", $definitions);

        $template = File::get(__DIR__.'/../../stubs/factory.stub');

        // Extract the base model name and full namespace
        $modelBaseName = class_basename($modelName);
        $modelNamespace = $this->config->getModelNamespace();
        
        // If modelName contains namespace separators, prepend to namespace
        if (str_contains($modelName, '\\')) {
            $parts = explode('\\', $modelName);
            $modelBaseName = array_pop($parts);
            $modelNamespace .= '\\' . implode('\\', $parts);
        }

        $replacements = [
            '{{ namespace }}' => $this->config->getFactoryNamespace(),
            '{{ modelNamespace }}' => $modelNamespace,
            '{{ modelName }}' => $modelBaseName,
            '{{ definitionsString }}' => $definitionsString,
        ];

        return array_reduce(
            array_keys($replacements),
            fn (string $content, string $key) => Str::replace($key, $replacements[$key], $content),
            $template
        );
    }

    private function shouldSkipColumn($column): bool
    {
        // Skip timestamp fields
        if (in_array($column->name, ['created_at', 'updated_at', 'deleted_at'])) {
            return true;
        }

        // Skip auto-increment fields (MySQL)
        if (isset($column->extra) && str_contains(strtolower($column->extra), 'auto_increment')) {
            return true;
        }

        // Skip fields that are primary keys and likely auto-incremented
        // This covers cases where we might not have the 'extra' field (SQLite, PostgreSQL)
        if (isset($column->primary) && $column->primary && 
            (strtolower($column->name) === 'id' || str_ends_with(strtolower($column->name), '_id'))) {
            return true;
        }

        return false;
    }

    private function getFakerMethod($column): string
    {
        $name = strtolower($column->name);
        $type = strtolower($column->type);

        // Common column name patterns
        if (str_contains($name, 'email')) {
            return 'safeEmail()';
        }
        if (str_contains($name, 'name')) {
            return 'name()';
        }
        if (str_contains($name, 'phone')) {
            return 'phoneNumber()';
        }
        if (str_contains($name, 'address')) {
            return 'address()';
        }
        if (str_contains($name, 'city')) {
            return 'city()';
        }
        if (str_contains($name, 'country')) {
            return 'country()';
        }
        if (str_contains($name, 'zip')) {
            return 'postcode()';
        }
        if (str_contains($name, 'password')) {
            return 'password()';
        }
        if (str_contains($name, 'url')) {
            return 'url()';
        }
        if (str_contains($name, 'description')) {
            return 'text()';
        }
        if (str_contains($name, 'title')) {
            return 'sentence()';
        }

        // Data types
        return match ($type) {
            'int', 'integer', 'bigint', 'smallint', 'tinyint' => 'randomNumber()',
            'decimal', 'float', 'double' => 'randomFloat()',
            'boolean', 'bool' => 'boolean()',
            'date' => 'date()',
            'datetime', 'timestamp' => 'dateTime()',
            'json', 'array' => 'words(3, true)',
            default => 'text()'
        };
    }
}
