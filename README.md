# Laravel Model Generator

[![Latest Version on Packagist](https://img.shields.io/packagist/v/wink/model-generator.svg)](https://packagist.org/packages/wink/model-generator)
[![Tests](https://github.com/wink-/wink-model-generator/actions/workflows/tests.yml/badge.svg)](https://github.com/wink-/wink-model-generator/actions/workflows/tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/wink/model-generator.svg)](https://packagist.org/packages/wink/model-generator)
[![License](https://img.shields.io/packagist/l/wink/model-generator.svg)](https://packagist.org/packages/wink/model-generator)

A powerful Laravel package that automatically generates Eloquent models from your existing database schema, supporting both MySQL and SQLite databases.

## Features

- Supports both MySQL and SQLite databases
- Generates complete model files with proper namespacing
- Auto-detects relationships from foreign keys
- Configurable model generation options
- Generates PHPDoc properties for better IDE support
- Includes validation rules based on schema
- Handles custom database connections
- Optional model factory generation

## Installation

```bash
composer require wink/model-generator
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
php artisan app:generate-models
```

With options:

```bash
# Specify database connection
php artisan app:generate-models --connection=mysql

# Generate models in a subdirectory
php artisan app:generate-models --directory=Admin

# Include relationships
php artisan app:generate-models --with-relationships

# Generate model factories
php artisan app:generate-models --with-factories

# Generate validation rules
php artisan app:generate-models --with-rules

# Combine options
php artisan app:generate-models --connection=mysql --with-relationships --with-rules
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
 * @property string $name
 * @property string $email
 * @property string|\DateTime $email_verified_at
 */
class User extends Model
{
    protected $connection = 'mysql';
    protected $table = 'users';
    protected $fillable = ['name', 'email', 'password'];
    // ...other model properties
}
```

## Configuration Options

Edit `config/model-generator.php` to customize:

- Default database connection
- Excluded tables
- Default paths
- Naming conventions

## Requirements

- PHP ^8.1
- Laravel ^10.0

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This package is open-sourced software licensed under the MIT license.
