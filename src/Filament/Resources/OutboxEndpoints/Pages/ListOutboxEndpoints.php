<?php

namespace Stboris\FilamentOutbox\Filament\Resources\OutboxEndpoints\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Stboris\FilamentOutbox\Filament\Resources\OutboxEndpoints\OutboxEndpointResource;

class ListOutboxEndpoints extends ListRecords
{
    protected static string $resource = OutboxEndpointResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
