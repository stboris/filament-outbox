<?php

namespace Stboris\FilamentOutbox\Exceptions;

use RuntimeException;

class CouldNotSendNotification extends RuntimeException
{
    public static function invalidMessage(string $channel, string $method, string $expected, mixed $actual): self
    {
        $type = get_debug_type($actual);

        return new self("The [{$channel}] channel expects {$method}() to return {$expected}, got [{$type}].");
    }

    public static function missingContent(string $channel): self
    {
        return new self("The [{$channel}] message has no content to send.");
    }

    public static function serviceRespondedWithAnError(string $channel, int $status, string $body): self
    {
        $body = mb_strimwidth($body, 0, 500, '…');

        return new self("The [{$channel}] endpoint responded with HTTP {$status}: {$body}");
    }

    public static function connectionFailed(string $channel, \Throwable $previous): self
    {
        return new self("Could not reach the [{$channel}] endpoint: {$previous->getMessage()}", previous: $previous);
    }

    public static function unknownEndpoint(string $channel, string $name): self
    {
        return new self("No [{$channel}] endpoint named [{$name}] exists. Check the endpoint name and its channel type in the panel.");
    }

    public static function endpointsUnavailable(string $channel, string $name): self
    {
        return new self("The [{$channel}] message references endpoint [{$name}], but named endpoints require the Filament Outbox admin package (no endpoint resolver is installed).");
    }
}
