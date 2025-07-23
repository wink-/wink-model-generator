<?php

declare(strict_types=1);

namespace Wink\ModelGenerator\Generators;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Wink\ModelGenerator\Config\GeneratorConfig;
use Wink\ModelGenerator\Exceptions\InvalidInputException;

class ObserverGenerator
{
    private GeneratorConfig $config;

    public function __construct(GeneratorConfig $config)
    {
        $this->config = $config;
    }

    /**
     * Generate an observer class for the specified model
     */
    public function generate(
        string $modelName,
        string $modelNamespace,
        string $connection,
        array $columns = [],
        bool $hasSoftDeletes = false
    ): string {
        if (empty($modelName)) {
            throw new InvalidInputException('Model name is required for observer generation');
        }

        $template = File::get(__DIR__.'/../../stubs/observer.stub');

        $observerDefinition = $this->buildObserverDefinition(
            $modelName,
            $modelNamespace,
            $connection,
            $columns,
            $hasSoftDeletes
        );

        return str_replace(
            array_keys($observerDefinition),
            array_values($observerDefinition),
            $template
        );
    }

    private function buildObserverDefinition(
        string $modelName,
        string $modelNamespace,
        string $connection,
        array $columns,
        bool $hasSoftDeletes
    ): array {
        $observerName = $modelName.'Observer';
        $observerNamespace = $this->buildObserverNamespace($connection);

        $events = $this->getObserverEvents($hasSoftDeletes);
        $methods = $this->generateObserverMethods($events, $modelName);

        return [
            '{{ observer_namespace }}' => $observerNamespace,
            '{{ observer_class }}' => $observerName,
            '{{ model_namespace }}' => $modelNamespace,
            '{{ model_class }}' => $modelName,
            '{{ observer_methods }}' => implode("\n\n", $methods),
            '{{ model_import }}' => "use {$modelNamespace}\\{$modelName};",
            '{{ package_name }}' => 'App\\Observers\\Generated',
        ];
    }

    private function buildObserverNamespace(string $connection): string
    {
        $baseNamespace = $this->config->getObserverNamespace();

        if ($this->config->getObserverProperty('observer_connection_based', true)) {
            return $baseNamespace.'\\'.Str::studly($connection);
        }

        return $baseNamespace;
    }

    private function getObserverEvents(bool $hasSoftDeletes): array
    {
        $allEvents = $this->config->getObserverProperty('observer_events', [
            'creating', 'created', 'updating', 'updated', 'deleting', 'deleted', 'saving', 'saved',
        ]);
        $excludeEvents = $this->config->getObserverProperty('exclude_events', []);

        // Filter out excluded events
        $events = array_diff($allEvents, $excludeEvents);

        // Add soft delete events if model has soft deletes
        if ($hasSoftDeletes && ! in_array('restoring', $events)) {
            $events[] = 'restoring';
            $events[] = 'restored';
        }

        // Add optional events
        if ($this->config->getObserverProperty('include_retrieved', false)) {
            $events[] = 'retrieved';
        }

        if ($this->config->getObserverProperty('include_booted', false)) {
            $events[] = 'booted';
        }

        return array_unique($events);
    }

    private function generateObserverMethods(array $events, string $modelName): array
    {
        $methods = [];
        $generateStubs = $this->config->getObserverProperty('observer_method_stubs', true);

        foreach ($events as $event) {
            $methodName = $event;
            $modelVar = Str::camel($modelName);

            if ($generateStubs) {
                $methods[] = $this->generateMethodStub($methodName, $modelName, $modelVar);
            } else {
                $methods[] = $this->generateEmptyMethod($methodName, $modelName, $modelVar);
            }
        }

        return $methods;
    }

    private function generateMethodStub(string $event, string $modelName, string $modelVar): string
    {
        $comment = $this->getEventComment($event);
        $businessLogic = $this->generateBusinessLogic($event, $modelName, $modelVar);

        return <<<EOT
    /**
     * {$comment}
     */
    public function {$event}({$modelName} \${$modelVar}): void
    {
        {$businessLogic}
    }
EOT;
    }

    private function generateEmptyMethod(string $event, string $modelName, string $modelVar): string
    {
        $comment = $this->getEventComment($event);

        return <<<EOT
    /**
     * {$comment}
     */
    public function {$event}({$modelName} \${$modelVar}): void
    {
        //
    }
EOT;
    }

    private function getEventComment(string $event): string
    {
        return match ($event) {
            'creating' => 'Handle the model "creating" event.',
            'created' => 'Handle the model "created" event.',
            'updating' => 'Handle the model "updating" event.',
            'updated' => 'Handle the model "updated" event.',
            'saving' => 'Handle the model "saving" event.',
            'saved' => 'Handle the model "saved" event.',
            'deleting' => 'Handle the model "deleting" event.',
            'deleted' => 'Handle the model "deleted" event.',
            'restoring' => 'Handle the model "restoring" event.',
            'restored' => 'Handle the model "restored" event.',
            'force_deleting' => 'Handle the model "force deleting" event.',
            'retrieved' => 'Handle the model "retrieved" event.',
            'booted' => 'Handle the model "booted" event.',
            default => "Handle the model \"{$event}\" event.",
        };
    }

    private function generateBusinessLogic(string $event, string $modelName, string $modelVar): string
    {
        $logic = [];

        // Add business validation for certain events
        if (in_array($event, ['creating', 'updating'])) {
            $logic[] = '        // Validate business constraints';
            $logic[] = "        if (!\$this->validateBusinessConstraints(\${$modelVar}, '{$event}')) {";
            $logic[] = '            return; // Validation failed';
            $logic[] = '        }';
            $logic[] = '';
        }

        // Add audit logging
        if (in_array($event, ['created', 'updated', 'deleted'])) {
            $changes = $event === 'updated' ? "\${$modelVar}->getChanges()" : '[]';
            $logic[] = '        // Business audit logging';
            $logic[] = "        \$this->auditBusinessAction(\${$modelVar}, '{$event}', {$changes});";
            $logic[] = '';
        }

        // Add cache invalidation
        if (in_array($event, ['created', 'updated', 'deleted'])) {
            $logic[] = '        // Cache invalidation';
            $logic[] = "        \$this->invalidateBusinessCache(\${$modelVar});";
            $logic[] = '';
        }

        // Add search index updates
        if (in_array($event, ['created', 'updated', 'deleted'])) {
            $logic[] = '        // Search index updates';
            $logic[] = "        \$this->updateSearchIndex(\${$modelVar}, '{$event}');";
            $logic[] = '';
        }

        // Add workflow triggers
        if (in_array($event, ['created', 'updated', 'deleted'])) {
            $changes = $event === 'updated' ? "\${$modelVar}->getChanges()" : '[]';
            $logic[] = '        // Business workflow triggers';
            $logic[] = "        \$this->triggerBusinessWorkflows(\${$modelVar}, '{$event}', {$changes});";
            $logic[] = '';
        }

        // Add notifications
        if (in_array($event, ['created', 'updated', 'deleted'])) {
            $logic[] = '        // Business notifications';
            $logic[] = "        \$context = \$this->getBusinessContext(\${$modelVar}, '{$event}');";
            $logic[] = "        \$this->handleBusinessNotifications(\${$modelVar}, '{$event}', \$context);";
            $logic[] = '';
        }

        // Add special handling for specific events
        if ($event === 'updated') {
            $logic[] = '        // Handle business-critical changes';
            $logic[] = "        \$changes = \${$modelVar}->getChanges();";
            $logic[] = '        if ($this->hasBusinessCriticalChanges($changes)) {';
            $logic[] = "            Log::info('Business-critical changes detected', [";
            $logic[] = "                'model' => class_basename(\${$modelVar}),";
            $logic[] = "                'id' => \${$modelVar}->id,";
            $logic[] = "                'changes' => \$changes,";
            $logic[] = '            ]);';
            $logic[] = '        }';
            $logic[] = '';
        }

        // Add default TODO comment if no logic generated
        if (empty($logic)) {
            $logic[] = "        // TODO: Implement {$event} business logic";
            $logic[] = '        // - Add audit logging';
            $logic[] = '        // - Handle notifications';
            $logic[] = '        // - Update cache';
            $logic[] = '        // - Trigger workflows';
        }

        return implode("\n", $logic);
    }
}
