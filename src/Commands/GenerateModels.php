<?php

declare(strict_types=1);

namespace Wink\ModelGenerator\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use Wink\ModelGenerator\Config\GeneratorConfig;
use Wink\ModelGenerator\Database\MySqlSchemaReader;
use Wink\ModelGenerator\Database\PostgreSqlSchemaReader;
use Wink\ModelGenerator\Database\SchemaReader;
use Wink\ModelGenerator\Database\SqliteSchemaReader;
use Wink\ModelGenerator\Generators\FactoryGenerator;
use Wink\ModelGenerator\Generators\ModelGenerator;
use Wink\ModelGenerator\Generators\ObserverGenerator;
use Wink\ModelGenerator\Services\FileService;

class GenerateModels extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wink:generate-models 
                          {--connection=sqlite : Database connection to use}
                          {--directory= : Path where models should be generated (absolute or relative to project root)}
                          {--factory-directory= : Path where factories should be generated (absolute or relative to project root)}
                          {--observer-directory= : Path where observers should be generated (absolute or relative to project root)}
                          {--with-relationships : Generate relationship methods}
                          {--with-factories : Generate model factories}
                          {--with-observers : Generate model observers}
                          {--with-rules : Generate validation rules}
                          {--with-scopes : Generate query scopes}
                          {--with-timestamp-scopes : Generate timestamp-based scopes}
                          {--with-events : Generate model event methods}
                          {--with-boot-method : Generate boot method for event registration}';

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

    private ObserverGenerator $observerGenerator;

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
            $connection = (string) $this->option('connection');
            $directory = $this->option('directory') ? (string) $this->option('directory') : null;
            $factoryDirectory = $this->option('factory-directory') ? (string) $this->option('factory-directory') : null;
            $observerDirectory = $this->option('observer-directory') ? (string) $this->option('observer-directory') : null;

            $this->validateInputs($connection, $directory, $factoryDirectory, $observerDirectory);
            $this->overrideConfigForScopes();
            $this->initializeGenerators($connection);
            $this->displayStartupInfo($connection, $directory, $factoryDirectory, $observerDirectory);

            // Use the provided directory path or create one based on connection name
            $baseDir = $directory ? $this->resolveDirectoryPath($directory) : $this->getDefaultDirectory($connection);
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
                $this->info('Generating factories...');
                $factoryBaseDir = $factoryDirectory ? $this->resolveDirectoryPath($factoryDirectory) : $this->getDefaultFactoryDirectory($connection);
                $this->createDirectory($factoryBaseDir);
                $this->generateFactories($tables, $connection, $factoryBaseDir);
            }

            if ($this->option('with-observers')) {
                $this->info('Generating observers...');
                $observerBaseDir = $observerDirectory ? $this->resolveDirectoryPath($observerDirectory) : $this->getDefaultObserverDirectory($connection);
                $this->createDirectory($observerBaseDir);
                $this->generateObservers($tables, $connection, $observerBaseDir);
            }

            $this->info('Model generation completed successfully.');

            return 0;
        } catch (\InvalidArgumentException $e) {
            $this->error('Invalid argument: '.$e->getMessage());

            return 1;
        } catch (RuntimeException $e) {
            $this->error('Runtime error: '.$e->getMessage());

            return 1;
        } catch (\PDOException $e) {
            $this->error('Database connection failed: '.$e->getMessage());
            $this->error('Please check your database configuration.');

            return 1;
        } catch (\Exception $e) {
            $this->error('Unexpected error: '.$e->getMessage());
            if ($this->option('verbose')) {
                $this->error('Stack trace: '.$e->getTraceAsString());
            }

            return 1;
        }
    }

    private function validateInputs(string $connection, ?string $directory, ?string $factoryDirectory, ?string $observerDirectory): void
    {
        // Validate connection exists
        $connections = config('database.connections');
        if (! isset($connections[$connection])) {
            throw new \InvalidArgumentException("Database connection '{$connection}' is not configured.");
        }

        // Validate driver is supported
        $driver = config("database.connections.{$connection}.driver");
        if (! in_array($driver, ['sqlite', 'mysql', 'pgsql'])) {
            throw new \InvalidArgumentException("Database driver '{$driver}' is not supported. Supported drivers: sqlite, mysql, pgsql");
        }

        // Validate directories are writable
        if ($directory) {
            $absolutePath = $this->resolveDirectoryPath($directory);
            $this->validateDirectoryIsWritable($absolutePath, $directory, 'models');
        }

        if ($factoryDirectory) {
            $absolutePath = $this->resolveDirectoryPath($factoryDirectory);
            $this->validateDirectoryIsWritable($absolutePath, $factoryDirectory, 'factories');
        }

        if ($observerDirectory) {
            $absolutePath = $this->resolveDirectoryPath($observerDirectory);
            $this->validateDirectoryIsWritable($absolutePath, $observerDirectory, 'observers');
        }
    }

    private function resolveDirectoryPath(string $directory): string
    {
        // If it's already an absolute path, return it
        if (str_starts_with($directory, '/') || (DIRECTORY_SEPARATOR === '\\' && preg_match('/^[A-Z]:/i', $directory))) {
            return $directory;
        }

        // Otherwise, resolve it relative to the Laravel base path
        return base_path($directory);
    }

    private function validateDirectoryIsWritable(string $absolutePath, string $originalPath, string $type): void
    {
        // Find the first existing parent directory
        $checkPath = $absolutePath;
        while (!file_exists($checkPath) && $checkPath !== dirname($checkPath)) {
            $checkPath = dirname($checkPath);
        }

        if (!is_writable($checkPath)) {
            throw new \InvalidArgumentException(
                "Cannot create {$type} directory '{$originalPath}' - parent directory '{$checkPath}' is not writable.\n".
                "Please use either:\n".
                "  - Absolute path: --directory=/full/path/to/{$type}\n".
                "  - Path relative to project root: --directory=app/Models/Your" . ucfirst($type)
            );
        }
    }

    private function getDefaultDirectory(string $connection): string
    {
        return app_path('Models/GeneratedModels/'.$connection);
    }

    private function getDefaultFactoryDirectory(string $connection): string
    {
        return database_path('factories/GeneratedFactories/'.$connection);
    }

    private function getDefaultObserverDirectory(string $connection): string
    {
        return app_path('Observers/GeneratedObservers/'.$connection);
    }

    private function initializeGenerators(string $connection): void
    {
        $driver = config("database.connections.{$connection}.driver");
        $this->schemaReader = match ($driver) {
            'sqlite' => new SqliteSchemaReader,
            'mysql' => new MySqlSchemaReader,
            'pgsql' => new PostgreSqlSchemaReader,
            default => throw new RuntimeException("Unsupported database driver: {$driver}")
        };

        $fileService = app(FileService::class);
        $this->modelGenerator = new ModelGenerator($this->config, $fileService);
        $this->factoryGenerator = new FactoryGenerator($this->config);
        $this->observerGenerator = new ObserverGenerator($this->config);
    }

    private function displayStartupInfo(string $connection, ?string $directory, ?string $factoryDirectory, ?string $observerDirectory): void
    {
        $this->info('Starting model generation with:');
        $this->info("Connection: $connection");
        $this->info('Directory: '.($directory ?: $this->getDefaultDirectory($connection)));
        if ($this->option('with-factories')) {
            $this->info('Factory Directory: '.($factoryDirectory ?: $this->getDefaultFactoryDirectory($connection)));
        }
        if ($this->option('with-observers')) {
            $this->info('Observer Directory: '.($observerDirectory ?: $this->getDefaultObserverDirectory($connection)));
        }
        $this->info('With Relationships: '.($this->option('with-relationships') ? 'yes' : 'no'));
        $this->info('With Factories: '.($this->option('with-factories') ? 'yes' : 'no'));
        $this->info('With Observers: '.($this->option('with-observers') ? 'yes' : 'no'));
        $this->info('With Rules: '.($this->option('with-rules') ? 'yes' : 'no'));
        $this->info('With Scopes: '.($this->option('with-scopes') ? 'yes' : 'no'));
        $this->info('With Timestamp Scopes: '.($this->option('with-timestamp-scopes') ? 'yes' : 'no'));
        $this->info('With Events: '.($this->option('with-events') ? 'yes' : 'no'));
        $this->info('With Boot Method: '.($this->option('with-boot-method') ? 'yes' : 'no'));
    }

    private function overrideConfigForScopes(): void
    {
        $properties = $this->config->getModelProperties();

        // Override scope generation based on command options
        if ($this->option('with-scopes')) {
            $properties['auto_generate_scopes'] = true;
        }

        if ($this->option('with-timestamp-scopes')) {
            $properties['auto_generate_timestamp_scopes'] = true;
        }

        if ($this->option('with-events')) {
            $properties['generate_event_methods'] = true;
        }

        if ($this->option('with-boot-method')) {
            $properties['generate_boot_method'] = true;
        }

        // Create new config with overridden properties
        $this->config = new GeneratorConfig(['model_properties' => $properties]);
    }

    private function createDirectory(string $path): void
    {
        if (! File::isDirectory($path)) {
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
            $this->info('Found '.count($columns).' columns');

            $foreignKeys = [];
            if ($this->option('with-relationships')) {
                $foreignKeys = $this->schemaReader->getForeignKeys($connection, $tableName);
                $this->info('Found '.count($foreignKeys).' relationships');
            }

            // Build the full model name with namespace path
            $namespace = $this->buildModelNamespace($connection);
            $relativeNamespace = str_replace('App\\Models\\', '', $namespace);
            $fullModelName = $relativeNamespace ? $relativeNamespace.'\\'.$modelName : $modelName;

            $modelContent = $this->modelGenerator->generate(
                $fullModelName,
                $tableName,
                $connection,
                $columns,
                $foreignKeys,
                $this->option('with-relationships'),
                $this->option('with-rules'),
                $this->option('with-events')
            );

            $modelPath = $baseDir.'/'.$modelName.'.php';
            File::put($modelPath, $modelContent);
            $this->info("Model file created: $modelPath");
        }
    }

    private function generateFactories(array $tables, string $connection, string $baseDir): void
    {
        foreach ($tables as $table) {
            $tableName = $table->name;
            $modelName = Str::studly(Str::singular($tableName));

            // Build the full model name with namespace path
            $namespace = $this->buildModelNamespace($connection);
            $relativeNamespace = str_replace('App\\Models\\', '', $namespace);
            $fullModelName = $relativeNamespace ? $relativeNamespace.'\\'.$modelName : $modelName;

            $columns = $this->schemaReader->getTableColumns($connection, $tableName);
            $factoryContent = $this->factoryGenerator->generate($fullModelName, $columns);

            $factoryPath = $baseDir.'/'.$modelName.'Factory.php';
            File::put($factoryPath, $factoryContent);
            $this->info("Factory {$modelName}Factory created at {$factoryPath}");
        }
    }

    private function generateObservers(array $tables, string $connection, string $baseDir): void
    {
        foreach ($tables as $table) {
            $tableName = $table->name;
            $modelName = Str::studly(Str::singular($tableName));

            $this->info("Generating observer for: $tableName -> {$modelName}Observer");

            $columns = $this->schemaReader->getTableColumns($connection, $tableName);
            $hasSoftDeletes = $this->detectSoftDeletes($columns);
            $modelNamespace = $this->buildModelNamespace($connection);

            $observerContent = $this->observerGenerator->generate(
                $modelName,
                $modelNamespace,
                $connection,
                $columns,
                $hasSoftDeletes
            );

            $observerPath = $baseDir.'/'.$modelName.'Observer.php';
            File::put($observerPath, $observerContent);
            $this->info("Observer {$modelName}Observer created at {$observerPath}");
        }
    }

    private function detectSoftDeletes(array $columns): bool
    {
        if (! $this->config->getModelProperty('auto_detect_soft_deletes', true)) {
            return false;
        }

        foreach ($columns as $column) {
            if ($column->name === 'deleted_at') {
                return true;
            }
        }

        return false;
    }

    private function buildModelNamespace(string $connection): string
    {
        $baseNamespace = $this->config->getModelNamespace();

        // Check if connection-based organization is enabled
        if ($this->config->getModelProperty('connection_based_organization', true)) {
            return $baseNamespace.'\\GeneratedModels\\'.Str::studly($connection);
        }

        return $baseNamespace;
    }

    private function buildObserverNamespace(string $connection): string
    {
        $baseNamespace = $this->config->getObserverNamespace();

        if ($this->config->getObserverProperty('observer_connection_based', true)) {
            return $baseNamespace.'\\'.Str::studly($connection);
        }

        return $baseNamespace;
    }
}
