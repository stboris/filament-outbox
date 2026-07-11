<?php

namespace Stboris\FilamentOutbox\Filament\Resources\OutboxMessages;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Stboris\FilamentOutbox\Filament\Resources\OutboxMessages\Pages\ListOutboxMessages;
use Stboris\FilamentOutbox\Filament\Resources\OutboxMessages\Schemas\OutboxMessageInfolist;
use Stboris\FilamentOutbox\Filament\Resources\OutboxMessages\Tables\OutboxMessagesTable;
use Stboris\FilamentOutbox\Models\OutboxMessage;
use UnitEnum;

class OutboxMessageResource extends Resource
{
    protected static ?string $model = OutboxMessage::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-inbox-stack';

    protected static string|UnitEnum|null $navigationGroup = 'Outbox';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'message';

    protected static ?string $pluralModelLabel = 'send history';

    protected static ?string $navigationLabel = 'Send history';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return OutboxMessageInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OutboxMessagesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOutboxMessages::route('/'),
        ];
    }
}
