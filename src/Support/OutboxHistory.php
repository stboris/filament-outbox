<?php

namespace Stboris\FilamentOutbox\Support;

use Illuminate\Support\Facades\Schema;
use Stboris\FilamentOutbox\Enums\MessageStatus;
use Stboris\FilamentOutbox\Models\OutboxMessage;

/**
 * Persists send attempts to the outbox_messages table. Recording degrades to
 * a no-op when history is disabled or the table has not been migrated, so
 * the channels work without the admin layer installed.
 */
class OutboxHistory
{
    protected static ?bool $tableExists = null;

    public static function record(array $attributes): ?OutboxMessage
    {
        if (! static::enabled()) {
            return null;
        }

        return OutboxMessage::create($attributes + ['status' => MessageStatus::Pending]);
    }

    public static function enabled(): bool
    {
        if (! config('filament-outbox.history.enabled', true)) {
            return false;
        }

        return static::$tableExists ??= Schema::hasTable((new OutboxMessage)->getTable());
    }

    public static function flushCache(): void
    {
        static::$tableExists = null;
    }
}
