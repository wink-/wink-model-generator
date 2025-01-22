<?php

declare(strict_types=1);

namespace Wink\ModelGenerator\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Wink\ModelGenerator\Config\GeneratorConfig;
use Wink\ModelGenerator\Database\MySqlSchemaReader;
use Wink\ModelGenerator\Database\SchemaReader;
use Wink\ModelGenerator\Database\SqliteSchemaReader;
use Wink\ModelGenerator\Generators\ModelGenerator;
use Wink\ModelGenerator\Generators\FactoryGenerator;
use RuntimeException;

class GenerateModels extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wink:generate-models 
                          {--connection=sqlite : Database connection to use}
                          {--directory= : Full path where models should be generated}
                          {--factory-directory= : Full path where factories should be generated}
                          {--with-relationships : Generate relationship methods}
                          {--with-factories : Generate model factories}
                          {--with-rules : Generate validation rules}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Eloquent models from your database schema';

    private GeneratorConfig $config;
    private SchemaReader $schemaReader;
    private ModelGenerator $modelGenerator;
    private FactoryGenerator $factoryGenerator;

    public function __construct(GeneratorConfig $config)
    {
        parent::__construct();
        $this->config = $config;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $connection = $this->option('connection');
            $directory = $this->option('directory');
            $factoryDirectory = $this->option('factory-directory');
            
            $this->initializeGenerators($connection);
            $this->displayStartupInfo($connection, $directory, $factoryDirectory);
            
            // Use the provided directory path or create one based on connection name
            $baseDir = $directory ?: $this->getDefaultDirectory($connection);
            $this->createDirectory($baseDir);

            // Get the tables
            $tables = $this->schemaReader->getTables($connection, $this->config->getExcludedTables());

            if (empty($tables)) {
                $this->error('No tables found in database.');
                return 1;
            }

            $this->info('Processing tables...');
            $this->generateModels($tables, $connection, $baseDir);

            if ($this->option('with-factories')) {
                $this->info("Generating factories...");
                $factoryBaseDir = $factoryDirectory ?: $this->getDefaultFactoryDirectory($connection);
                $this->createDirectory($factoryBaseDir);
                $this->generateFactories($tables, $connection, $factoryBaseDir);
            }

            $this->info('Model generation completed successfully.');
            return 0;
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            $this->error("Stack trace: " . $e->getTraceAsString());
            return 1;
        }
    }

    private function getDefaultDirectory(string $connection): string
    {
        return app_path('Models/GeneratedModels/' . $connection);
    }

    private function getDefaultFactoryDirectory(string $connection): string
    {
        return database_path('factories/GeneratedFactories/' . $connection);
    }

    private function initializeGenerators(string $connection): void
    {
        $driver = config("database.connections.{$connection}.driver") ?? 'sqlite';
        
        $this->schemaReader = match ($driver) {
            'sqlite' => new SqliteSchemaReader(),
            'mysql' => new MySqlSchemaReader(),
            default => throw new RuntimeException("Unsupported database driver: {$driver}")
        };

        $this->modelGenerator = new ModelGenerator($this->config);
        $this->factoryGenerator = new FactoryGenerator($this->config);
    }

    private function displayStartupInfo(string $connection, ?string $directory, ?string $factoryDirectory): void
    {
        $this->info('Starting model generation with:');
        $this->info("Connection: $connection");
        $this->info("Directory: " . ($directory ?: $this->getDefaultDirectory($connection)));
        if ($this->option('with-factories')) {
            $this->info("Factory Directory: " . ($factoryDirectory ?: $this->getDefaultFactoryDirectory($connection)));
        }
        $this->info("With Relationships: " . ($this->option('with-relationships') ? 'yes' : 'no'));
        $this->info("With Factories: " . ($this->option('with-factories') ? 'yes' : 'no'));
        $this->info("With Rules: " . ($this->option('with-rules') ? 'yes' : 'no'));
    }

    private function createDirectory(string $path): void
    {
        if (!File::isDirectory($path)) {
            File::makeDirectory($path, 0755, true);
        }
    }

    private function generateModels(array $tables, string $connection, string $baseDir): void
    {
        foreach ($tables as $table) {
            $tableName = $table->name;
            $modelName = Str::studly(Str::singular($tableName));
            
            $this->info("Processing table: $tableName -> $modelName");
            
            $columns = $this->schemaReader->getTableColumns($connection, $tableName);
            $this->info("Found " . count($columns) . " columns");

            $foreignKeys = [];
            if ($this->option('with-relationships')) {
                $foreignKeys = $this->schemaReader->getForeignKeys($connection, $tableName);
                $this->info("Found " . count($foreignKeys) . " relationships");
            }

            $modelContent = $this->modelGenerator->generate(
                $modelName,
                $tableName,
                $connection,
                $columns,
                $foreignKeys,
                $this->option('with-relationships'),
                $this->option('with-rules')
            );

            $modelPath = $baseDir . '/' . $modelName . '.php';
            File::put($modelPath, $modelContent);
            $this->info("Model file created: $modelPath");
        }
    }

    private function generateFactories(array $tables, string $connection, string $baseDir): void
    {
        foreach ($tables as $table) {
            $tableName = $table->name;
            $modelName = Str::studly(Str::singular($tableName));
            
            $columns = $this->schemaReader->getTableColumns($connection, $tableName);
            $factoryContent = $this->factoryGenerator->generate($modelName, $columns);

            $factoryPath = $baseDir . '/' . $modelName . 'Factory.php';
            File::put($factoryPath, $factoryContent);
            $this->info("Factory {$modelName}Factory created at {$factoryPath}");
        }
    }
}