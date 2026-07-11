<?php

namespace Stboris\FilamentOutbox\Contracts;

/**
 * A named destination resolved by an EndpointResolver. Implemented by the
 * admin package's OutboxEndpoint model; the channels only ever talk to this
 * interface so they work without the admin package installed.
 */
interface Endpoint
{
    /**
     * Identifier stored with history records (outbox_endpoint_id).
     */
    public function key(): int|string|null;

    /**
     * The channel this endpoint belongs to ('discord', 'slack', 'webhook').
     */
    public function channel(): string;

    public function url(): string;

    /**
     * Inactive endpoints (disabled, or scoped to another environment) cause
     * the send to be skipped quietly rather than fail.
     */
    public function isActive(): bool;

    /**
     * Channel-specific defaults (e.g. 'username', 'icon_emoji', 'headers')
     * applied when the message leaves them unset. Dot notation supported.
     */
    public function setting(string $key): mixed;

    /**
     * Signing secret for webhook endpoints, null otherwise.
     */
    public function secret(): ?string;
}
