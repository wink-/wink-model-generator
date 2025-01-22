<?php

declare(strict_types=1);

namespace Wink\ModelGenerator\Database;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SqliteSchemaReader implements SchemaReader
{
    public function getTables(string $connection, array $excludedTables): array
    {
        // Set read-only mode for this connection
        DB::connection($connection)->statement('PRAGMA query_only = 1');
        
        $tables = [];
        foreach (Schema::connection($connection)->getAllTables() as $table) {
            $tableName = $table->name;
            if (!in_array($tableName, $excludedTables) && !Str::startsWith($tableName, 'sqlite_')) {
                $tables[] = (object)['name' => $tableName];
            }
        }
        return $tables;
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
