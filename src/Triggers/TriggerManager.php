<?php

namespace Stboris\FilamentOutbox\Triggers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Str;
use Stboris\FilamentOutbox\Jobs\SendOutboxNotification;
use Stboris\FilamentOutbox\Notifications\OutboxTriggerNotification;
use Stboris\FilamentOutbox\Support\TemplateRenderer;

/**
 * Wires config-defined model events to outgoing notifications. Registered
 * once at boot; each configured model gets closure listeners on its
 * created/updated/deleted/restored events.
 */
class TriggerManager
{
    public const EVENTS = ['created', 'updated', 'deleted', 'restored'];

    public static function register(): void
    {
        foreach (config('filament-outbox.triggers', []) as $model => $config) {
            if (! is_string($model) || ! class_exists($model)) {
                continue;
            }

            $events = array_intersect((array) ($config['events'] ?? []), self::EVENTS);

            foreach ($events as $event) {
                $model::{$event}(fn (Model $record) => static::handle($record, $event, $config));
            }
        }
    }

    public static function handle(Model $record, string $event, array $config): void
    {
        $channels = (array) ($config['channels'] ?? []);

        if ($channels === []) {
            return;
        }

        $text = TemplateRenderer::render(
            $config['template'] ?? '{model} #{key} {event}',
            $record,
            [
                'event' => $event,
                'model' => class_basename($record),
                'key' => (string) $record->getKey(),
            ],
        );

        $notification = new OutboxTriggerNotification(
            channels: $channels,
            endpoints: static::endpoints($config, $channels),
            text: $text,
            payload: [
                'event' => Str::snake(class_basename($record)).'.'.$event,
                'model' => $record::class,
                'key' => $record->getKey(),
                'message' => $text,
                'attributes' => $record->attributesToArray(),
                'occurred_at' => now()->toIso8601String(),
            ],
        );

        if ($config['queue'] ?? false) {
            SendOutboxNotification::dispatch($notification);
        } else {
            (new AnonymousNotifiable)->notifyNow($notification);
        }
    }

    /**
     * 'endpoints' maps channel => endpoint name; a plain 'endpoint' string
     * is accepted as shorthand when a single channel is configured.
     *
     * @return array<string, string>
     */
    protected static function endpoints(array $config, array $channels): array
    {
        $endpoints = (array) ($config['endpoints'] ?? []);

        if (isset($config['endpoint']) && count($channels) === 1) {
            $endpoints[$channels[0]] ??= $config['endpoint'];
        }

        return $endpoints;
    }
}
