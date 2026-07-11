<?php

namespace Stboris\FilamentOutbox\Support;

use Illuminate\Database\Eloquent\Model;

/**
 * Replaces {placeholder} tokens with model attributes (dot notation allowed)
 * or extra values like {event}, {model} and {key}. Unknown placeholders are
 * left untouched so typos are visible in the delivered message.
 */
class TemplateRenderer
{
    public static function render(string $template, Model $model, array $extra = []): string
    {
        return preg_replace_callback('/\{([a-zA-Z0-9_.]+)\}/', function (array $matches) use ($model, $extra) {
            $value = $extra[$matches[1]] ?? data_get($model, $matches[1]);

            return match (true) {
                $value === null => $matches[0],
                is_bool($value) => $value ? 'true' : 'false',
                is_scalar($value) || $value instanceof \Stringable => (string) $value,
                default => json_encode($value),
            };
        }, $template);
    }
}
