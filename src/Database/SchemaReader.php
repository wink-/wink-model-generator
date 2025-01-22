<?php

declare(strict_types=1);

namespace Wink\ModelGenerator\Database;

interface SchemaReader
{
    /**
     * Get all tables from the database
     *
     * @param string $connection Database connection name
     * @param array<string> $excludedTables Tables to exclude
     * @return array<object> Array of table objects with 'name' property
     */
    public function getTables(string $connection, array $excludedTables): array;

    /**
     * Get columns for a specific table
     *
     * @param string $connection Database connection name
     * @param string $tableName Table name
     * @return array<object> Array of column objects
     */
    public function getTableColumns(string $connection, string $tableName): array;

    /**
     * Get foreign keys for a specific table
     *
     * @param string $connection Database connection name
     * @param string $tableName Table name
     * @return array<object> Array of foreign key objects
     */
    public function getForeignKeys(string $connection, string $tableName): array;
}
