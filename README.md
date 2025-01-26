# Laravel Model Generator

[![Latest Version on Packagist](https://img.shields.io/packagist/v/wink/wink-model-generator.svg)](https://packagist.org/packages/wink/wink-model-generator)
[![Tests](https://github.com/wink-/wink-model-generator/actions/workflows/tests.yml/badge.svg?branch=main)](https://github.com/wink-/wink-model-generator/actions/workflows/tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/wink/model-generator.svg)](https://packagist.org/packages/wink/wink-model-generator)
[![License](https://img.shields.io/packagist/l/wink/model-generator.svg)](https://packagist.org/packages/wink/wink-model-generator)

A powerful Laravel package that automatically generates Eloquent models from your existing database schema, supporting both MySQL and SQLite databases.

## Features

- Supports both MySQL and SQLite databases (including in-memory SQLite for testing)
- Generates complete model files with proper namespacing
- Auto-detects relationships from foreign keys
- Configurable model generation options
- Generates PHPDoc properties for better IDE support
- Includes validation rules based on schema
- Handles custom database connections
- Optional model factory generation
- Optional policy generation with customizable namespaces
- Connection-based directory structure for multi-database projects
- Compatible with PHP 8.2, 8.3, and 8.4

## Installation

You can install the package via composer:

```bash
composer require wink/wink-model-generator --dev
```

The package will automatically register its service provider.

## Configuration

### Database Connections

The package supports both MySQL and SQLite databases. Make sure your database connections are properly configured in `config/database.php`.

#### SQLite Configuration
For SQLite databases, ensure you:
1. Use absolute paths for the database file
2. Don't use the `url` key in the connection config (this can override the database path)
3. Use Laravel's `database_path()` helper for proper path resolution

Example SQLite configuration:
```php
'sqlite-connection' => [
    'driver' => 'sqlite',
    'database' => database_path('your-database.sqlite'),
    'prefix' => '',
```

### Policy Generation

You can generate Laravel policies for your models using the `--with-policies` option:

```bash
php artisan wink:generate-models --with-policies
```

Customize policy generation in your `config/model-generator.php`:

```php
return [
    'policy_namespace' => 'App\Policies',  // Namespace for generated policies
    'policy_path' => app_path('Policies'),  // Output directory for policy files
];
```

Generated policies include standard CRUD methods (viewAny, view, create, update, delete) with proper type-hinting and namespacing.
    'foreign_key_constraints' => true,
],
```

#### MySQL Configuration
For MySQL databases, configure as normal:
```php
'mysql-connection' => [
    'driver' => 'mysql',
    'host' => env('DB_HOST', '127.0.0.1'),
    'database' => env('DB_DATABASE'),
    'username' => env('DB_USERNAME'),
    'password' => env('DB_PASSWORD'),
    // ...
],
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag="model-generator-config"
```

## Usage

Basic usage:

```bash
php artisan wink:generate-models
```

### Command Options

```bash
# Specify database connection (default: sqlite)
php artisan wink:generate-models --connection=mysql

# Generate models in a specific directory
php artisan wink:generate-models --directory=/path/to/models

# Generate factories in a specific directory
php artisan wink:generate-models --factory-directory=/path/to/factories

# Include relationships
php artisan wink:generate-models --with-relationships

# Generate model factories
php artisan wink:generate-models --with-factories

# Generate validation rules
php artisan wink:generate-models --with-rules

# Combine options
php artisan wink:generate-models --connection=mysql --directory=app/Models/Custom --with-relationships
```

### Directory Structure

By default, the package organizes generated files by connection name to prevent conflicts in multi-database projects:

```
app/
├── Models/
│   └── GeneratedModels/
│       ├── mysql/           # Models for MySQL connection
│       │   ├── User.php
│       │   └── Post.php
│       └── sqlite/          # Models for SQLite connection
│           ├── User.php
│           └── Comment.php
└── database/
    └── factories/
        └── GeneratedFactories/
            ├── mysql/       # Factories for MySQL connection
            │   ├── UserFactory.php
            │   └── PostFactory.php
            └── sqlite/      # Factories for SQLite connection
                ├── UserFactory.php
                └── CommentFactory.php
```

### Directory Options

The `--directory` and `--factory-directory` options accept either:
- A full path (e.g., `/path/to/models`)
- A relative path from the project root (e.g., `app/Models/Generated`)

If no directories are specified:
- Models will be generated in `app/Models/GeneratedModels/{connection}`
- Factories will be generated in `database/factories/GeneratedFactories/{connection}`

Examples:
```bash
# Using absolute paths
php artisan wink:generate-models --directory=/var/www/html/app/Models/Custom --factory-directory=/var/www/html/database/factories/Custom

# Using relative paths
php artisan wink:generate-models --directory=app/Models/Admin --factory-directory=database/factories/Admin

# Using paths with spaces (quote the paths)
php artisan wink:generate-models --directory="app/Models/Generated Models" --factory-directory="database/factories/Generated Factories"

# Using default connection-based directories
php artisan wink:generate-models --connection=mysql --with-factories
# Will generate:
# - Models in app/Models/GeneratedModels/mysql
# - Factories in database/factories/GeneratedFactories/mysql
```

## Generated Model Features

Each generated model includes:

- Table name and connection configuration
- Primary key settings
- Timestamps configuration
- Date format settings
- Fillable attributes
- Hidden/visible attributes
- Attribute casting
- Default attribute values
- Validation rules
- PHPDoc property definitions
- Foreign key relationships

Example generated model:

```php
/**
 * User Model
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|\DateTime $email_verified_at
 * @property \DateTime $created_at
 * @property \DateTime $updated_at
 */
class User extends Model
{
    protected $connection = 'mysql';
    protected $table = 'users';
    
    protected $fillable = [
        'name',
        'email',
        'password'
    ];
    
    protected $casts = [
        'email_verified_at' => 'datetime'
    ];
    
    public static $rules = [
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'string', 'email', 'max:255', 'unique:users']
    ];
}

## Testing

```bash
composer test
```

## Requirements

- PHP 8.2 or higher (including PHP 8.3 and 8.4)
- Laravel 11.x

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) and [CODE_OF_CONDUCT](.github/CODE_OF_CONDUCT.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Wink](https://github.com/wink-)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
