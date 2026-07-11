<?php

namespace Stboris\FilamentOutbox\Contracts;

/**
 * A single recorded send attempt whose outcome is updated in place.
 * Implemented by the admin package's OutboxMessage model.
 */
interface HistoryRecord
{
    public function markSent(?int $statusCode): void;

    public function markFailed(?int $statusCode, ?string $error): void;
}
