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
- [ ] Add PostgreSQL support
- [ ] Add support for soft deletes detection (PostgrSQL support first)
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
