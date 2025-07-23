<?php

/**
 * Example demonstrating model event generation with wink-model-generator
 *
 * This example shows how to use the event generation feature to automatically
 * generate event methods and boot method for Laravel models.
 */

use Wink\ModelGenerator\Config\GeneratorConfig;
use Wink\ModelGenerator\Generators\ModelGenerator;
use Wink\ModelGenerator\Services\FileService;

// Example 1: Generate model with event methods
echo "Example 1: Model with event methods\n";
echo "=====================================\n";

$config = new GeneratorConfig([
    'model_properties' => [
        'generate_event_methods' => true,
        'generate_boot_method' => true,
        'model_events' => ['creating', 'created', 'updating', 'updated', 'deleting', 'deleted'],
        'event_method_stubs' => true,
    ],
]);

$modelGenerator = new ModelGenerator($config, new FileService);

$columns = [
    (object) ['name' => 'id', 'type' => 'integer', 'primary' => true],
    (object) ['name' => 'name', 'type' => 'varchar', 'length' => 255],
    (object) ['name' => 'email', 'type' => 'varchar', 'length' => 255],
    (object) ['name' => 'created_at', 'type' => 'datetime'],
    (object) ['name' => 'updated_at', 'type' => 'datetime'],
];

$modelContent = $modelGenerator->generate(
    modelName: 'User',
    tableName: 'users',
    connection: 'sqlite',
    columns: $columns,
    foreignKeys: [],
    withRelationships: false,
    withRules: false,
    withEvents: true
);

echo "Generated model structure:\n";
echo "- Event methods: handleCreating(), handleCreated(), etc.\n";
echo "- Boot method with event registration\n";
echo "- Event method stubs with TODO comments\n\n";

// Example 2: Model with soft deletes includes additional events
echo "Example 2: Model with soft deletes\n";
echo "===================================\n";

$columnsWithSoftDeletes = [
    (object) ['name' => 'id', 'type' => 'integer', 'primary' => true],
    (object) ['name' => 'name', 'type' => 'varchar', 'length' => 255],
    (object) ['name' => 'deleted_at', 'type' => 'datetime'],
    (object) ['name' => 'created_at', 'type' => 'datetime'],
    (object) ['name' => 'updated_at', 'type' => 'datetime'],
];

$modelWithSoftDeletes = $modelGenerator->generate(
    modelName: 'Post',
    tableName: 'posts',
    connection: 'sqlite',
    columns: $columnsWithSoftDeletes,
    foreignKeys: [],
    withRelationships: false,
    withRules: false,
    withEvents: true
);

echo "Generated model includes:\n";
echo "- Standard events: creating, created, updating, updated, etc.\n";
echo "- Soft delete events: restoring, restored\n";
echo "- SoftDeletes trait and import\n\n";

// Example 3: Configuration options
echo "Example 3: Configuration options\n";
echo "=================================\n";

$customConfig = new GeneratorConfig([
    'model_properties' => [
        'generate_event_methods' => true,
        'generate_boot_method' => true,
        'model_events' => ['creating', 'created', 'saving', 'saved'], // Limited events
        'exclude_model_events' => ['deleting'], // Exclude specific events
        'include_retrieved_event' => true, // Include retrieved event
        'event_method_stubs' => false, // No TODO comments
    ],
]);

echo "Available configuration options:\n";
echo "- generate_event_methods: Enable/disable event method generation\n";
echo "- generate_boot_method: Enable/disable boot method generation\n";
echo "- model_events: Array of events to generate\n";
echo "- exclude_model_events: Array of events to exclude\n";
echo "- include_retrieved_event: Include retrieved event\n";
echo "- include_booted_event: Include booted event\n";
echo "- event_method_stubs: Generate TODO comments in methods\n\n";

// Example 4: Command line usage
echo "Example 4: Command line usage\n";
echo "==============================\n";

echo "Generate models with events:\n";
echo "php artisan wink:generate-models --with-events\n\n";

echo "Generate models with events and boot method:\n";
echo "php artisan wink:generate-models --with-events --with-boot-method\n\n";

echo "Sample generated model excerpt:\n";
echo "```php\n";
echo "class User extends Model\n";
echo "{\n";
echo "    // ... other model properties ...\n";
echo "\n";
echo "    /**\n";
echo "     * The \"booting\" method of the model.\n";
echo "     */\n";
echo "    protected static function boot(): void\n";
echo "    {\n";
echo "        parent::boot();\n";
echo "\n";
echo "        static::creating(function (\$model) {\n";
echo "            \$model->handleCreating();\n";
echo "        });\n";
echo "        // ... other event registrations ...\n";
echo "    }\n";
echo "\n";
echo "    /**\n";
echo "     * Handle the model \"creating\" event.\n";
echo "     */\n";
echo "    public function handleCreating(): void\n";
echo "    {\n";
echo "        // TODO: Implement creating event logic\n";
echo "    }\n";
echo "\n";
echo "    // ... other event methods ...\n";
echo "}\n";
echo "```\n";
