<?php

declare(strict_types=1);

namespace Wink\ModelGenerator\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;

class ValidateModelNamespaces extends Command
{
    protected $signature = 'wink:validate-namespaces
                         {--directory= : Directory containing models to validate}
                         {--fix : Automatically fix incorrect namespaces}';

    protected $description = 'Validate and optionally fix model namespaces according to PSR-4';

    public function handle(): int
    {
        try {
            $directory = $this->option('directory') ?: app_path('Models');
            $shouldFix = $this->option('fix') === true;

            if (!File::isDirectory($directory)) {
                throw new RuntimeException("Directory not found: {$directory}");
            }

            $this->info("Scanning directory: {$directory}");
            $this->validateModels($directory, $shouldFix);

            return 0;
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            return 1;
        }
    }

    private function validateModels(string $directory, bool $shouldFix): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory)
        );

        $issuesFound = false;

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $namespace = $this->getCurrentNamespace($file->getPathname());
                if ($namespace === null) {
                    continue; // Skip files without namespace
                }

                $expectedNamespace = $this->determineExpectedNamespace($file->getPathname());

                if ($namespace !== $expectedNamespace) {
                    $issuesFound = true;
                    $this->line("");
                    $normalizedPath = str_replace('\\', '/', $file->getPathname());
                    $this->warn("Namespace mismatch in: " . $normalizedPath);
                    $this->line("Current:  {$namespace}");
                    $this->line("Expected: {$expectedNamespace}");

                    if ($shouldFix) {
                        $this->fixNamespace($file->getPathname(), $namespace, $expectedNamespace);
                        $this->info("Fixed namespace in: " . str_replace('\\', '/', $file->getPathname()));
                    }
                }
            }
        }

        if (!$issuesFound) {
            $this->info("All model namespaces are correct!");
        } elseif (!$shouldFix) {
            $this->line("");
            $this->info("Run with --fix option to automatically correct namespaces");
        }
    }

    private function getCurrentNamespace(string $filePath): ?string
    {
        $content = file_get_contents($filePath);
        if (preg_match('/namespace\s+([^;]+);/', $content, $matches)) {
            return $matches[1];
        }
        return null;
    }

    private function determineExpectedNamespace(string $filePath): string
    {
        // Normalize path separators
        $filePath = str_replace('\\', '/', $filePath);
        $basePath = str_replace('\\', '/', base_path());

        // Remove base path to get relative path
        $relativePath = Str::after($filePath, $basePath . '/');

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
            $namespace .= '\\' . $namespaceAddition;
        }

        return $namespace;
    }

    private function fixNamespace(string $filePath, string $currentNamespace, string $correctNamespace): void
    {
        $content = file_get_contents($filePath);

        // Create backup
        file_put_contents($filePath . '.bak', $content);

        // Replace namespace
        $newContent = preg_replace(
            '/namespace\s+' . preg_quote($currentNamespace) . ';/',
            'namespace ' . $correctNamespace . ';',
            $content
        );

        file_put_contents($filePath, $newContent);
    }
}
