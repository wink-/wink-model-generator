<?php
namespace Wink\ModelGenerator\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class GenerateModels extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-models 
                          {--connection=sqlite : Database connection to use}
                          {--directory= : Subdirectory for generated models}
                          {--with-relationships : Generate relationship methods}
                          {--with-factories : Generate model factories}
                          {--with-rules : Generate validation rules}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate models from SQLite database';

    /**
     * Default Laravel tables to exclude
     */
    private array $excludedTables = [
        'migrations',
        'failed_jobs',
        'password_reset_tokens',
        'personal_access_tokens',
        'sessions',
        'cache',
        'jobs',
        'cache_locks',
        'job_batches'
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $connection = $this->option('connection');
            $directory = $this->option('directory');
            
            // Create base directory for generated models
            $baseDir = app_path('Models/GeneratedModels');
            if ($directory) {
                $baseDir .= '/' . ltrim($directory, '/');
            }
            
            if (!File::isDirectory($baseDir)) {
                File::makeDirectory($baseDir, 0755, true);
            }

            $databaseName = config("database.connections.{$connection}.database");

            if (!file_exists($databaseName)) {
                $this->error("Database file {$databaseName} not found.");
                return 1;
            }

            // Replace the direct query with database-agnostic method
            $excludedTablesString = "'" . implode("','", $this->excludedTables) . "'";
            $tables = $this->getTables($connection, $excludedTablesString);

            foreach ($tables as $table) {
                $tableName = $table->name;
                $modelName = Str::studly(Str::singular($tableName));
                
                // Get columns based on connection type
                $columns = $this->getTableColumns($connection, $tableName);

                $fillable = [];
                $properties = [];
                $timestamps = false;
                $relationships = [];
                $casts = [];
                $rules = [];

                if ($this->option('with-relationships')) {
                    $foreignKeys = $this->getForeignKeys($connection, $tableName);
                    foreach ($foreignKeys as $fk) {
                        $relationships[] = $this->generateRelationship($fk);
                    }
                }

                foreach ($columns as $column) {
                    if ($column->name === 'created_at' || $column->name === 'updated_at') {
                        $timestamps = true;
                        continue;
                    }
                    if ($column->name !== 'id') {
                        $fillable[] = "'{$column->name}'";
                        $phpType = $this->mapSqliteTypeToPhp($column->type);
                        $properties[] = " * @property {$phpType} \${$column->name}";
                    }

                    // Add casts for specific types
                    if (str_contains($column->type, 'json')) {
                        $casts[] = "'{$column->name}' => 'array'";
                    } elseif (str_contains($column->name, '_at')) {
                        $casts[] = "'{$column->name}' => 'datetime'";
                    }

                    // Generate validation rules
                    if ($this->option('with-rules')) {
                        $rules[] = $this->generateValidationRule($column);
                    }
                }

                $fillableString = implode(",\n        ", $fillable);
                $propertiesString = implode("\n", $properties);
                $relationshipsString = implode("\n\n", $relationships);
                $castsString = implode(",\n        ", $casts);
                $rulesString = implode(",\n        ", $rules);

                // Modify namespace for subdirectory
                $namespace = 'App\\Models\\GeneratedModels';
                if ($directory) {
                    $namespace .= '\\' . str_replace('/', '\\', $directory);
                }

                // Convert boolean to string before interpolation
                $timestampsValue = $timestamps ? 'true' : 'false';

                $modelContent = $this->generateModelContent(
                    $namespace, 
                    $modelName, 
                    $tableName, 
                    $connection,
                    $timestampsValue,
                    $fillableString,
                    $propertiesString,
                    $relationshipsString,
                    $castsString,
                    $rulesString
                );

                $modelPath = $baseDir . "/{$modelName}.php";
                File::put($modelPath, $modelContent);
                $this->info("Model {$modelName} created at {$modelPath}");
            }

            if ($this->option('with-factories')) {
                $this->generateFactories($baseDir, $tables, $connection);
            }

            $this->info('Models generated successfully!');
            return 0;
        } catch (\Exception $e) {
            $this->error("Error generating models: " . $e->getMessage());
            return 1;
        }
    }

    private function getTableColumns($connection, $tableName)
    {
        if (config("database.connections.{$connection}.driver") === 'sqlite') {
            return DB::connection($connection)->select("PRAGMA table_info({$tableName})");
        } else {
            return DB::connection($connection)
                ->select("SELECT 
                    COLUMN_NAME as name,
                    DATA_TYPE as type,
                    IS_NULLABLE as nullable,
                    COLUMN_DEFAULT as default
                FROM information_schema.columns 
                WHERE table_schema = ? 
                AND table_name = ?", 
                [config("database.connections.{$connection}.database"), $tableName]);
        }
    }

    private function getForeignKeys($connection, $tableName)
    {
        if (config("database.connections.{$connection}.driver") === 'sqlite') {
            return DB::connection($connection)
                ->select("SELECT * FROM pragma_foreign_key_list('{$tableName}')");
        } else {
            return DB::connection($connection)
                ->select("SELECT 
                    COLUMN_NAME as 'from',
                    REFERENCED_TABLE_NAME as 'table',
                    REFERENCED_COLUMN_NAME as 'to'
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = ? 
                AND TABLE_NAME = ? 
                AND REFERENCED_TABLE_NAME IS NOT NULL", 
                [config("database.connections.{$connection}.database"), $tableName]);
        }
    }

    private function mapSqliteTypeToPhp(string $sqliteType): string
    {
        return match (strtolower($sqliteType)) {
            'integer', 'int', 'bigint', 'smallint', 'tinyint' => 'int',
            'real', 'float', 'double', 'decimal' => 'float',
            'boolean', 'bool', 'tinyint(1)' => 'bool',
            'datetime', 'timestamp', 'date' => 'string|\\DateTime',
            'json', 'longtext' => 'array',
            default => 'string',
        };
    }

    private function generateRelationship($foreignKey): string
    {
        $relatedModel = Str::studly(Str::singular($foreignKey->table));
        return <<<EOT
    public function {$foreignKey->to}()
    {
        return \$this->belongsTo(\\App\\Models\\{$relatedModel}::class, '{$foreignKey->from}');
    }
EOT;
    }

    private function generateValidationRule($column): string
    {
        $rules = ["'{$column->name}' => ['"];
        if ($column->notnull) {
            $rules[] = 'required';
        }
        if (str_contains($column->type, 'int')) {
            $rules[] = 'integer';
        }
        return implode('|', $rules) . "']";
    }

    private function generateFactories(string $baseDir, array $tables, string $connection): void
    {
        $factoryDir = database_path('factories/Generated');
        if (!File::isDirectory($factoryDir)) {
            File::makeDirectory($factoryDir, 0755, true);
        }

        foreach ($tables as $table) {
            // Generate factory class for each model
            // Implementation details here
        }
    }

    private function generateModelContent(
        string $namespace,
        string $modelName,
        string $tableName,
        string $connection,
        string $timestampsValue,
        string $fillableString,
        string $propertiesString,
        string $relationshipsString,
        string $castsString,
        string $rulesString
    ): string {
        return <<<EOT
<?php

namespace {$namespace};

use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;
use Illuminate\\Database\\Eloquent\\Model;

/**
 * {$modelName} Model
 *
{$propertiesString}
 */
class {$modelName} extends Model
{
    use HasFactory;

    /**
     * The database connection that should be used by the model.
     */
    protected \$connection = '{$connection}';
    
    /**
     * The table associated with the model.
     */
    protected \$table = '{$tableName}';

    /**
     * The primary key associated with the table.
     */
    protected \$primaryKey = 'id';

    /**
     * Indicates if the model's ID is auto-incrementing.
     */
    public \$incrementing = true;

    /**
     * The data type of the auto-incrementing ID.
     */
    protected \$keyType = 'int';

    /**
     * Indicates if the model should be timestamped.
     */
    public \$timestamps = {$timestampsValue};

    /**
     * The storage format of the model's date columns.
     */
    protected \$dateFormat = 'Y-m-d H:i:s';

    /**
     * The attributes that are mass assignable.
     */
    protected \$fillable = [
        {$fillableString}
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected \$hidden = [];

    /**
     * The attributes that should be visible in serialization.
     */
    protected \$visible = [];

    /**
     * The attributes that should be cast.
     */
    protected \$casts = [
        {$castsString}
    ];

    /**
     * The model's default values for attributes.
     */
    protected \$attributes = [];

    /**
     * The validation rules for the model.
     */
    public static \$rules = [
        {$rulesString}
    ];

{$relationshipsString}
}

EOT;
    }

    private function getTables($connection, $excludedTablesString)
    {
        if (config("database.connections.{$connection}.driver") === 'sqlite') {
            return DB::connection($connection)->select(
                "SELECT name FROM sqlite_master 
                WHERE type='table' 
                AND name NOT LIKE 'sqlite_%'
                AND name NOT IN ({$excludedTablesString});"
            );
        } else {
            return DB::connection($connection)
                ->select("SELECT table_name as name 
                         FROM information_schema.tables 
                         WHERE table_schema = ? 
                         AND table_name NOT IN ({$excludedTablesString})", 
                         [config("database.connections.{$connection}.database")]);
        }
    }
}