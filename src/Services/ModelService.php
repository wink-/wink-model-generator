<?php

declare(strict_types=1);

namespace Wink\ModelGenerator\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use ReflectionClass;
use RuntimeException;
use Wink\ModelGenerator\Exceptions\ModelNotFoundException;

class ModelService
{
    public function resolveModelClass(string $modelName): string
    {
        $fullClassName = $this->parseModelName($modelName);

        if (!class_exists($fullClassName)) {
            throw new ModelNotFoundException("Model not found: {$modelName}");
        }

        return $fullClassName;
    }

    public function createModelInstance(string $modelClass): Model
    {
        return new $modelClass();
    }

    public function getModelInfo(Model $model): array
    {
        return [
            'table' => $model->getTable(),
            'connection' => $model->getConnectionName() ?: config('database.default'),
        ];
    }

    public function getModelRelationships(Model $model): array
    {
        $relationships = [];
        $reflection = new ReflectionClass($model);

        foreach ($reflection->getMethods() as $method) {
            if ($this->isRelationshipMethod($method, $model)) {
                $relationships[] = [
                    'name' => $method->getName(),
                    'type' => $this->getRelationType($method, $model),
                    'related_model' => $this->getRelatedModel($method, $model),
                ];
            }
        }

        return $relationships;
    }

    private function parseModelName(string $modelName): string
    {
        $model = str_replace('/', '\\', trim($modelName, '/'));
        
        if (!Str::startsWith($model, '\\')) {
            $model = '\\App\\Models\\' . $model;
        }

        return ltrim($model, '\\');
    }

    public function getClassNameFromFile(\SplFileInfo $file): ?string
    {
        $contents = file_get_contents($file->getPathname());
        
        // Extract namespace
        $namespace = '';
        if (preg_match('/namespace\s+([^;]+);/', $contents, $matches)) {
            $namespace = $matches[1];
        }

        // Extract class name
        if (preg_match('/class\s+(\w+)(?:\s+extends\s+[^{]+)?(?:\s+implements\s+[^{]+)?/', $contents, $matches)) {
            $className = $matches[1];
            return $namespace ? $namespace . '\\' . $className : $className;
        }

        return null;
    }

    private function isRelationshipMethod(\ReflectionMethod $method, Model $model): bool
    {
        if (!$method->isPublic() || $method->isStatic()) {
            return false;
        }

        try {
            $return = $method->invoke($model);
            return $return instanceof \Illuminate\Database\Eloquent\Relations\Relation;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function getRelationType(\ReflectionMethod $method, Model $model): string
    {
        $return = $method->invoke($model);
        $type = class_basename(get_class($return));
        return strtolower($type);
    }

    private function getRelatedModel(\ReflectionMethod $method, Model $model): string
    {
        $return = $method->invoke($model);
        return get_class($return->getRelated());
    }
}
