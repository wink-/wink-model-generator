<?php

declare(strict_types=1);

namespace Wink\ModelGenerator\Models\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Wink\ModelGenerator\Services\FileService;
use Wink\ModelGenerator\Services\NamespaceService;
use Wink\ModelGenerator\Exceptions\InvalidInputException;

class ValidateModelNamespaces extends Command
{
    protected $signature = 'wink:validate-namespaces
                         {--directory= : Directory containing models to validate}
                         {--fix : Automatically fix incorrect namespaces}';

    protected $description = 'Validate and optionally fix model namespaces according to PSR-4';

    private FileService $fileService;
    private NamespaceService $namespaceService;

    public function __construct(FileService $fileService, NamespaceService $namespaceService)
    {
        parent::__construct();
        $this->fileService = $fileService;
        $this->namespaceService = $namespaceService;
    }

    public function handle(): int
    {
        try {
            $directory = $this->option('directory') ?: app_path('Models');
            $shouldFix = $this->option('fix') === true;

            if (!$this->fileService->isDirectory($directory)) {
                throw new InvalidInputException('Directory not found: ' . $directory);
            }

            $this->info("Scanning directory: {$directory}");
            $this->validateModels($directory, $shouldFix);

            return 0;
        } catch (InvalidInputException $e) {
            $this->error('Error: ' . $e->getMessage());
            return 1;
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            return 1;
        }
    }

    private function validateModels(string $directory, bool $shouldFix): void
    {
        $files = $this->fileService->getPhpFiles($directory);
        $issuesFound = false;

        foreach ($files as $file) {
            $filePath = $file->getPathname();
            $namespace = $this->namespaceService->getCurrentNamespace($filePath);
            
            if ($namespace === null) {
                continue; // Skip files without namespace
            }

            $expectedNamespace = $this->namespaceService->determineExpectedNamespace($filePath);

            if ($namespace !== $expectedNamespace) {
                $issuesFound = true;
                $this->line("");
                $normalizedPath = str_replace('\\', '/', $filePath);
                $this->warn("Namespace mismatch in: " . $normalizedPath);
                $this->line("Current:  {$namespace}");
                $this->line("Expected: {$expectedNamespace}");

                if ($shouldFix) {
                    try {
                        $this->namespaceService->fixNamespace($filePath, $namespace, $expectedNamespace);
                        $this->info("Fixed namespace in: " . str_replace('\\', '/', $filePath));
                    } catch (InvalidInputException $e) {
                        $this->error("Failed to fix namespace: " . $e->getMessage());
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
}
