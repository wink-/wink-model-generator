# Laravel Model Generator

[![Latest Version on Packagist](https://img.shields.io/packagist/v/wink/wink-model-generator.svg)](https://packagist.org/packages/wink/wink-model-generator)
[![Tests](https://github.com/wink-/wink-model-generator/actions/workflows/tests.yml/badge.svg?branch=main)](https://github.com/wink-/wink-model-generator/actions/workflows/tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/wink/wink-model-generator.svg)](https://packagist.org/packages/wink/wink-model-generator)
[![License](https://img.shields.io/packagist/l/wink/wink-model-generator.svg)](https://packagist.org/packages/wink/wink-model-generator)

A focused Laravel package that automatically generates Eloquent models and factories from your existing database schema. For API Resource and Controller generation, check out our companion package [wink-resource-generator](https://github.com/wink-/wink-resource-generator).

## Features

- Supports MySQL and SQLite databases (including in-memory SQLite for testing)
- Generates complete model files with proper namespacing and **comprehensive Laravel model properties**
- **Smart property detection**: auto-detects primary keys, key types, incrementing, soft deletes
- **Enhanced security**: automatically hides sensitive fields (passwords, tokens, etc.)
- **Flexible mass assignment**: configurable `$fillable` vs `$guarded` approaches
- **Complete property support**: `$hidden`, `$visible`, `$attributes`, `$with`, `$perPage`, etc.
- Auto-detects relationships from foreign keys
- Configurable model generation options via comprehensive config file
- Generates PHPDoc properties for better IDE support
- Includes validation rules based on schema
- Handles custom database connections
- Optional model factory generation
- Connection-based directory structure for multi-database projects
- PSR-4 namespace validation and auto-correction
- Compatible with PHP 8.3+ and Laravel 11+

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

This will create a `config/model-generator.php` file with comprehensive configuration options:

```php
return [
    // Basic configuration
    'default_connection' => 'mysql',
    'excluded_tables' => ['migrations', 'failed_jobs', /* ... */],

    // Model Property Generation Options
    'model_properties' => [
        // Auto-detect primary key from schema instead of hardcoding 'id'
        'auto_detect_primary_key' => true,

        // Generate $hidden array for sensitive fields (password, token, etc.)
        'auto_hidden_fields' => true,
        'hidden_field_patterns' => ['password', 'token', 'secret', 'key', 'hash'],

        // Generate $guarded array (alternative to $fillable)
        'use_guarded_instead_of_fillable' => false,
        'guarded_fields' => ['id', 'created_at', 'updated_at'],

        // Pagination settings
        'per_page' => null, // Set to integer to override default pagination

        // Date format customization
        'date_format' => 'Y-m-d H:i:s', // Laravel default

        // Auto-generate $attributes for default values based on schema
        'auto_default_attributes' => true,

        // Auto-generate $with for common relationships
        'auto_eager_load' => false,
        'eager_load_relationships' => [], // e.g., ['user', 'category']

        // Soft delete detection
        'auto_detect_soft_deletes' => true,

        // Generate $visible array (alternative to $hidden)
        'use_visible_instead_of_hidden' => false,
    ],

    // Custom field type mappings
    'custom_casts' => [
        // Add custom cast mappings here
    ],

    // Validation rules generation
    'validation' => [
        'generate_rules' => true,
        'strict_rules' => false,
        'include_unique_rules' => true,
    ],
];
```

### Model Property Generation

The package now automatically generates comprehensive Laravel model properties based on your database schema:

**Smart Detection:**
- **Primary Keys**: Auto-detects from schema instead of hardcoding 'id'
- **Key Types**: Sets `$keyType` to 'string' for UUID/varchar keys, 'int' for integers
- **Incrementing**: Sets `$incrementing = false` for UUID/string primary keys
- **Soft Deletes**: Detects `deleted_at` columns and adds SoftDeletes trait
- **Timestamps**: Detects `created_at`/`updated_at` columns

**Security & Visibility:**
- **Hidden Fields**: Automatically hides sensitive fields matching patterns (password, token, secret, etc.)
- **Visible Fields**: Option to use `$visible` instead of `$hidden`

**Mass Assignment:**
- **Fillable vs Guarded**: Choose between `$fillable` and `$guarded` approaches
- **Smart Exclusions**: Automatically excludes primary keys and timestamps from fillable

**Advanced Properties:**
- **Default Attributes**: Generates `$attributes` array from schema default values
- **Pagination**: Configure custom `$perPage` values
- **Eager Loading**: Auto-generate `$with` for common relationships
- **Date Formatting**: Customize `$dateFormat`

## Usage

The package provides two main commands for generating models and factories.

### Model Generation

Generate Eloquent models from your database schema:

```bash
# Basic usage
php artisan wink:generate-models

# Common Options
--connection=sqlite         # Specify database connection (default: sqlite)
--directory=path/to/models  # Custom output directory for models
--with-relationships       # Include relationships
--with-rules              # Generate validation rules
--with-factories          # Generate model factories
--factory-directory=path  # Custom output directory for factories
```

### Directory Structure

By default, the package organizes generated model files into a `GeneratedModels/{connection_name}` subdirectory. This subdirectory is created within the path defined by the `model_path` configuration option (which defaults to `app/Models`, resulting in a final default path like `app/Models/GeneratedModels/mysql`). A similar structure is used for factories based on the `factory_path` configuration.

The typical default directory structure looks like this:
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

The `--directory` command-line option allows you to specify a custom output directory for models, completely overriding the default `{model_path}/GeneratedModels/{connection_name}` structure. Similarly, the `--factory-directory` option overrides the default for factories. These options accept either full paths or paths relative to the project root.

If the relevant `--directory` or `--factory-directory` options are not specified, the defaults are:
- Models: `{model_path}/GeneratedModels/{connection_name}` (e.g., `app/Models/GeneratedModels/mysql` using the default `model_path`)
- Factories: `{factory_path}/GeneratedFactories/{connection_name}` (e.g., `database/factories/GeneratedFactories/mysql` using the default `factory_path`)

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
