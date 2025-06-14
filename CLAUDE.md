# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Common Commands

### Testing
- Run all tests: `vendor/bin/phpunit`
- Run SQLite tests: `vendor/bin/phpunit` (default)
- Run MySQL tests: `vendor/bin/phpunit -c phpunit.mysql.xml`
- Run both test suites: `composer run test-all`
- Run single test file: `vendor/bin/phpunit tests/path/to/TestFile.php`

### Code Quality
- Format code: `vendor/bin/pint`
- Check code style (CI): `vendor/bin/pint --test`
- Static analysis: `vendor/bin/phpstan analyse`
- Mutation testing: `vendor/bin/infection --min-msi=80`

### Package Commands
- Generate models: `php artisan wink:generate-models`
- Validate namespaces: `php artisan wink:validate-model-namespaces`

## Architecture Overview

This is a Laravel package that generates Eloquent models and factories from existing database schemas, supporting both MySQL and SQLite databases.

### Core Components

**Command Layer** (`src/Commands/`):
- `GenerateModels`: Main command for model generation with extensive options
- `ValidateModelNamespaces`: Validates PSR-4 namespace compliance

**Database Schema Reading** (`src/Database/`):
- Abstract `SchemaReader` with database-specific implementations
- `MySqlSchemaReader` and `SqliteSchemaReader` handle schema introspection
- Auto-detects relationships from foreign key constraints

**Generation Layer** (`src/Generators/`):
- `ModelGenerator`: Creates Eloquent models with relationships, validation rules, and PHPDoc
- `FactoryGenerator`: Creates model factories based on schema constraints

**Services** (`src/Services/`):
- `FileService`: Handles file operations and directory management
- `ModelService`: Business logic for model processing
- `NamespaceService`: PSR-4 namespace validation and path resolution

### Key Patterns

**Connection-Based Organization**: Generated files are organized by database connection name to support multi-database projects (`app/Models/GeneratedModels/{connection}/`).

**Template System**: Uses stub files in `stubs/` directory for model and factory templates with placeholder replacement.

**Configuration-Driven**: Centralized configuration through `GeneratorConfig` class, supports both config file and command-line overrides.

### Testing Strategy

- Uses Orchestra Testbench for Laravel package testing
- Separate test configurations for SQLite (`phpunit.xml.dist`) and MySQL (`phpunit.mysql.xml`)
- In-memory SQLite database for fast unit tests
- Feature tests validate end-to-end command functionality
- Unit tests cover individual components in isolation

### Development Standards

- PHP 8.3+ with strict typing (`declare(strict_types=1)`)
- Laravel Pint for code formatting (Laravel preset)
- PHPStan at max level for static analysis
- PSR-4 autoloading with `Wink\ModelGenerator` namespace
- Spatie Laravel Package Tools for service provider boilerplate