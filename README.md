# Laravel Model Generator

[![Latest Version on Packagist](https://img.shields.io/packagist/v/wink/wink-model-generator.svg)](https://packagist.org/packages/wink/wink-model-generator)
[![Tests](https://github.com/wink-/wink-model-generator/actions/workflows/tests.yml/badge.svg?branch=main)](https://github.com/wink-/wink-model-generator/actions/workflows/tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/wink/wink-model-generator.svg)](https://packagist.org/packages/wink/wink-model-generator)
[![License](https://img.shields.io/packagist/l/wink/wink-model-generator.svg)](https://packagist.org/packages/wink/wink-model-generator)

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
- Connection-based directory structure for multi-database projects
- PSR-4 namespace validation and auto-correction
- Compatible with PHP 8.2, 8.3, and 8.4

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

### Database Support

The package supports both MySQL and SQLite databases. Configure your connections in `config/database.php`:

#### MySQL Configuration
```php
'mysql-connection' => [
    'driver' => 'mysql',
    'host' => env('DB_HOST', '127.0.0.1'),
    'database' => env('DB_DATABASE'),
    'username' => env('DB_USERNAME'),
    'password' => env('DB_PASSWORD'),
],
```

#### SQLite Configuration
```php
'sqlite-connection' => [
    'driver' => 'sqlite',
    'database' => database_path('your-database.sqlite'),
    'prefix' => '',
    'foreign_key_constraints' => true,
],
```

Note: For SQLite, use absolute paths and avoid using the `url` key in the connection config.

## Usage

The package provides two main commands for generating models and API resources.

### Model Generation

Generate Eloquent models from your database schema:

```bash
# Basic usage
php artisan wink:generate-models

# Common Options
--connection=mysql          # Specify database connection (default: sqlite)
--directory=path/to/models  # Custom output directory for models
--with-relationships       # Include relationships
--with-rules              # Generate validation rules
--with-factories          # Generate model factories
--factory-directory=path  # Custom output directory for factories
```

### API Resource Generation

Generate Laravel API Resources from your models:

```bash
# Generate resources for all models in a directory
php artisan wink:generate-resources --directory=app/Models/Admin

# Common Options
--model=path/to/model.php  # Generate for a specific model
--collection              # Generate collection resources
--output=path            # Custom output directory for resources
```

### Directory Structure

The package organizes generated files by connection name to prevent conflicts in multi-database projects:

```
app/
├── Models/
│   └── GeneratedModels/
│       ├── mysql/           # Models for MySQL connection
│       └── sqlite/          # Models for SQLite connection
└── database/
    └── factories/
        └── GeneratedFactories/
            ├── mysql/       # Factories for MySQL connection
            └── sqlite/      # Factories for SQLite connection
```

Directory options accept either full paths or paths relative to the project root. If not specified:
- Models: `app/Models/GeneratedModels/{connection}`
- Factories: `database/factories/GeneratedFactories/{connection}`

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
