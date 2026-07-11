<?php

namespace Stboris\FilamentOutbox;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Stboris\FilamentOutbox\Filament\Resources\OutboxEndpoints\OutboxEndpointResource;
use Stboris\FilamentOutbox\Filament\Resources\OutboxMessages\OutboxMessageResource;

class FilamentOutboxPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'filament-outbox';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            OutboxEndpointResource::class,
            OutboxMessageResource::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
