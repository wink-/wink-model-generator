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

        return DB::connection($connection)
            ->select("PRAGMA table_info({$tableName})");
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
