<?php

declare(strict_types=1);

namespace Wink\ModelGenerator\Database;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class SqliteSchemaReader implements SchemaReader
{
    /**
     * Validate connection and prepare for read-only operations.
     *
     * @throws RuntimeException
     */
    private function validateAndPrepareConnection(string $connection): void
    {
        $config = config("database.connections.{$connection}");

        if (! isset($config['database'])) {
            throw new RuntimeException("No database path configured for connection: {$connection}");
        }

        $database = $config['database'];
        if ($database !== ':memory:' && ! file_exists($database)) {
            throw new RuntimeException("Database file at path [{$database}] does not exist. Ensure this is an absolute path to the database.");
        }

        // Set read-only mode for this connection
        DB::connection($connection)->statement('PRAGMA query_only = 1');
    }

    /**
     * Sanitize table name to prevent SQL injection in PRAGMA statements.
     * SQLite table names must be valid identifiers.
     *
     * Note: SQLite PRAGMA commands cannot use parameter binding, so we validate
     * table names to ensure they only contain safe identifier characters.
     *
     * @throws RuntimeException
     */
    private function sanitizeTableName(string $tableName): string
    {
        // SQLite identifiers can contain alphanumeric characters, underscores
        // They cannot start with a digit and cannot contain special characters
        if (! preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $tableName)) {
            throw new RuntimeException("Invalid table name: {$tableName}");
        }

        return $tableName;
    }

    public function getTables(string $connection, array $excludedTables): array
    {
        $this->validateAndPrepareConnection($connection);

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
        $this->validateAndPrepareConnection($connection);
        $safeTableName = $this->sanitizeTableName($tableName);

        // PRAGMA commands cannot use parameter binding in SQLite,
        // so we use sanitized table name to prevent SQL injection
        $columns = DB::connection($connection)
            ->select("PRAGMA table_info({$safeTableName})");

        // Get the table's SQL definition to check for AUTOINCREMENT
        $tableSql = DB::connection($connection)
            ->select('SELECT sql FROM sqlite_master WHERE type=? AND name=?', ['table', $safeTableName]);

        $isAutoIncrement = false;
        if (! empty($tableSql) && $tableSql[0]->sql) {
            // Check if table has AUTOINCREMENT keyword (case-insensitive)
            $isAutoIncrement = str_contains(strtolower($tableSql[0]->sql), 'autoincrement');
        }

        // Ensure pk field is properly converted to boolean and add primary alias
        // Also convert SQLite's notnull to nullable for consistency
        foreach ($columns as $column) {
            $column->pk = (bool) $column->pk;
            $column->primary = $column->pk;
            // SQLite uses 'notnull' where 1 = NOT NULL, 0 = NULL allowed
            // Convert to 'nullable' for consistency with other database drivers
            $column->nullable = ! $column->notnull;

            // Add extra field for auto-increment detection
            // In SQLite, INTEGER PRIMARY KEY columns are auto-incremented by default
            if ($column->primary && strtoupper($column->type) === 'INTEGER') {
                $column->extra = 'auto_increment';
            } elseif ($isAutoIncrement && $column->primary) {
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
        $this->validateAndPrepareConnection($connection);
        $safeTableName = $this->sanitizeTableName($tableName);

        // PRAGMA functions cannot use parameter binding in SQLite,
        // so we use sanitized table name to prevent SQL injection
        return DB::connection($connection)
            ->select("SELECT * FROM pragma_foreign_key_list({$safeTableName})");
    }
}
