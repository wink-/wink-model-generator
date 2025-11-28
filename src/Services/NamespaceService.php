<?php

declare(strict_types=1);

namespace Wink\ModelGenerator\Services;

use Illuminate\Support\Str;
use Wink\ModelGenerator\Exceptions\InvalidInputException;

class NamespaceService
{
    private FileService $fileService;

    public function __construct(FileService $fileService)
    {
        $this->fileService = $fileService;
    }

    /**
     * Get the base namespace for a given type from config.
     */
    public function getBaseNamespace(string $type): string
    {
        return match ($type) {
            'models' => config('model-generator.model_namespace', 'App\\Models').'\\GeneratedModels',
            'factories' => config('model-generator.factory_namespace', 'Database\\Factories').'\\GeneratedFactories',
            'observers' => config('model-generator.observer_namespace', 'App\\Observers').'\\GeneratedObservers',
            default => 'App',
        };
    }

    /**
     * Get the base path for a given type from config.
     */
    public function getBasePath(string $type): string
    {
        return match ($type) {
            'models' => config('model-generator.model_path', app_path('Models')).'/GeneratedModels',
            'factories' => config('model-generator.factory_path', database_path('factories')).'/GeneratedFactories',
            'observers' => config('model-generator.observer_path', app_path('Observers')).'/GeneratedObservers',
            default => app_path(),
        };
    }

    public function getCurrentNamespace(string $filePath): ?string
    {
        $content = $this->fileService->get($filePath);
        if (preg_match('/namespace\s+([^;]+);/', $content, $matches)) {
            return $matches[1];
        }

        return null;
    }

    public function determineExpectedNamespace(string $filePath): string
    {
        // Normalize path separators
        $filePath = str_replace('\\', '/', $filePath);
        $basePath = str_replace('\\', '/', base_path());

        // Remove base path to get relative path
        $relativePath = Str::after($filePath, $basePath.'/');

        // Handle common Laravel paths
        if (Str::startsWith($relativePath, 'app/')) {
            $relativePath = Str::after($relativePath, 'app/');
            $namespace = 'App';
        } else {
            // For other paths, assume PSR-4 autoloading
            $namespace = 'Wink\\ModelGenerator';
        }

        // Get directory path without filename
        $directory = dirname($relativePath);

        // Convert directory separators to namespace separators
        if ($directory !== '.') {
            $namespaceAddition = str_replace('/', '\\', $directory);
            $namespace .= '\\'.$namespaceAddition;
        }

        return $namespace;
    }

    public function fixNamespace(string $filePath, string $currentNamespace, string $correctNamespace): void
    {
        $content = $this->fileService->get($filePath);

        // Create backup
        $this->fileService->writeFile($filePath.'.bak', $content);

        // Replace namespace
        $newContent = preg_replace(
            '/namespace\s+'.preg_quote($currentNamespace).';/',
            'namespace '.$correctNamespace.';',
            $content
        );

        if ($newContent === null) {
            throw new InvalidInputException("Failed to update namespace in file: {$filePath}");
        }

        $this->fileService->writeFile($filePath, $newContent);
    }
}
