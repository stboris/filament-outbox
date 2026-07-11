<?php

namespace Stboris\FilamentOutbox\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stboris\FilamentOutbox\Enums\MessageStatus;

class OutboxMessage extends Model
{
    use MassPrunable;

    protected $guarded = [];

    protected $casts = [
        'headers' => 'array',
        'payload' => 'array',
        'status' => MessageStatus::class,
        'sent_at' => 'datetime',
    ];

    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(OutboxEndpoint::class, 'outbox_endpoint_id');
    }

    public function resentFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'resent_from_id');
    }

    public function markSent(?int $statusCode): void
    {
        $this->forceFill([
            'status' => MessageStatus::Sent,
            'status_code' => $statusCode,
            'error' => null,
            'sent_at' => now(),
        ])->save();
    }

    public function markFailed(?int $statusCode, ?string $error): void
    {
        $this->forceFill([
            'status' => MessageStatus::Failed,
            'status_code' => $statusCode,
            'error' => $error === null ? null : mb_strimwidth($error, 0, 1000, '…'),
        ])->save();
    }

    public function prunable(): Builder
    {
        return static::query()->where(
            'created_at',
            '<',
            now()->subDays((int) config('filament-outbox.history.prune_after_days', 30)),
        );
    }
}
