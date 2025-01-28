# Changelog

## [Unreleased]

### Fixed
- Fixed `ArgumentCountError` in `GenerateModels` command by properly injecting `FileService` into `ModelGenerator`

## [v0.2.8] - 2025-01-29

### Added
- New `NamespaceService` class to handle namespace operations

### Changed
- Refactored `ValidateModelNamespaces` command to use services and improve error handling
- Moved namespace operations to dedicated `NamespaceService`
- Fixed error message consistency in namespace validation

## [v0.2.7] - 2025-01-28

### Added
- New `FileService` class to handle file operations
- New `ModelService` class to handle model-related operations
- Custom exceptions for better error handling (`ModelNotFoundException`, `InvalidInputException`)
- Improved dependency injection in `GenerateResources` command

### Changed
- Refactored `GenerateResources` command to improve maintainability
- Refactored `ModelGenerator` for better code organization and error handling
- Moved file operations to dedicated `FileService`
- Moved model operations to dedicated `ModelService`
- Improved error handling with custom exceptions
- Enhanced test assertions for resource generation

### Fixed
- Fixed model file content corruption in resource generation
- Improved SchemaReader initialization to properly handle SQLite databases
- Fixed test output to be more readable and maintainable

## [v0.2.6] - 2025-01-23

### Added
- New `wink:validate-namespaces` command to verify and fix model namespaces
- PSR-4 namespace validation for model files
- Automatic namespace correction with --fix option
- Backup creation before modifying files
- Support for recursive directory scanning
- Detailed reporting of namespace mismatches

## [v0.2.5] - 2025-01-22

### Fixed
- Fixed database driver detection to properly handle SQLite and MySQL connections
- Added better error messages for missing or invalid database configurations
- Fixed SQLite database existence check before setting PRAGMA query_only
- Improved error messages for SQLite database path configuration

### Added
- Added detailed SQLite and MySQL configuration examples to documentation
- Added warning about SQLite `url` key in connection configuration

## [v0.2.4] - 2025-01-22

### Fixed
- Fixed database driver detection to properly handle SQLite and MySQL connections
- Added better error messages for missing or invalid database configurations
- Fixed SQLite database existence check before setting PRAGMA query_only
- Improved error messages for SQLite database path configuration

## [v0.2.3] - 2025-01-22

### Added
- Added PHP 8.4 support to test matrix
- Verified compatibility with PHP 8.4

### Changed
- Updated GitHub Actions workflow to test against PHP 8.2, 8.3, and 8.4

## [v0.2.2] - 2025-01-22

### Added
- Connection-based directory structure for models and factories
- New `--factory-directory` option for custom factory output location

### Changed
- Models are now generated in `app/Models/GeneratedModels/{connection}`
- Factories are now generated in `database/factories/GeneratedFactories/{connection}`
- Updated tests to verify connection-based directory structure

## [v0.2.1] - 2025-01-22

### Changed
- Updated GitHub Actions workflow to only test Laravel 11
- Removed Laravel 10 from CI test matrix
- Updated CI PHP versions to 8.2 and 8.3 only

## [v0.2.0] - 2025-01-22

### Added
- Support for model generation with relationships
- Enhanced type detection for database columns
- Added relationship method generation based on foreign keys
- Added support for belongs to, has many, and has one relationships
- Added read-only mode for MySQL and SQLite schema readers

### Changed
- Improved code organization in GenerateModels command
- Enhanced test coverage for relationship generation
- Updated documentation with relationship examples
- Implemented read-only transactions in MySQL schema reader
- Implemented PRAGMA query_only in SQLite schema reader
- Dropped support for Laravel 10 to focus on Laravel 11 features
- Updated minimum PHP requirement to 8.2
- Refactored SQLite schema reader to use raw SQL queries

### Security
- Prevented any potential database modifications during schema reading
- Added safeguards to ensure schema readers can only perform read operations

### Breaking Changes
- Now requires Laravel 11 and PHP 8.2
- Removed support for Laravel 10

## [v0.1.4]

### Added
- Factory generation feature now working
- Added factory directory at database/factories/GeneratedFactories
- Added Faker method mapping for common column types
- Added factory generation test
- Added factory cleanup in tests

### Changed
- Updated factory namespace to Database\Factories\GeneratedFactories
- Improved factory file structure and organization
- Enhanced test coverage for factory generation

### Fixed
- Fixed non-functioning --with-factories flag
- Fixed factory directory creation and cleanup

## [v0.1.3]

### Added
- Laravel 11 support
- Improved type hints and return types
- Added proper test configuration

### Changed
- Updated PHPUnit configuration to latest schema
- Migrated from doc-comments to attributes in tests
- Updated dependency constraints for Laravel 11

### Fixed
- Fixed PHPUnit deprecation warnings
- Fixed test environment configuration

[Previous version changes...] 