<?php

declare(strict_types=1);

namespace Wink\ModelGenerator\Models\Generators;

use Illuminate\Support\Facades\File;
use Wink\ModelGenerator\Config\GeneratorConfig;
use Wink\ModelGenerator\Exceptions\InvalidInputException;

class FactoryGenerator
{
    private GeneratorConfig $config;

    public function __construct(GeneratorConfig $config)
    {
        $this->config = $config;
    }

    public function generate(
        string $modelName,
        string $tableName,
        array $columns = [],
        array $relationships = [],
        ?string $templatePath = null
    ): string {
        if (empty($modelName) || empty($tableName)) {
            throw new InvalidInputException('Model name and table name are required');
        }

        $definitions = [];
        foreach ($columns as $column) {
            if ($column->name === 'id' || $column->name === 'created_at' || $column->name === 'updated_at') {
                continue;
            }

            $faker = $this->getFakerMethod($column);
            $definitions[] = "            '{$column->name}' => fake()->{$faker}";
        }

        $definitionsString = implode(",\n", $definitions);

        $template = $this->getStub($templatePath);

        $replacements = [
            '{{ namespace }}' => $this->config->getFactoryNamespace(),
            '{{ modelNamespace }}' => $this->config->getModelNamespace(),
            '{{ className }}' => $modelName,
            '{{ definitions }}' => $definitionsString,
        ];

        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $template
        );
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

    private function getStub(?string $path = null): string
    {
        $template_path = $path ?? __DIR__.'/../Templates/factory.stub';

        return File::get($template_path);
    }
}
