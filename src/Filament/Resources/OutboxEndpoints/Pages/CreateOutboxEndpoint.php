<?php

namespace Stboris\FilamentOutbox\Filament\Resources\OutboxEndpoints\Pages;

use Filament\Resources\Pages\CreateRecord;
use Stboris\FilamentOutbox\Filament\Resources\OutboxEndpoints\OutboxEndpointResource;

class CreateOutboxEndpoint extends CreateRecord
{
    protected static string $resource = OutboxEndpointResource::class;
}
