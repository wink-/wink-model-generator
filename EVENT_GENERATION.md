# Model Event Generation

The wink-model-generator package now supports automatic generation of Laravel model event methods. This feature allows you to generate event handler methods directly in your model classes, along with boot method registration.

## Features

- Generate event methods for all standard Laravel model events
- Automatic boot method generation for event registration
- Support for soft delete events (restoring, restored)
- Configurable event selection and exclusion
- Method stub generation with TODO comments
- Command-line options for easy usage

## Configuration

### Model Properties

Add the following configuration options to your `model_properties` array:

```php
'model_properties' => [
    'generate_event_methods' => true,         // Enable event method generation
    'generate_boot_method' => true,           // Enable boot method generation
    'model_events' => [                       // Events to generate methods for
        'creating', 'created', 'updating', 'updated', 
        'deleting', 'deleted', 'saving', 'saved'
    ],
    'exclude_model_events' => [],             // Events to exclude
    'include_retrieved_event' => false,       // Include retrieved event
    'include_booted_event' => false,          // Include booted event
    'event_method_stubs' => true,             // Generate TODO comments
]
```

### Command Line Usage

Use the following command-line options:

```bash
# Generate models with event methods
php artisan wink:generate-models --with-events

# Generate models with event methods and boot method
php artisan wink:generate-models --with-events --with-boot-method

# Combine with other options
php artisan wink:generate-models --with-events --with-relationships --with-rules
```

## Generated Code Structure

### Event Methods

The generator creates individual methods for each configured event:

```php
/**
 * Handle the model "creating" event.
 */
public function handleCreating(): void
{
    // TODO: Implement creating event logic
}

/**
 * Handle the model "created" event.
 */
public function handleCreated(): void
{
    // TODO: Implement created event logic
}
```

### Boot Method

The boot method registers each event with its corresponding handler:

```php
/**
 * The "booting" method of the model.
 */
protected static function boot(): void
{
    parent::boot();

    static::creating(function ($model) {
        $model->handleCreating();
    });
    static::created(function ($model) {
        $model->handleCreated();
    });
    // ... other event registrations
}
```

## Supported Events

### Standard Events

- `creating`: Fired before a model is created
- `created`: Fired after a model is created
- `updating`: Fired before a model is updated
- `updated`: Fired after a model is updated
- `saving`: Fired before a model is saved (created or updated)
- `saved`: Fired after a model is saved (created or updated)
- `deleting`: Fired before a model is deleted
- `deleted`: Fired after a model is deleted

### Soft Delete Events

When a model has a `deleted_at` column, additional events are generated:

- `restoring`: Fired before a soft-deleted model is restored
- `restored`: Fired after a soft-deleted model is restored

### Optional Events

- `retrieved`: Fired when a model is retrieved from the database
- `booted`: Fired when a model is booted

## Advanced Configuration

### Custom Event Selection

```php
'model_events' => ['creating', 'created', 'saving', 'saved'],
```

### Exclude Specific Events

```php
'model_events' => ['creating', 'created', 'updating', 'updated'],
'exclude_model_events' => ['updating'],
```

### Include Optional Events

```php
'include_retrieved_event' => true,
'include_booted_event' => true,
```

### Method Stub Configuration

```php
'event_method_stubs' => false,  // Generate empty methods without TODO comments
```

## Integration with Existing Features

Event generation works seamlessly with other generator features:

- **Relationships**: Generate events alongside relationship methods
- **Validation Rules**: Combine event methods with validation rules
- **Scopes**: Include event methods with query scopes
- **Soft Deletes**: Automatic detection and event generation for soft deletes

## Example Usage

```php
use Wink\ModelGenerator\Config\GeneratorConfig;
use Wink\ModelGenerator\Generators\ModelGenerator;

$config = new GeneratorConfig([
    'model_properties' => [
        'generate_event_methods' => true,
        'generate_boot_method' => true,
        'model_events' => ['creating', 'created', 'updating', 'updated'],
        'event_method_stubs' => true,
    ]
]);

$modelGenerator = new ModelGenerator($config, new FileService());

$model = $modelGenerator->generate(
    modelName: 'User',
    tableName: 'users',
    connection: 'sqlite',
    columns: $columns,
    withEvents: true
);
```

## Implementation Details

### EventsGenerator Class

The `EventsGenerator` class handles all event-related code generation:

- `generateEventMethods()`: Creates individual event handler methods
- `generateBootMethod()`: Creates the boot method with event registration
- `getModelEvents()`: Determines which events to generate based on configuration

### Integration Points

- **ModelGenerator**: Integrates event generation into the main model generation flow
- **GeneratorConfig**: Provides configuration options for event generation
- **Commands**: Adds command-line options for event generation

## Benefits

1. **Consistency**: All models have the same event method structure
2. **Discoverability**: Event methods are clearly visible in the model
3. **Type Safety**: Proper method signatures and return types
4. **Documentation**: Clear comments explaining each event's purpose
5. **Customization**: Easy to modify generated methods for specific needs

## Best Practices

1. **Selective Generation**: Only generate events you actually need
2. **Method Implementation**: Replace TODO comments with actual business logic
3. **Testing**: Write tests for your event method implementations
4. **Documentation**: Update method comments to reflect actual implementation
5. **Performance**: Consider performance implications of event handlers