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
- Compatible with Laravel 11.x

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
    | Note: Generated relationship methods currently assume that related models
    | reside in the `App\Models` namespace. If you customize `model_namespace`
    | to something else, you may need to manually adjust the namespaces in the
    | generated relationship methods.
    |
    */
    'model_namespace' => 'App\\Models',

    /*
    |--------------------------------------------------------------------------
    | Model Output Path
    |--------------------------------------------------------------------------
    |
    | This value defines the default base path for models and is used to
    | determine their PHP namespace (e.g., `App\Models` if `model_path` is
    | `app/Models`). By default, models will be placed in a
    | `GeneratedModels/{connection_name}` subdirectory within this path
    | (e.g., `app/Models/GeneratedModels/mysql`). This final output path can be
    | fully customized using the `--directory` command-line option.
    |
    */
    'model_path' => 'app/Models',

    /*
    |--------------------------------------------------------------------------
    | Factory Output Path
    |--------------------------------------------------------------------------
    |
    | This value defines the default base output path for generated factory files.
    | By default, factories will be placed in a
    | `GeneratedFactories/{connection_name}` subdirectory within this path
    | (e.g., `database/factories/GeneratedFactories/mysql`). This output path
    | can be fully customized using the `--factory-directory` command-line option.
    |
    */
    'factory_path' => 'database/factories',

    /*
    |--------------------------------------------------------------------------
    | Validation Rules
    |--------------------------------------------------------------------------
    |
    | When true, the generator will add a `public static function rules(): array`
    | method to the model class. This method contains Laravel validation rules
    | based on the column types and constraints.
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
