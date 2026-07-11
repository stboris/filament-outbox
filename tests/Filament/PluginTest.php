<?php

use Filament\Facades\Filament;
use Stboris\FilamentOutbox\Filament\Resources\OutboxEndpoints\OutboxEndpointResource;
use Stboris\FilamentOutbox\Filament\Resources\OutboxMessages\OutboxMessageResource;

it('registers both resources on the panel', function () {
    $resources = Filament::getPanel('admin')->getResources();

    expect($resources)->toContain(OutboxEndpointResource::class)
        ->and($resources)->toContain(OutboxMessageResource::class);
});

it('exposes the plugin by id', function () {
    expect(Filament::getPanel('admin')->getPlugin('filament-outbox'))
        ->toBeInstanceOf(\Stboris\FilamentOutbox\FilamentOutboxPlugin::class);
});
