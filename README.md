# Laravel Model Generator

[![Latest Version on Packagist](https://img.shields.io/packagist/v/wink/wink-model-generator.svg)](https://packagist.org/packages/wink/wink-model-generator)
[![Tests](https://github.com/wink-/wink-model-generator/actions/workflows/tests.yml/badge.svg?branch=main)](https://github.com/wink-/wink-model-generator/actions/workflows/tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/wink/wink-model-generator.svg)](https://packagist.org/packages/wink/wink-model-generator)
[![License](https://img.shields.io/packagist/l/wink/wink-model-generator.svg)](https://packagist.org/packages/wink/wink-model-generator)

A focused Laravel package that automatically generates Eloquent models and factories from your existing database schema. For API Resource and Controller generation, check out our companion package [wink-resource-generator](https://github.com/wink-/wink-resource-generator).

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

## Related Packages

- [wink-resource-generator](https://github.com/wink-/wink-resource-generator) - Generate API Resources, Controllers, and Routes for your Laravel models

## Configuration

You can publish the configuration file with:

```bash
php artisan vendor:publish --provider="Wink\ModelGenerator\ModelGeneratorServiceProvider" --tag="config"
```

This will create a `config/model-generator.php` file with the following options:

```php
return [
    /*
    |--------------------------------------------------------------------------
    | Model Namespace
    |--------------------------------------------------------------------------
    |
    | This value defines the default namespace for generated model classes.
    | You can override this on a per-model basis using the --namespace option.
    |
    */
    'model_namespace' => 'App\\Models',

    /*
    |--------------------------------------------------------------------------
    | Model Output Path
    |--------------------------------------------------------------------------
    |
    | This value defines the default output path for generated model files.
    | The path should be relative to the project root.
    |
    */
    'model_path' => 'app/Models',

    /*
    |--------------------------------------------------------------------------
    | Factory Output Path
    |--------------------------------------------------------------------------
    |
    | This value defines the default output path for generated factory files.
    | The path should be relative to the project root.
    |
    */
    'factory_path' => 'database/factories',

    /*
    |--------------------------------------------------------------------------
    | Validation Rules
    |--------------------------------------------------------------------------
    |
    | When true, the generator will add Laravel validation rules as PHPDoc
    | annotations based on the column types and constraints.
    |
    */
    'generate_validation_rules' => true,
];
```

## Usage

The package provides two main commands for generating models and factories.

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
