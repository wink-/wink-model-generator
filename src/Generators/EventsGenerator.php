<?php

declare(strict_types=1);

namespace Wink\ModelGenerator\Generators;

use Illuminate\Support\Str;
use Wink\ModelGenerator\Config\GeneratorConfig;

class EventsGenerator
{
    private GeneratorConfig $config;

    public function __construct(GeneratorConfig $config)
    {
        $this->config = $config;
    }

    /**
     * Generate event methods for a model
     */
    public function generateEventMethods(string $modelName, bool $hasSoftDeletes = false): array
    {
        $events = $this->getModelEvents($hasSoftDeletes);
        $methods = [];

        foreach ($events as $event) {
            $methods[] = $this->generateEventMethod($event, $modelName);
        }

        return $methods;
    }

    /**
     * Generate boot method for event registration
     */
    public function generateBootMethod(string $modelName, bool $hasSoftDeletes = false): string
    {
        $events = $this->getModelEvents($hasSoftDeletes);
        $registrations = [];

        foreach ($events as $event) {
            $methodName = $this->getEventMethodName($event);
            $registrations[] = "        static::{$event}(function (\$model) {
            \$model->{$methodName}();
        });";
        }

        if (empty($registrations)) {
            return '';
        }

        $registrationString = implode("\n", $registrations);

        return <<<EOT
    /**
     * The "booting" method of the model.
     */
    protected static function boot(): void
    {
        parent::boot();

{$registrationString}
    }
EOT;
    }

    /**
     * Generate event method placeholders for model template
     */
    public function generateEventMethodPlaceholders(string $modelName, bool $hasSoftDeletes = false): string
    {
        if (! $this->config->getModelProperty('generate_event_methods', false)) {
            return '';
        }

        $methods = $this->generateEventMethods($modelName, $hasSoftDeletes);

        if (empty($methods)) {
            return '';
        }

        return "\n    ".implode("\n\n    ", $methods);
    }

    /**
     * Get model events based on configuration
     */
    private function getModelEvents(bool $hasSoftDeletes): array
    {
        $allEvents = $this->config->getModelProperty('model_events', [
            'creating', 'created', 'updating', 'updated', 'deleting', 'deleted', 'saving', 'saved',
        ]);

        $excludeEvents = $this->config->getModelProperty('exclude_model_events', []);

        // Filter out excluded events
        $events = array_diff($allEvents, $excludeEvents);

        // Add soft delete events if model has soft deletes
        if ($hasSoftDeletes) {
            $softDeleteEvents = ['restoring', 'restored'];
            foreach ($softDeleteEvents as $event) {
                if (! in_array($event, $events)) {
                    $events[] = $event;
                }
            }
        }

        // Add optional events
        if ($this->config->getModelProperty('include_retrieved_event', false)) {
            $events[] = 'retrieved';
        }

        if ($this->config->getModelProperty('include_booted_event', false)) {
            $events[] = 'booted';
        }

        return array_unique($events);
    }

    /**
     * Generate a single event method
     */
    private function generateEventMethod(string $event, string $modelName): string
    {
        $methodName = $this->getEventMethodName($event);
        $comment = $this->getEventComment($event);
        $modelVar = Str::camel($modelName);

        $methodStub = $this->config->getModelProperty('event_method_stubs', true);

        if ($methodStub) {
            return <<<EOT
/**
     * {$comment}
     */
    public function {$methodName}(): void
    {
        // TODO: Implement {$event} event logic
    }
EOT;
        } else {
            return <<<EOT
/**
     * {$comment}
     */
    public function {$methodName}(): void
    {
        //
    }
EOT;
        }
    }

    /**
     * Get event method name based on event
     */
    private function getEventMethodName(string $event): string
    {
        return 'handle'.Str::studly($event);
    }

    /**
     * Get event comment based on event type
     */
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

    /**
     * Generate event method imports (if needed)
     */
    public function generateEventImports(): string
    {
        // For now, no additional imports are needed for event methods
        // This could be extended to include event-specific imports
        return '';
    }
}
