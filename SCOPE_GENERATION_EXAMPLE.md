# Model Scope Generation Example

This document demonstrates the scope generation feature implemented in the wink-model-generator package.

## Overview

The ScopeGenerator analyzes database columns and automatically generates appropriate Eloquent query scopes based on column names, types, and common patterns.

## Generated Scope Types

### 1. Boolean Scopes

For boolean columns or columns with boolean-like naming patterns:

```php
// Column: is_active (tinyint(1))
public function scopeActive($query)
{
    return $query->where('is_active', true);
}

public function scopeInactive($query)
{
    return $query->where('is_active', false);
}
```

### 2. Status Scopes

For columns containing status-related keywords:

```php
// Column: status (varchar)
public function scopeByStatus($query, $status)
{
    return $query->where('status', $status);
}
```

### 3. Enum Scopes

For ENUM columns, individual scopes for each value:

```php
// Column: priority ENUM('low', 'medium', 'high')
public function scopeLow($query)
{
    return $query->where('priority', 'low');
}

public function scopeMedium($query)
{
    return $query->where('priority', 'medium');
}

public function scopeHigh($query)
{
    return $query->where('priority', 'high');
}
```

### 4. Date Scopes

For date/datetime columns:

```php
// Column: published_at (datetime)
public function scopeRecent($query, $days = 30)
{
    return $query->where('published_at', '>=', now()->subDays($days));
}

public function scopePublishedAtBetween($query, $startDate, $endDate)
{
    return $query->whereBetween('published_at', [$startDate, $endDate]);
}

public function scopePublishedAtAfter($query, $date)
{
    return $query->where('published_at', '>', $date);
}

public function scopePublishedAtBefore($query, $date)
{
    return $query->where('published_at', '<', $date);
}
```

### 5. Foreign Key Scopes

For foreign key columns (ending with _id):

```php
// Column: user_id (bigint)
public function scopeByUser($query, $id)
{
    return $query->where('user_id', $id);
}
```

### 6. Search Scopes

For text columns with searchable patterns:

```php
// Column: title (varchar)
public function scopeSearchTitle($query, $search)
{
    return $query->where('title', 'LIKE', '%' . $search . '%');
}
```

### 7. Numeric Scopes

For numeric columns:

```php
// Column: price (decimal)
public function scopePriceGreaterThan($query, $value)
{
    return $query->where('price', '>', $value);
}

public function scopePriceLessThan($query, $value)
{
    return $query->where('price', '<', $value);
}

public function scopePriceBetween($query, $min, $max)
{
    return $query->whereBetween('price', [$min, $max]);
}
```

### 8. Timestamp Scopes

Common timestamp-related scopes:

```php
public function scopeCreatedRecently($query, $days = 7)
{
    return $query->where('created_at', '>=', now()->subDays($days));
}

public function scopeUpdatedRecently($query, $days = 7)
{
    return $query->where('updated_at', '>=', now()->subDays($days));
}

public function scopeCreatedToday($query)
{
    return $query->whereDate('created_at', today());
}

public function scopeLatest($query)
{
    return $query->orderBy('created_at', 'desc');
}

public function scopeOldest($query)
{
    return $query->orderBy('created_at', 'asc');
}
```

## Usage

### Command Line

```bash
# Generate models with scopes
php artisan wink:generate-models --with-scopes

# Generate models with timestamp scopes only
php artisan wink:generate-models --with-timestamp-scopes

# Generate models with both scopes and timestamp scopes
php artisan wink:generate-models --with-scopes --with-timestamp-scopes
```

### Configuration

Update your `config/model-generator.php`:

```php
'model_properties' => [
    'auto_generate_scopes' => true,
    'auto_generate_timestamp_scopes' => true,
    
    // Customize boolean scope patterns
    'boolean_scope_patterns' => [
        'is_active' => ['active', 'inactive'],
        'is_published' => ['published', 'unpublished'],
        'is_featured' => ['featured', 'notFeatured'],
    ],
    
    // Customize column patterns
    'boolean_column_patterns' => [
        'is_', 'has_', 'can_', 'should_', 'will_', 'active', 'enabled'
    ],
    
    'status_column_patterns' => [
        'status', 'state', 'type', 'category', 'kind', 'mode'
    ],
    
    'searchable_column_patterns' => [
        'name', 'title', 'description', 'content', 'email', 'username'
    ],
],
```

## Example Generated Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    use HasFactory;

    protected $connection = 'mysql';
    protected $table = 'articles';
    
    protected $fillable = [
        'title',
        'content',
        'is_published',
        'status',
        'user_id',
        'published_at',
        'views'
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    // Boolean scopes
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeUnpublished($query)
    {
        return $query->where('is_published', false);
    }

    // Status scopes
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    // Foreign key scopes
    public function scopeByUser($query, $id)
    {
        return $query->where('user_id', $id);
    }

    // Search scopes
    public function scopeSearchTitle($query, $search)
    {
        return $query->where('title', 'LIKE', '%' . $search . '%');
    }

    // Date scopes
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('published_at', '>=', now()->subDays($days));
    }

    // Numeric scopes
    public function scopeViewsGreaterThan($query, $value)
    {
        return $query->where('views', '>', $value);
    }

    // Timestamp scopes
    public function scopeCreatedRecently($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeLatest($query)
    {
        return $query->orderBy('created_at', 'desc');
    }
}
```

## Usage in Application

```php
// Using the generated scopes
$publishedArticles = Article::published()->get();
$recentArticles = Article::recent(7)->get();
$userArticles = Article::byUser(1)->get();
$searchResults = Article::searchTitle('laravel')->get();
$popularArticles = Article::viewsGreaterThan(1000)->get();
$latestArticles = Article::latest()->get();

// Combining scopes
$articles = Article::published()
    ->recent(30)
    ->byUser(1)
    ->viewsGreaterThan(100)
    ->latest()
    ->get();
```

## Priority Order

The scope generator follows this priority order to avoid conflicts:

1. Boolean scopes (highest priority)
2. Enum scopes 
3. Status scopes
4. Date/DateTime scopes
5. Foreign key scopes (priority over numeric)
6. Search scopes
7. Numeric scopes (lowest priority)

This ensures that columns like `user_id` generate foreign key scopes rather than numeric scopes, and boolean columns don't generate multiple conflicting scope types.