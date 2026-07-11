<?php

namespace Stboris\FilamentOutbox\Contracts;

/**
 * Persists send attempts. Bound to the container by the admin package; when
 * no binding exists the channels skip recording entirely.
 */
interface HistoryRecorder
{
    /**
     * Returns null when recording is disabled or unavailable (e.g. the
     * history table has not been migrated).
     */
    public function record(array $attributes): ?HistoryRecord;
}
