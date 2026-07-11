<?php

namespace Stboris\FilamentOutbox\Filament\Resources\OutboxEndpoints\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Stboris\FilamentOutbox\Filament\Resources\OutboxEndpoints\OutboxEndpointResource;

class EditOutboxEndpoint extends EditRecord
{
    protected static string $resource = OutboxEndpointResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
