<?php

declare(strict_types=1);

namespace Wink\ModelGenerator\Database;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class PostgreSqlSchemaReader implements SchemaReader
{
    public function getTables(string $connection, array $excludedTables): array
    {
        $schema = Config::get("database.connections.{$connection}.schema", 'public');
        $excludedTablesString = ! empty($excludedTables) ?
            "AND table_name NOT IN ('".implode("','", $excludedTables)."')" :
            '';

        return DB::connection($connection)
            ->select("SELECT table_name as name 
                     FROM information_schema.tables 
                     WHERE table_schema = ? 
                     AND table_type = 'BASE TABLE'
                     {$excludedTablesString}
                     ORDER BY table_name",
                [$schema]);
    }

    public function getTableColumns(string $connection, string $tableName): array
    {
        $schema = Config::get("database.connections.{$connection}.schema", 'public');

        $columns = DB::connection($connection)
            ->select("SELECT 
                column_name as name,
                data_type as type,
                CASE WHEN is_nullable = 'NO' THEN 1 ELSE 0 END as notnull,
                column_default as \"default\",
                character_maximum_length as length,
                numeric_precision as \"precision\",
                numeric_scale as \"scale\",
                udt_name as type_extra
            FROM information_schema.columns 
            WHERE table_schema = ? 
            AND table_name = ?
            ORDER BY ordinal_position",
                [$schema, $tableName]);

        // Add fulltext information (PostgreSQL uses GIN/GiST indexes for full-text search)
        foreach ($columns as $column) {
            $column->fulltext = false; // PostgreSQL doesn't have MySQL-style FULLTEXT indexes
        }

        return $columns;
    }

    public function getForeignKeys(string $connection, string $tableName): array
    {
        $schema = Config::get("database.connections.{$connection}.schema", 'public');

        return DB::connection($connection)
            ->select("SELECT 
                kcu.column_name as \"from\",
                ccu.table_name as \"table\",
                ccu.column_name as \"to\",
                rc.delete_rule as on_delete
            FROM information_schema.table_constraints tc
            JOIN information_schema.key_column_usage kcu
                ON tc.constraint_name = kcu.constraint_name
                AND tc.table_schema = kcu.table_schema
            JOIN information_schema.constraint_column_usage ccu
                ON ccu.constraint_name = tc.constraint_name
                AND ccu.table_schema = tc.table_schema
            JOIN information_schema.referential_constraints rc
                ON tc.constraint_name = rc.constraint_name
                AND tc.table_schema = rc.constraint_schema
            WHERE tc.constraint_type = 'FOREIGN KEY'
            AND tc.table_schema = ?
            AND tc.table_name = ?",
                [$schema, $tableName]);
    }

    public function getTableIndexes(string $connection, string $tableName): array
    {
        $schema = Config::get("database.connections.{$connection}.schema", 'public');

        $indexes = DB::connection($connection)
            ->select("SELECT 
                i.relname as name,
                CASE 
                    WHEN ix.indisunique THEN 'UNIQUE'
                    WHEN ix.indisprimary THEN 'PRIMARY'
                    ELSE 'INDEX'
                END as type,
                string_agg(a.attname, ',' ORDER BY array_position(ix.indkey, a.attnum)) as column_list
            FROM pg_class t
            JOIN pg_index ix ON t.oid = ix.indrelid
            JOIN pg_class i ON i.oid = ix.indexrelid
            JOIN pg_attribute a ON t.oid = a.attrelid
            JOIN pg_namespace n ON t.relnamespace = n.oid
            WHERE n.nspname = ?
            AND t.relname = ?
            AND a.attnum = ANY(ix.indkey)
            AND t.relkind = 'r'
            GROUP BY i.relname, ix.indisunique, ix.indisprimary",
                [$schema, $tableName]);

        $result = [];
        foreach ($indexes as $index) {
            $result[] = (object) [
                'name' => $index->name,
                'type' => $index->type,
                'columns' => explode(',', $index->column_list),
            ];
        }

        return $result;
    }
}
