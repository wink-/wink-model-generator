# Laravel Model Generator

[![Latest Version on Packagist](https://img.shields.io/packagist/v/wink/wink-model-generator.svg)](https://packagist.org/packages/wink/wink-model-generator)
[![Tests](https://github.com/wink-/wink-model-generator/actions/workflows/tests.yml/badge.svg)](https://github.com/wink-/wink-model-generator/actions/workflows/tests.yml)
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

## Installation

You can install the package via composer:

```bash
composer require wink/wink-model-generator --dev
```

The package will automatically register its service provider.

## Configuration

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

# Include relationships
php artisan wink:generate-models --with-relationships

# Generate model factories
php artisan wink:generate-models --with-factories

# Generate validation rules
php artisan wink:generate-models --with-rules

# Combine options
php artisan wink:generate-models --connection=mysql --directory=app/Models/Generated --with-relationships
```

### Directory Option

The `--directory` option accepts either:
- A full path (e.g., `/path/to/models`)
- A relative path from the project root (e.g., `app/Models/Generated`)

If no directory is specified, models will be generated in `app/Models/GeneratedModels`.

Examples:
```bash
# Using absolute path
php artisan wink:generate-models --directory=/var/www/html/app/Models/Custom

# Using relative path
php artisan wink:generate-models --directory=app/Models/Admin

# Using path with spaces (quote the path)
php artisan wink:generate-models --directory="app/Models/Generated Models"
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
```

## Testing

```bash
composer test
```

## Requirements

- PHP ^8.1
- Laravel ^10.0|^11.0

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) and [CODE_OF_CONDUCT](.github/CODE_OF_CONDUCT.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Josh Wingerd](https://github.com/wink-)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
