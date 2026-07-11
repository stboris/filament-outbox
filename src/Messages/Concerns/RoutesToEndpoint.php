<?php

namespace Stboris\FilamentOutbox\Messages\Concerns;

trait RoutesToEndpoint
{
    protected ?string $endpointName = null;

    /**
     * Send through a named endpoint managed in the Filament panel. The
     * endpoint provides the URL plus channel defaults (and the signing
     * secret for webhooks).
     */
    public function endpoint(string $name): static
    {
        $this->endpointName = $name;

        return $this;
    }

    public function endpointName(): ?string
    {
        return $this->endpointName;
    }
}
