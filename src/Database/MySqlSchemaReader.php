<?php

declare(strict_types=1);

namespace Wink\ModelGenerator\Database;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

class MySqlSchemaReader implements SchemaReader
{
    public function getTables(string $connection, array $excludedTables): array
    {
        $schema = Config::get("database.connections.{$connection}.database");
        $excludedTablesString = !empty($excludedTables) ? 
            "AND table_name NOT IN ('" . implode("','", $excludedTables) . "')" : 
            "";
        
        // Set read-only mode for this connection
        DB::connection($connection)->statement('SET SESSION TRANSACTION READ ONLY');
        
        return DB::connection($connection)
            ->select("SELECT TABLE_NAME as name 
                     FROM information_schema.tables 
                     WHERE table_schema = ? 
                     AND table_type = 'BASE TABLE'
                     AND table_name NOT LIKE 'pma%'
                     {$excludedTablesString}", 
                     [$schema]);
    }

    public function getTableColumns(string $connection, string $tableName): array
    {
        $schema = Config::get("database.connections.{$connection}.database");
        
        // Set read-only mode for this connection
        DB::connection($connection)->statement('SET SESSION TRANSACTION READ ONLY');
        
        $columns = DB::connection($connection)
            ->select("SELECT 
                COLUMN_NAME as name,
                DATA_TYPE as type,
                IF(IS_NULLABLE = 'NO', 1, 0) as notnull,
                COLUMN_DEFAULT as `default`,
                CHARACTER_MAXIMUM_LENGTH as length,
                NUMERIC_PRECISION as `precision`,
                NUMERIC_SCALE as `scale`,
                COLUMN_TYPE as type_extra,
                EXTRA as extra
            FROM information_schema.columns 
            WHERE table_schema = ? 
            AND table_name = ?
            ORDER BY ORDINAL_POSITION", 
            [$schema, $tableName]);

        // Get fulltext indexes to mark columns
        $fulltextColumns = $this->getFulltextColumns($connection, $schema, $tableName);
        foreach ($columns as $column) {
            $column->fulltext = in_array($column->name, $fulltextColumns);
        }

        return $columns;
    }

    public function getForeignKeys(string $connection, string $tableName): array
    {
        $schema = Config::get("database.connections.{$connection}.database");
        
        // Set read-only mode for this connection
        DB::connection($connection)->statement('SET SESSION TRANSACTION READ ONLY');
        
        return DB::connection($connection)
            ->select("SELECT 
                k.COLUMN_NAME as 'from',
                k.REFERENCED_TABLE_NAME as 'table',
                k.REFERENCED_COLUMN_NAME as 'to',
                r.DELETE_RULE as on_delete
            FROM information_schema.KEY_COLUMN_USAGE k
            JOIN information_schema.REFERENTIAL_CONSTRAINTS r
                ON k.CONSTRAINT_NAME = r.CONSTRAINT_NAME
                AND k.CONSTRAINT_SCHEMA = r.CONSTRAINT_SCHEMA
            WHERE k.REFERENCED_TABLE_SCHEMA = ? 
            AND k.TABLE_NAME = ? 
            AND k.REFERENCED_TABLE_NAME IS NOT NULL", 
            [$schema, $tableName]);
    }

    public function getTableIndexes(string $connection, string $tableName): array
    {
        $schema = Config::get("database.connections.{$connection}.database");
        
        // Set read-only mode for this connection
        DB::connection($connection)->statement('SET SESSION TRANSACTION READ ONLY');
        
        $indexes = DB::connection($connection)
            ->select("SELECT 
                s.INDEX_NAME as name,
                s.INDEX_TYPE as type,
                GROUP_CONCAT(s.COLUMN_NAME ORDER BY s.SEQ_IN_INDEX) as column_list,
                s.NON_UNIQUE as non_unique
            FROM information_schema.STATISTICS s
            WHERE s.TABLE_SCHEMA = ?
            AND s.TABLE_NAME = ?
            AND s.INDEX_NAME != 'PRIMARY'
            GROUP BY s.INDEX_NAME, s.INDEX_TYPE, s.NON_UNIQUE",
            [$schema, $tableName]);

        // Add primary key if it exists
        $primaryKey = DB::connection($connection)
            ->select("SELECT 
                GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) as column_list
            FROM information_schema.STATISTICS 
            WHERE TABLE_SCHEMA = ?
            AND TABLE_NAME = ?
            AND INDEX_NAME = 'PRIMARY'
            GROUP BY INDEX_NAME",
            [$schema, $tableName]);

        $result = [];
        
        // Add primary key first if it exists
        if (!empty($primaryKey)) {
            $result[] = (object)[
                'name' => 'PRIMARY',
                'type' => 'PRIMARY',
                'columns' => explode(',', $primaryKey[0]->column_list)
            ];
        }

        // Format and add other indexes
        foreach ($indexes as $index) {
            $result[] = (object)[
                'name' => $index->name,
                'type' => $this->normalizeIndexType($index),
                'columns' => explode(',', $index->column_list)
            ];
        }

        return $result;
    }

    private function getFulltextColumns(string $connection, string $schema, string $tableName): array
    {
        // Set read-only mode for this connection
        DB::connection($connection)->statement('SET SESSION TRANSACTION READ ONLY');
        
        $fulltextIndexes = DB::connection($connection)
            ->select("SELECT 
                GROUP_CONCAT(COLUMN_NAME) as columns
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = ?
            AND TABLE_NAME = ?
            AND INDEX_TYPE = 'FULLTEXT'
            GROUP BY INDEX_NAME",
            [$schema, $tableName]);

        $columns = [];
        foreach ($fulltextIndexes as $index) {
            $columns = array_merge($columns, explode(',', $index->columns));
        }
        return array_unique($columns);
    }

    private function normalizeIndexType(object $index): string
    {
        if ($index->type === 'FULLTEXT') {
            return 'FULLTEXT';
        }
        if ($index->non_unique == 0) {
            return 'UNIQUE';
        }
        return 'INDEX';
    }
}
