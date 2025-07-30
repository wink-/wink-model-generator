<?php

declare(strict_types=1);

namespace Wink\ModelGenerator\Database;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class SqliteSchemaReader implements SchemaReader
{
    public function getTables(string $connection, array $excludedTables): array
    {
        $config = config("database.connections.{$connection}");

        if (! isset($config['database'])) {
            throw new RuntimeException("No database path configured for connection: {$connection}");
        }

        $database = $config['database'];
        if ($database !== ':memory:' && ! file_exists($database)) {
            throw new RuntimeException("Database file at path [{$database}] does not exist. Ensure this is an absolute path to the database.");
        }

        // Only set read-only mode if database exists
        DB::connection($connection)->statement('PRAGMA query_only = 1');

        $tables = DB::connection($connection)
            ->select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");

        return collect($tables)
            ->reject(fn ($table) => in_array($table->name, $excludedTables))
            ->map(fn ($table) => (object) [
                'name' => $table->name,
                'comment' => '',
            ])
            ->values()
            ->all();
    }

    public function getTableColumns(string $connection, string $tableName): array
    {
        $config = config("database.connections.{$connection}");

        if (! isset($config['database'])) {
            throw new RuntimeException("No database path configured for connection: {$connection}");
        }

        $database = $config['database'];
        if ($database !== ':memory:' && ! file_exists($database)) {
            throw new RuntimeException("Database file at path [{$database}] does not exist. Ensure this is an absolute path to the database.");
        }

        // Only set read-only mode if database exists
        DB::connection($connection)->statement('PRAGMA query_only = 1');

        $columns = DB::connection($connection)
            ->select("PRAGMA table_info({$tableName})");
        
        // Get the table's SQL definition to check for AUTOINCREMENT
        $tableSql = DB::connection($connection)
            ->select("SELECT sql FROM sqlite_master WHERE type='table' AND name=?", [$tableName]);
        
        $isAutoIncrement = false;
        if (!empty($tableSql) && $tableSql[0]->sql) {
            // Check if table has AUTOINCREMENT keyword
            $isAutoIncrement = stripos($tableSql[0]->sql, 'AUTOINCREMENT') !== false;
        }
        
        // Ensure pk field is properly converted to boolean and add primary alias
        // Also convert SQLite's notnull to nullable for consistency
        foreach ($columns as $column) {
            $column->pk = (bool) $column->pk;
            $column->primary = $column->pk;
            // SQLite uses 'notnull' where 1 = NOT NULL, 0 = NULL allowed
            // Convert to 'nullable' for consistency with other database drivers
            $column->nullable = !$column->notnull;
            
            // Add extra field for auto-increment detection
            // In SQLite, INTEGER PRIMARY KEY columns are auto-incremented by default
            if ($column->primary && strtoupper($column->type) === 'INTEGER') {
                $column->extra = 'auto_increment';
            } else if ($isAutoIncrement && $column->primary) {
                // If AUTOINCREMENT keyword is explicitly used
                $column->extra = 'auto_increment';
            } else {
                $column->extra = '';
            }
            
            // For SQLite, the type field may contain additional info like tinyint(1)
            // Extract this for consistency with MySQL which has separate type_extra
            if (preg_match('/^(\w+)(\(.+\))$/', $column->type, $matches)) {
                $column->type = $matches[1];
                $column->type_extra = $column->type . $matches[2]; // Store full type like 'tinyint(1)'
            } else {
                $column->type_extra = '';
            }
        }
        
        return $columns;
    }

    public function getForeignKeys(string $connection, string $tableName): array
    {
        $config = config("database.connections.{$connection}");

        if (! isset($config['database'])) {
            throw new RuntimeException("No database path configured for connection: {$connection}");
        }

        $database = $config['database'];
        if ($database !== ':memory:' && ! file_exists($database)) {
            throw new RuntimeException("Database file at path [{$database}] does not exist. Ensure this is an absolute path to the database.");
        }

        // Only set read-only mode if database exists
        DB::connection($connection)->statement('PRAGMA query_only = 1');

        return DB::connection($connection)
            ->select("SELECT * FROM pragma_foreign_key_list('{$tableName}')");
    }
}
