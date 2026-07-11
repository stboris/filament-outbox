<?php

namespace Stboris\FilamentOutbox\Support;

use Stboris\FilamentOutbox\Contracts\Endpoint;
use Stboris\FilamentOutbox\Contracts\EndpointResolver;
use Stboris\FilamentOutbox\Models\OutboxEndpoint;

/**
 * Resolves named endpoints from the outbox_endpoints table. Bound to the
 * EndpointResolver contract, which is how the channels reach it.
 */
class EloquentEndpointResolver implements EndpointResolver
{
    public function findByName(string $name): ?Endpoint
    {
        return OutboxEndpoint::findByName($name);
    }
}
