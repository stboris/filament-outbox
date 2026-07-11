<?php

namespace Stboris\FilamentOutbox\Filament\Resources\OutboxEndpoints;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Stboris\FilamentOutbox\Filament\Resources\OutboxEndpoints\Pages\CreateOutboxEndpoint;
use Stboris\FilamentOutbox\Filament\Resources\OutboxEndpoints\Pages\EditOutboxEndpoint;
use Stboris\FilamentOutbox\Filament\Resources\OutboxEndpoints\Pages\ListOutboxEndpoints;
use Stboris\FilamentOutbox\Filament\Resources\OutboxEndpoints\Schemas\OutboxEndpointForm;
use Stboris\FilamentOutbox\Filament\Resources\OutboxEndpoints\Tables\OutboxEndpointsTable;
use Stboris\FilamentOutbox\Models\OutboxEndpoint;
use UnitEnum;

class OutboxEndpointResource extends Resource
{
    protected static ?string $model = OutboxEndpoint::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-signal';

    protected static string|UnitEnum|null $navigationGroup = 'Outbox';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'endpoint';

    protected static ?string $navigationLabel = 'Endpoints';

    public static function form(Schema $schema): Schema
    {
        return OutboxEndpointForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OutboxEndpointsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOutboxEndpoints::route('/'),
            'create' => CreateOutboxEndpoint::route('/create'),
            'edit' => EditOutboxEndpoint::route('/{record}/edit'),
        ];
    }
}
