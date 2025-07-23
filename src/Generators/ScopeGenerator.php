<?php

declare(strict_types=1);

namespace Wink\ModelGenerator\Generators;

use Illuminate\Support\Str;
use Wink\ModelGenerator\Config\GeneratorConfig;

class ScopeGenerator
{
    private GeneratorConfig $config;

    public function __construct(GeneratorConfig $config)
    {
        $this->config = $config;
    }

    /**
     * Generate scope methods for a model based on its columns
     */
    public function generateScopes(array $columns): array
    {
        if (! $this->config->getModelProperty('auto_generate_scopes', false)) {
            return [];
        }

        $scopes = [];

        foreach ($columns as $column) {
            $scopes = array_merge($scopes, $this->generateColumnScopes($column));
        }

        return $scopes;
    }

    /**
     * Generate scopes for a specific column
     */
    private function generateColumnScopes(object $column): array
    {
        $scopes = [];
        $columnName = $column->name;
        $dbType = strtolower($column->type);
        $typeExtra = isset($column->type_extra) ? strtolower($column->type_extra) : '';

        // Skip timestamp columns (they get special handling)
        if (in_array($columnName, ['created_at', 'updated_at', 'deleted_at'])) {
            return [];
        }

        // Boolean column scopes (highest priority)
        if (($dbType === 'tinyint' && $typeExtra === '1') ||
            $dbType === 'boolean' ||
            $dbType === 'bool' ||
            $this->isBooleanColumn($columnName)) {
            $scopes = array_merge($scopes, $this->generateBooleanScopes($columnName));

            // Don't generate other scopes for boolean columns
            return $scopes;
        }

        // Enum column scopes (high priority)
        if ($dbType === 'enum' && isset($column->type_extra)) {
            $scopes = array_merge($scopes, $this->generateEnumScopes($columnName, $column->type_extra));

            // Don't generate other scopes for enum columns
            return $scopes;
        }

        // Status column scopes
        if ($this->isStatusColumn($columnName)) {
            $scopes = array_merge($scopes, $this->generateStatusScopes($columnName));
        }

        // Date/DateTime column scopes
        if (in_array($dbType, ['date', 'datetime', 'timestamp'])) {
            $scopes = array_merge($scopes, $this->generateDateScopes($columnName));
        }

        // Foreign key scopes (priority over numeric)
        if ($this->isForeignKey($columnName)) {
            $scopes = array_merge($scopes, $this->generateForeignKeyScopes($columnName));

            // Don't generate numeric scopes for foreign keys
            return $scopes;
        }

        // Searchable column scopes
        if ($this->isSearchableColumn($columnName, $dbType)) {
            $scopes = array_merge($scopes, $this->generateSearchScopes($columnName));
        }

        // Numeric column scopes
        if ($this->isNumericColumn($dbType)) {
            $scopes = array_merge($scopes, $this->generateNumericScopes($columnName));
        }

        return $scopes;
    }

    /**
     * Generate boolean-based scopes
     */
    private function generateBooleanScopes(string $columnName): array
    {
        $scopes = [];
        $patterns = $this->config->getModelProperty('boolean_scope_patterns', [
            'is_active' => ['active', 'inactive'],
            'is_published' => ['published', 'unpublished'],
            'is_featured' => ['featured', 'notFeatured'],
            'is_enabled' => ['enabled', 'disabled'],
            'is_verified' => ['verified', 'unverified'],
            'is_approved' => ['approved', 'unapproved'],
            'is_visible' => ['visible', 'hidden'],
            'is_archived' => ['archived', 'notArchived'],
        ]);

        foreach ($patterns as $pattern => $scopeNames) {
            if (Str::contains($columnName, str_replace('is_', '', $pattern))) {
                $scopes[] = $this->generateBooleanScope(Str::studly($scopeNames[0]), $columnName, true);
                $scopes[] = $this->generateBooleanScope(Str::studly($scopeNames[1]), $columnName, false);
                break;
            }
        }

        // Generic boolean scopes if no pattern matched
        if (empty($scopes) && Str::startsWith($columnName, 'is_')) {
            $baseName = Str::camel(str_replace('is_', '', $columnName));
            $scopes[] = $this->generateBooleanScope($baseName, $columnName, true);
            $scopes[] = $this->generateBooleanScope('not'.Str::studly($baseName), $columnName, false);
        }

        return $scopes;
    }

    /**
     * Generate a boolean scope method
     */
    private function generateBooleanScope(string $scopeName, string $columnName, bool $value): string
    {
        $boolValue = $value ? 'true' : 'false';

        return <<<EOT
    /**
     * Scope a query to only include records where {$columnName} is {$boolValue}.
     */
    public function scope{$scopeName}(\$query)
    {
        return \$query->where('{$columnName}', {$boolValue});
    }
EOT;
    }

    /**
     * Generate status-based scopes
     */
    private function generateStatusScopes(string $columnName): array
    {
        $scopes = [];
        $statusName = Str::studly($columnName);

        $scopes[] = <<<EOT
    /**
     * Scope a query to filter by {$columnName}.
     */
    public function scopeBy{$statusName}(\$query, \$status)
    {
        return \$query->where('{$columnName}', \$status);
    }
EOT;

        return $scopes;
    }

    /**
     * Generate enum-based scopes
     */
    private function generateEnumScopes(string $columnName, string $typeExtra): array
    {
        $scopes = [];
        $enumValues = $this->extractEnumValues($typeExtra);

        if (empty($enumValues)) {
            return [];
        }

        foreach ($enumValues as $value) {
            $scopeName = Str::studly($value);
            $scopes[] = <<<EOT
    /**
     * Scope a query to only include {$value} records.
     */
    public function scope{$scopeName}(\$query)
    {
        return \$query->where('{$columnName}', '{$value}');
    }
EOT;
        }

        return $scopes;
    }

    /**
     * Generate date-based scopes
     */
    private function generateDateScopes(string $columnName): array
    {
        $scopes = [];
        $camelName = Str::camel($columnName);
        $studlyName = Str::studly($columnName);

        // Recent scope
        $scopes[] = <<<EOT
    /**
     * Scope a query to only include recent records based on {$columnName}.
     */
    public function scopeRecent(\$query, \$days = 30)
    {
        return \$query->where('{$columnName}', '>=', now()->subDays(\$days));
    }
EOT;

        // Date range scope
        $scopes[] = <<<EOT
    /**
     * Scope a query to filter by {$columnName} date range.
     */
    public function scope{$studlyName}Between(\$query, \$startDate, \$endDate)
    {
        return \$query->whereBetween('{$columnName}', [\$startDate, \$endDate]);
    }
EOT;

        // After date scope
        $scopes[] = <<<EOT
    /**
     * Scope a query to only include records after a specific {$columnName}.
     */
    public function scope{$studlyName}After(\$query, \$date)
    {
        return \$query->where('{$columnName}', '>', \$date);
    }
EOT;

        // Before date scope
        $scopes[] = <<<EOT
    /**
     * Scope a query to only include records before a specific {$columnName}.
     */
    public function scope{$studlyName}Before(\$query, \$date)
    {
        return \$query->where('{$columnName}', '<', \$date);
    }
EOT;

        return $scopes;
    }

    /**
     * Generate foreign key scopes
     */
    private function generateForeignKeyScopes(string $columnName): array
    {
        $scopes = [];
        $relationName = Str::camel(str_replace('_id', '', $columnName));
        $studlyName = Str::studly($relationName);

        $scopes[] = <<<EOT
    /**
     * Scope a query to filter by {$relationName}.
     */
    public function scopeBy{$studlyName}(\$query, \$id)
    {
        return \$query->where('{$columnName}', \$id);
    }
EOT;

        return $scopes;
    }

    /**
     * Generate search scopes
     */
    private function generateSearchScopes(string $columnName): array
    {
        $scopes = [];
        $studlyName = Str::studly($columnName);

        $scopes[] = <<<EOT
    /**
     * Scope a query to search in {$columnName}.
     */
    public function scopeSearch{$studlyName}(\$query, \$search)
    {
        return \$query->where('{$columnName}', 'LIKE', '%' . \$search . '%');
    }
EOT;

        return $scopes;
    }

    /**
     * Generate numeric scopes
     */
    private function generateNumericScopes(string $columnName): array
    {
        $scopes = [];
        $studlyName = Str::studly($columnName);

        // Greater than scope
        $scopes[] = <<<EOT
    /**
     * Scope a query to filter {$columnName} greater than value.
     */
    public function scope{$studlyName}GreaterThan(\$query, \$value)
    {
        return \$query->where('{$columnName}', '>', \$value);
    }
EOT;

        // Less than scope
        $scopes[] = <<<EOT
    /**
     * Scope a query to filter {$columnName} less than value.
     */
    public function scope{$studlyName}LessThan(\$query, \$value)
    {
        return \$query->where('{$columnName}', '<', \$value);
    }
EOT;

        // Between scope
        $scopes[] = <<<EOT
    /**
     * Scope a query to filter {$columnName} between values.
     */
    public function scope{$studlyName}Between(\$query, \$min, \$max)
    {
        return \$query->whereBetween('{$columnName}', [\$min, \$max]);
    }
EOT;

        return $scopes;
    }

    /**
     * Generate common timestamp scopes
     */
    public function generateTimestampScopes(array $columns = []): array
    {
        if (! $this->config->getModelProperty('auto_generate_timestamp_scopes', true)) {
            return [];
        }

        // Find actual datetime columns in the table
        $dateTimeColumns = $this->findDateTimeColumns($columns);
        
        if (empty($dateTimeColumns)) {
            return [];
        }

        $scopes = [];

        // Generate scopes based on actual datetime columns found
        foreach ($dateTimeColumns as $column) {
            $columnName = $column['name'];
            $scopeName = $this->generateScopeNameFromColumn($columnName);
            
            // Recently scope
            $scopes[] = <<<EOT
    /**
     * Scope a query to only include recent records based on {$columnName}.
     */
    public function scope{$scopeName}Recently(\$query, \$days = 7)
    {
        return \$query->where('{$columnName}', '>=', now()->subDays(\$days));
    }
EOT;

            // Today scope
            $scopes[] = <<<EOT
    /**
     * Scope a query to only include records from today based on {$columnName}.
     */
    public function scope{$scopeName}Today(\$query)
    {
        return \$query->whereDate('{$columnName}', today());
    }
EOT;

            // Latest scope (newest first)
            $scopes[] = <<<EOT
    /**
     * Scope a query to order by {$columnName} newest first.
     */
    public function scopeLatest{$scopeName}(\$query)
    {
        return \$query->orderBy('{$columnName}', 'desc');
    }
EOT;

            // Oldest scope (oldest first)
            $scopes[] = <<<EOT
    /**
     * Scope a query to order by {$columnName} oldest first.
     */
    public function scopeOldest{$scopeName}(\$query)
    {
        return \$query->orderBy('{$columnName}', 'asc');
    }
EOT;
        }

        return $scopes;
    }

    /**
     * Find datetime columns in the table
     */
    private function findDateTimeColumns(array $columns): array
    {
        $dateTimeColumns = [];
        
        foreach ($columns as $column) {
            $columnName = $column->name;
            $columnType = strtolower($column->type);
            
            // Check if it's a datetime-related column
            if ($this->isDateTimeType($columnType) || $this->isDateTimeColumnName($columnName)) {
                $dateTimeColumns[] = [
                    'name' => $columnName,
                    'type' => $columnType
                ];
            }
        }
        
        return $dateTimeColumns;
    }

    /**
     * Check if column type is datetime-related
     */
    private function isDateTimeType(string $type): bool
    {
        return in_array($type, [
            'datetime', 'timestamp', 'date', 'time'
        ]);
    }

    /**
     * Check if column name suggests it's a datetime column
     */
    private function isDateTimeColumnName(string $name): bool
    {
        $patterns = [
            '_at$', '_date$', '_time$', 'date_', 'time_',
            '^created_at$', '^updated_at$', '^deleted_at$',
            '^timestamp$', '^created$', '^updated$'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match('/' . $pattern . '/i', $name)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Generate scope name from column name
     */
    private function generateScopeNameFromColumn(string $columnName): string
    {
        // Convert column name to scope name (PascalCase)
        $name = str_replace(['_at', '_date', '_time'], '', $columnName);
        $name = ucwords(str_replace('_', ' ', $name));
        $name = str_replace(' ', '', $name);
        
        // Handle common cases
        return match (strtolower($columnName)) {
            'created_at', 'created' => 'Created',
            'updated_at', 'updated' => 'Updated',
            'deleted_at', 'deleted' => 'Deleted',
            'timestamp' => 'Timestamp',
            default => $name ?: 'Date'
        };
    }

    /**
     * Extract enum values from type extra
     */
    private function extractEnumValues(string $typeExtra): array
    {
        if (preg_match('/(?:enum|set)\s*\((.+)\)/i', $typeExtra, $matches)) {
            $values = explode(',', $matches[1]);

            return array_map(fn ($val) => trim(trim($val), "'"), $values);
        }

        return [];
    }

    /**
     * Check if column is a boolean column based on naming patterns
     */
    private function isBooleanColumn(string $columnName): bool
    {
        $patterns = $this->config->getModelProperty('boolean_column_patterns', [
            'is_', 'has_', 'can_', 'should_', 'will_', 'active', 'enabled', 'published', 'featured', 'verified', 'approved', 'visible', 'archived',
        ]);

        foreach ($patterns as $pattern) {
            // For prefixes, check startsWith
            if (Str::endsWith($pattern, '_')) {
                if (Str::startsWith($columnName, $pattern)) {
                    return true;
                }
            } else {
                // For exact words, check if it's the full word, not a substring
                // But exclude timestamp columns like published_at, created_at, etc.
                if ($columnName === $pattern || (Str::startsWith($columnName, $pattern.'_') && ! Str::endsWith($columnName, '_at'))) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if column is a status column
     */
    private function isStatusColumn(string $columnName): bool
    {
        $patterns = $this->config->getModelProperty('status_column_patterns', [
            'status', 'state', 'type', 'category', 'kind', 'mode',
        ]);

        foreach ($patterns as $pattern) {
            if (Str::contains($columnName, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if column is a foreign key
     */
    private function isForeignKey(string $columnName): bool
    {
        return Str::endsWith($columnName, '_id') && $columnName !== 'id';
    }

    /**
     * Check if column is searchable
     */
    private function isSearchableColumn(string $columnName, string $dbType): bool
    {
        $searchableTypes = ['varchar', 'char', 'text', 'longtext', 'mediumtext', 'tinytext', 'string'];
        $searchablePatterns = $this->config->getModelProperty('searchable_column_patterns', [
            'name', 'title', 'description', 'content', 'body', 'summary', 'subject', 'message', 'comment', 'note', 'email', 'username', 'slug',
        ]);

        if (! in_array($dbType, $searchableTypes)) {
            return false;
        }

        foreach ($searchablePatterns as $pattern) {
            if (Str::contains($columnName, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if column is numeric
     */
    private function isNumericColumn(string $dbType): bool
    {
        return in_array($dbType, [
            'integer', 'int', 'bigint', 'smallint', 'tinyint', 'mediumint',
            'real', 'float', 'double', 'decimal', 'numeric',
        ]);
    }
}
