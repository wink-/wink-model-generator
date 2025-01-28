<?php

declare(strict_types=1);

namespace Wink\ModelGenerator\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Wink\ModelGenerator\Config\GeneratorConfig;
use Wink\ModelGenerator\Database\SchemaReader;
use Wink\ModelGenerator\Generators\ResourceGenerator;
use Wink\ModelGenerator\Services\FileService;
use Wink\ModelGenerator\Services\ModelService;
use Wink\ModelGenerator\Exceptions\ModelNotFoundException;
use Wink\ModelGenerator\Exceptions\InvalidInputException;

class GenerateResources extends Command
{
    protected $signature = 'wink:generate-resources
                         {--model= : Specific model to generate resources for}
                         {--directory= : Directory containing models to process}
                         {--collection : Generate collection resources as well}
                         {--output= : Directory where resources should be generated}';

    protected $description = 'Generate API resources from models';

    private GeneratorConfig $config;
    private ResourceGenerator $resourceGenerator;
    private SchemaReader $schemaReader;
    private FileService $fileService;
    private ModelService $modelService;

    public function __construct(
        GeneratorConfig $config,
        ResourceGenerator $resourceGenerator,
        SchemaReader $schemaReader,
        FileService $fileService,
        ModelService $modelService
    ) {
        parent::__construct();
        $this->config = $config;
        $this->resourceGenerator = $resourceGenerator;
        $this->schemaReader = $schemaReader;
        $this->fileService = $fileService;
        $this->modelService = $modelService;
    }

    public function handle(): int
    {
        try {
            $options = $this->validateAndGetOptions();
            $this->logGenerationStart($options);
            
            $this->fileService->prepareOutputDirectory($options['outputDir']);
            
            if ($options['model']) {
                $this->generateResourceForModel(
                    $options['model'],
                    $options['generateCollection'],
                    $options['outputDir']
                );
            } else {
                $this->generateResourcesForDirectory(
                    $options['directory'],
                    $options['generateCollection'],
                    $options['outputDir']
                );
            }

            $this->info('Resource generation completed successfully.');
            return 0;
        } catch (ModelNotFoundException $e) {
            $this->error($e->getMessage());
            return 1;
        } catch (InvalidInputException $e) {
            $this->error($e->getMessage());
            return 1;
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            return 1;
        }
    }

    private function validateAndGetOptions(): array
    {
        $model = $this->option('model');
        $directory = $this->option('directory');
        $generateCollection = $this->option('collection');
        $outputDir = $this->option('output') ?: $this->config->getResourcePath();

        if (!$model && !$directory) {
            throw new InvalidInputException('Either --model or --directory option must be provided');
        }

        if ($model && $directory) {
            throw new InvalidInputException('Cannot specify both --model and --directory options');
        }

        return [
            'model' => $model,
            'directory' => $directory,
            'generateCollection' => $generateCollection,
            'outputDir' => $outputDir,
        ];
    }

    private function logGenerationStart(array $options): void
    {
        $this->info("Starting resource generation...");
        $this->info("Model: " . ($options['model'] ?? 'None specified'));
        $this->info("Directory: " . ($options['directory'] ?? 'None specified'));
        $this->info("Generate Collection: " . ($options['generateCollection'] ? 'Yes' : 'No'));
        $this->info("Output Directory: " . $options['outputDir']);
    }

    private function generateResourceForModel(string $modelName, bool $generateCollection, string $outputDir): void
    {
        try {
            $this->info("Resolving model class for: {$modelName}");
            $modelClass = $this->modelService->resolveModelClass($modelName);
            $this->info("Found model class: {$modelClass}");

            $model = $this->modelService->createModelInstance($modelClass);
            $modelInfo = $this->modelService->getModelInfo($model);

            $this->info("Processing table: {$modelInfo['table']}");
            $this->info("Using connection: {$modelInfo['connection']}");

            $columns = $this->schemaReader->getTableColumns($modelInfo['connection'], $modelInfo['table']);
            $this->info("Retrieved " . count($columns) . " columns from table");

            $relationships = $this->modelService->getModelRelationships($model);
            $this->info("Found " . count($relationships) . " relationships");

            $this->generateAndSaveResource(
                $modelClass,
                $columns,
                $relationships,
                $outputDir,
                false
            );

            if ($generateCollection) {
                $this->generateAndSaveResource(
                    $modelClass,
                    $columns,
                    $relationships,
                    $outputDir,
                    true
                );
            }
        } catch (ModelNotFoundException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new RuntimeException(
                "Failed to generate resource for model {$modelName}: " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    private function generateAndSaveResource(
        string $modelClass,
        array $columns,
        array $relationships,
        string $outputDir,
        bool $isCollection
    ): void {
        $type = $isCollection ? 'collection' : 'resource';
        $this->info("Generating {$type} class");

        $content = $this->resourceGenerator->generate(
            $modelClass,
            $columns,
            $relationships,
            $isCollection
        );

        $filename = class_basename($modelClass) . ($isCollection ? 'Collection' : 'Resource') . '.php';
        $path = $outputDir . '/' . $filename;

        $this->fileService->writeFile($path, $content);
        $this->info("Generated {$type}: {$path}");
    }

    private function generateResourcesForDirectory(string $directory, bool $generateCollection, string $outputDir): void
    {
        if (!$this->fileService->isDirectory($directory)) {
            throw new InvalidInputException("Directory not found: {$directory}");
        }

        $this->info("Scanning directory: {$directory}");
        $files = $this->fileService->getPhpFiles($directory);

        $processedModels = 0;
        foreach ($files as $file) {
            try {
                $className = $this->modelService->getClassNameFromFile($file);
                if ($className && is_subclass_of($className, \Illuminate\Database\Eloquent\Model::class)) {
                    // Convert fully qualified class name to relative model name
                    $modelName = str_replace('App\\Models\\', '', $className);
                    $modelName = str_replace('\\', '/', $modelName);

                    $this->generateResourceForModel(
                        $modelName,
                        $generateCollection,
                        $outputDir
                    );
                    $processedModels++;
                }
            } catch (\Exception $e) {
                $this->warn("Skipping file {$file->getPathname()}: " . $e->getMessage());
            }
        }

        $this->info("Processed {$processedModels} models from directory");
    }
}
