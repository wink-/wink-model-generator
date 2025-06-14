# Package Split Plan

## Overview

Split the current `wink-model-generator` package into two focused packages:

1. `wink-model-generator` (renamed to `wink-models`)
   - Core model generation functionality
   - Database schema analysis
   - Model relationship detection
   - Factory generation
   - Validation rules generation

2. `wink-resource-generator` (new package)
   - API Resource generation
   - Controller generation
   - Route generation
   - OpenAPI/Swagger documentation

## Phase 1: Prepare Current Package

### Code Organization
1. Identify and separate concerns
   - Move resource-related code to a new namespace
   - Isolate shared utilities and helpers
   - Create clear boundaries between model and resource generation

### Dependencies
1. Review and split dependencies in composer.json
   - Identify dependencies specific to model generation
   - Identify dependencies specific to resource generation
   - List shared dependencies

### Configuration
1. Split configuration files
   - Separate model generation config
   - Create new resource generation config
   - Extract shared configuration options

## Phase 2: Create New Package

### Setup New Package
1. Create new repository `wink-resource-generator`
2. Initialize package structure
   - Set up PSR-4 autoloading
   - Create initial composer.json
   - Set up testing infrastructure
   - Add GitHub workflows

### Move Code
1. Transfer resource-related code
   - Move Commands/GenerateResources.php
   - Move resource-related tests
   - Move resource templates
   - Transfer relevant documentation

### Update Dependencies
1. Set up composer.json for new package
   - Required PHP version
   - Laravel framework requirements
   - Testing dependencies
   - Development tools

## Phase 3: Update Existing Package

### Cleanup and Refactor
1. Remove resource-related code
2. Update namespace to reflect new focus
3. Update configuration
4. Clean up unnecessary dependencies

### Documentation Updates
1. Update README.md
2. Update CHANGELOG.md
3. Update composer.json metadata
4. Update documentation references

## Phase 4: Testing and Validation

### Test Coverage
1. Ensure both packages have comprehensive tests
   - Unit tests
   - Integration tests
   - Feature tests
   - Edge cases

### Documentation
1. Create new documentation for both packages
   - Installation guides
   - Usage examples
   - Configuration options
   - API references

## Phase 5: Release and Migration

### Version Management
1. Determine version numbers
   - Major version bump for model generator
   - Initial release for resource generator
   - Document breaking changes

### Migration Guide
1. Create migration documentation
   - Steps to upgrade from old package
   - Changes in configuration
   - New features and improvements
   - Breaking changes

### Release Tasks
1. Tag releases
2. Update Packagist
3. Announce changes
4. Update all references

## Timeline and Milestones

1. Phase 1: 1-2 days
   - Code organization and analysis
   - Dependency review
   - Configuration planning

2. Phase 2: 2-3 days
   - New package setup
   - Code migration
   - Initial testing

3. Phase 3: 1-2 days
   - Cleanup existing package
   - Documentation updates
   - Testing adjustments

4. Phase 4: 2-3 days
   - Comprehensive testing
   - Documentation creation
   - User guides

5. Phase 5: 1 day
   - Version management
   - Release preparation
   - Announcements

Total Estimated Time: 7-11 days

## Future Considerations

### Shared Features
- Consider creating a shared package for common utilities
- Maintain consistent coding standards across packages
- Share test helpers and fixtures

### Package Integration
- Provide examples of using both packages together
- Create meta-package for full functionality
- Document best practices for combined usage

### Maintenance
- Set up automated dependency updates
- Maintain consistent release cycles
- Plan for long-term support strategy
