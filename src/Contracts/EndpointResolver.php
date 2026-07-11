<?php

namespace Stboris\FilamentOutbox\Contracts;

/**
 * Resolves ->endpoint('name') references on messages. Bound to the container
 * by the admin package; when no binding exists, named endpoints are
 * unavailable and the channels throw a descriptive exception.
 */
interface EndpointResolver
{
    public function findByName(string $name): ?Endpoint;
}
