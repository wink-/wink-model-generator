# TODO List

## Documentation Improvements
- [x] Add a "Quick Start" section with common use cases
- [x] Add troubleshooting guide for common issues
- [x] Add section about namespace customization
- [x] Document excluded tables configuration
- [x] Add examples of relationship generation
- [x] Add section about model factory customization
- [x] Add SQLite-specific configuration guide
- [x] Add examples for testing database scenarios
- [x] Add section about model customization
- [x] Add warnings about production database safety

## Feature Enhancements
- [x] Add support for custom model stubs
- [x] Split resource generation into separate package
- [x] Package split completed - focused on model generation
- [x] **Enhanced Model Property Generation** - Comprehensive Laravel model properties with smart detection
- [x] **Auto-detect primary keys** - Smart primary key detection from schema instead of hardcoding 'id'
- [x] **Auto-detect key types** - Automatically set `$keyType` based on column type (string for UUIDs, int for integers)
- [x] **Auto-detect incrementing** - Set `$incrementing = false` for UUID/string primary keys
- [x] **Soft deletes detection** - Auto-detect `deleted_at` columns and add SoftDeletes trait
- [x] **Enhanced security features** - Automatically hide sensitive fields (passwords, tokens, etc.)
- [x] **Flexible mass assignment** - Choose between `$fillable` and `$guarded` approaches
- [x] **Complete property support** - Generate `$hidden`, `$visible`, `$attributes`, `$with`, `$perPage`, etc.
- [x] **Advanced configuration** - Comprehensive config file with detailed model property options
- [x] **Educational benefits** - Config file teaches developers about available Laravel model properties
- [ ] Add PostgreSQL support
- [ ] Add option to generate model observers
- [ ] Add support for database views
- [ ] Add option to generate model interfaces
- [x] Add better SQLite type detection and mapping
- [x] Add support for SQLite JSON columns
- [x] Add support for in-memory database testing
- [x] Add support for Laravel's enum attributes
- [ ] Add option to generate model events
- [ ] Add support for Laravel's custom casts
- [ ] Add option to generate model policies
- [ ] Add support for model broadcasting
- [ ] Add option to generate model scopes
- [x] Add safety checks for production environments

## Testing Improvements
- [x] Add test for multiple tables generation
- [x] Add test for custom namespace handling
- [x] Add test for relationship generation
- [x] Add test for factory generation
- [x] Add test for different database drivers
- [x] Add test for edge cases (empty tables, reserved words)
- [x] Add test for SQLite-specific features
- [x] Add test for in-memory database operations
- [x] Add test for large table structures
- [x] Add test for all Laravel column types

## Code Quality
- [x] Add Pint configuration
- [x] Add static analysis with PHPStan
- [x] Add mutation testing with Infection
- [x] Improve type hints and return types

## CI/CD
- [ ] Add automated release workflow
- [x] Add code coverage reporting
- [ ] Add automated dependency updates
- [x] Add cross-PHP version testing
- [x] Add cross-Laravel version testing
