<?php

declare(strict_types=1);

namespace Wink\ModelGenerator\Database;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;

class SqliteSchemaReader implements SchemaReader
{
    public function getTables(string $connection, array $excludedTables): array
    {
        DB::connection($connection)->statement('PRAGMA query_only = 1');
        
        $tables = DB::connection($connection)
            ->select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
            
        return collect($tables)
            ->reject(fn($table) => in_array($table->name, $excludedTables))
            ->map(fn($table) => (object)[
                'name' => $table->name,
                'comment' => ''
            ])
            ->values()
            ->all();
    }

    public function getTableColumns(string $connection, string $tableName): array
    {
        // Set read-only mode for this connection
        DB::connection($connection)->statement('PRAGMA query_only = 1');
        
        return DB::connection($connection)
            ->select("PRAGMA table_info({$tableName})");
    }

    public function getForeignKeys(string $connection, string $tableName): array
    {
        // Set read-only mode for this connection
        DB::connection($connection)->statement('PRAGMA query_only = 1');
        
        return DB::connection($connection)
            ->select("SELECT * FROM pragma_foreign_key_list('{$tableName}')");
    }
}
