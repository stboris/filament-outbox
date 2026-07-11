<?php

namespace Stboris\FilamentOutbox\Filament\Resources\OutboxMessages\Pages;

use Filament\Resources\Pages\ListRecords;
use Stboris\FilamentOutbox\Filament\Resources\OutboxMessages\OutboxMessageResource;

class ListOutboxMessages extends ListRecords
{
    protected static string $resource = OutboxMessageResource::class;
}
