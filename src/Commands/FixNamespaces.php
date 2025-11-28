<?php

declare(strict_types=1);

namespace Wink\ModelGenerator\Commands;

use Illuminate\Console\Command;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Wink\ModelGenerator\Services\NamespaceService;

class FixNamespaces extends Command
{
    protected $signature = 'wink:fix-namespaces
                          {path? : Root directory to scan (defaults based on type)}
                          {--type=models : Type of files to fix (models, factories, observers, any)}
                          {--connection= : Connection name to include under Generated* segments}
                          {--dry-run : Only print intended changes, do not write}
                          {--verbose : Show detailed per-file logs}';

    protected $description = 'Fix PHP namespace declarations to match PSR-4 and configured base namespaces';

    private NamespaceService $namespaceService;

    /** @var array<string, array{base_namespace: string, base_path: string}> */
    private array $typeConfig;

    public function __construct(NamespaceService $namespaceService)
    {
        parent::__construct();
        $this->namespaceService = $namespaceService;
    }

    public function handle(): int
    {
        $this->initializeTypeConfig();

        $type = $this->option('type');
        $path = $this->argument('path');
        $connection = $this->option('connection');
        $dryRun = $this->option('dry-run') === true;
        $verbose = $this->option('verbose') === true;

        if (! in_array($type, ['models', 'factories', 'observers', 'any'])) {
            $this->error("Invalid type '{$type}'. Must be one of: models, factories, observers, any");

            return 1;
        }

        $rootPath = $this->resolveRootPath($type, $path);

        if (! is_dir($rootPath)) {
            $this->error("Directory not found: {$rootPath}");

            return 1;
        }

        $this->info('Scanning directory: '.$rootPath);
        if ($dryRun) {
            $this->warn('Running in dry-run mode - no files will be modified');
        }

        $filesProcessed = 0;
        $filesFixed = 0;
        $errors = 0;

        $phpFiles = $this->getPhpFiles($rootPath);

        foreach ($phpFiles as $file) {
            $filePath = $file->getPathname();
            $filesProcessed++;

            try {
                $result = $this->processFile($filePath, $rootPath, $type, $connection, $dryRun, $verbose);
                if ($result) {
                    $filesFixed++;
                }
            } catch (\Exception $e) {
                $this->error("Error processing {$filePath}: ".$e->getMessage());
                $errors++;
            }
        }

        $this->newLine();
        $this->info("Files processed: {$filesProcessed}");
        $this->info('Files '.($dryRun ? 'would be ' : '')."fixed: {$filesFixed}");
        if ($errors > 0) {
            $this->warn("Errors encountered: {$errors}");
        }

        return $errors > 0 ? 1 : 0;
    }

    private function initializeTypeConfig(): void
    {
        $this->typeConfig = [
            'models' => [
                'base_namespace' => $this->namespaceService->getBaseNamespace('models'),
                'base_path' => $this->namespaceService->getBasePath('models'),
            ],
            'factories' => [
                'base_namespace' => $this->namespaceService->getBaseNamespace('factories'),
                'base_path' => $this->namespaceService->getBasePath('factories'),
            ],
            'observers' => [
                'base_namespace' => $this->namespaceService->getBaseNamespace('observers'),
                'base_path' => $this->namespaceService->getBasePath('observers'),
            ],
        ];
    }

    private function resolveRootPath(string $type, ?string $path): string
    {
        if ($path !== null) {
            return $this->resolveAbsolutePath($path);
        }

        return match ($type) {
            'models' => $this->typeConfig['models']['base_path'],
            'factories' => $this->typeConfig['factories']['base_path'],
            'observers' => $this->typeConfig['observers']['base_path'],
            'any' => app_path(),
        };
    }

    private function resolveAbsolutePath(string $path): string
    {
        if (str_starts_with($path, '/') || (DIRECTORY_SEPARATOR === '\\' && preg_match('/^[A-Z]:/i', $path))) {
            return $path;
        }

        return base_path($path);
    }

    /**
     * @return SplFileInfo[]
     */
    private function getPhpFiles(string $directory): array
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        $files = [];
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = new SplFileInfo($file->getPathname());
            }
        }

        return $files;
    }

    private function processFile(
        string $filePath,
        string $rootPath,
        string $type,
        ?string $connection,
        bool $dryRun,
        bool $verbose
    ): bool {
        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new \RuntimeException("Could not read file: {$filePath}");
        }

        $currentNamespace = $this->extractNamespace($content);
        $expectedNamespace = $this->computeExpectedNamespace($filePath, $rootPath, $type, $connection);

        if ($currentNamespace === $expectedNamespace) {
            if ($verbose) {
                $this->line("OK: {$filePath}");
            }

            return false;
        }

        $this->line('');
        $this->warn("Namespace mismatch in: {$filePath}");
        $this->line('  Current:  '.($currentNamespace ?? '(none)'));
        $this->line("  Expected: {$expectedNamespace}");

        if (! $dryRun) {
            $newContent = $this->updateNamespace($content, $currentNamespace, $expectedNamespace);
            if (file_put_contents($filePath, $newContent) === false) {
                throw new \RuntimeException("Could not write file: {$filePath}");
            }
            $this->info("  Fixed: {$filePath}");
        } else {
            $this->info("  Would fix: {$filePath}");
        }

        return true;
    }

    private function extractNamespace(string $content): ?string
    {
        if (preg_match('/namespace\s+([^;]+);/', $content, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    private function computeExpectedNamespace(
        string $filePath,
        string $rootPath,
        string $type,
        ?string $connection
    ): string {
        // Normalize paths
        $filePath = str_replace('\\', '/', $filePath);
        $rootPath = rtrim(str_replace('\\', '/', $rootPath), '/');

        // Get relative path from root - ensure we're properly handling the path
        $relativePath = $filePath;
        if (str_starts_with($filePath, $rootPath.'/')) {
            $relativePath = substr($filePath, strlen($rootPath) + 1);
        }
        $relativeDir = dirname($relativePath);

        // Determine base namespace based on type
        $baseNamespace = $this->determineBaseNamespace($rootPath, $type);

        // Build namespace from directory structure
        $namespace = $baseNamespace;

        // If connection is specified and not already in the path, add it after Generated* segment
        if ($connection !== null) {
            $connectionSegment = ucfirst($connection);
            $namespace = $this->ensureConnectionSegment($namespace, $connectionSegment, $type);
        }

        // Add subdirectory segments
        if ($relativeDir !== '.') {
            $segments = explode('/', $relativeDir);
            $segments = array_map(fn ($s) => ucfirst($s), $segments);
            $segments = array_filter($segments, fn ($s) => $s !== '');

            // If we added a connection segment, we need to check if these segments duplicate it
            if ($connection !== null) {
                $connectionSegment = ucfirst($connection);
                // Remove connection segment if it's the first segment (to avoid duplication)
                if (! empty($segments) && strcasecmp($segments[0], $connectionSegment) === 0) {
                    array_shift($segments);
                }
            }

            if (! empty($segments)) {
                $namespace .= '\\'.implode('\\', $segments);
            }
        }

        return $namespace;
    }

    private function determineBaseNamespace(string $rootPath, string $type): string
    {
        // Normalize root path
        $normalizedRoot = str_replace('\\', '/', $rootPath);

        // Check if the root path is under one of our configured base paths
        if ($type !== 'any') {
            $configBasePath = str_replace('\\', '/', $this->typeConfig[$type]['base_path']);
            if (str_starts_with($normalizedRoot, $configBasePath)) {
                return $this->typeConfig[$type]['base_namespace'];
            }
        }

        // For custom paths or 'any' type, compute namespace from Laravel's standard structure
        $basePath = str_replace('\\', '/', base_path());

        if (str_starts_with($normalizedRoot, $basePath.'/app/')) {
            $relativePath = str_replace($basePath.'/app/', '', $normalizedRoot);

            return 'App'.($relativePath ? '\\'.$this->pathToNamespace($relativePath) : '');
        }

        if (str_starts_with($normalizedRoot, $basePath.'/database/factories/')) {
            $relativePath = str_replace($basePath.'/database/factories/', '', $normalizedRoot);

            return 'Database\\Factories'.($relativePath ? '\\'.$this->pathToNamespace($relativePath) : '');
        }

        // Fall back to computing from path relative to base
        $relativePath = str_replace($basePath.'/', '', $normalizedRoot);

        return $this->pathToNamespace($relativePath);
    }

    /**
     * Convert a path to a properly cased namespace segment.
     */
    private function pathToNamespace(string $path): string
    {
        $segments = explode('/', $path);
        $segments = array_map(fn ($s) => ucfirst($s), $segments);
        $segments = array_filter($segments, fn ($s) => $s !== '');

        return implode('\\', $segments);
    }

    private function ensureConnectionSegment(string $namespace, string $connectionSegment, string $type): string
    {
        // Check if the namespace already ends with the connection segment
        if (str_ends_with($namespace, '\\'.$connectionSegment)) {
            return $namespace;
        }

        // Check if this is a Generated* namespace where we should add the connection
        $generatedPatterns = [
            'GeneratedModels',
            'GeneratedFactories',
            'GeneratedObservers',
        ];

        foreach ($generatedPatterns as $pattern) {
            if (str_contains($namespace, $pattern)) {
                // Check if connection segment is already after Generated*
                $afterGenerated = substr($namespace, strpos($namespace, $pattern) + strlen($pattern));
                if (str_starts_with($afterGenerated, '\\'.$connectionSegment)) {
                    return $namespace;
                }

                // Add connection segment after Generated*
                $parts = explode('\\'.$pattern, $namespace);
                if (count($parts) === 2) {
                    return $parts[0].'\\'.$pattern.'\\'.$connectionSegment.$parts[1];
                }
            }
        }

        // If no Generated* pattern found, just append connection to namespace
        return $namespace.'\\'.$connectionSegment;
    }

    private function updateNamespace(string $content, ?string $currentNamespace, string $expectedNamespace): string
    {
        if ($currentNamespace !== null) {
            // Replace existing namespace
            return preg_replace(
                '/namespace\s+'.preg_quote($currentNamespace, '/').'\s*;/',
                'namespace '.$expectedNamespace.';',
                $content
            ) ?? $content;
        }

        // Insert namespace after <?php and optional declare statement
        $insertPos = 0;
        $insertPrefix = "\n";

        // Find position after <?php
        if (preg_match('/<\?php\s*/', $content, $matches, PREG_OFFSET_CAPTURE)) {
            $insertPos = $matches[0][1] + strlen($matches[0][0]);
        }

        // Find position after declare(strict_types=1); if present
        if (preg_match('/declare\s*\(\s*strict_types\s*=\s*1\s*\)\s*;/', $content, $matches, PREG_OFFSET_CAPTURE)) {
            $insertPos = $matches[0][1] + strlen($matches[0][0]);
            $insertPrefix = "\n\n";
        }

        return substr($content, 0, $insertPos).$insertPrefix.'namespace '.$expectedNamespace.';'."\n".substr($content, $insertPos);
    }
}
