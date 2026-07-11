<?php

namespace Stboris\FilamentOutbox\Filament\Resources\OutboxMessages\Schemas;

use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontFamily;
use Stboris\FilamentOutbox\Models\OutboxMessage;

class OutboxMessageInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Delivery')
                ->schema([
                    TextEntry::make('channel')
                        ->badge(),
                    TextEntry::make('status')
                        ->badge(),
                    TextEntry::make('status_code')
                        ->label('HTTP status')
                        ->placeholder('—'),
                    TextEntry::make('endpoint.name')
                        ->label('Endpoint')
                        ->placeholder('—'),
                    TextEntry::make('url')
                        ->columnSpan(2),
                    TextEntry::make('method')
                        ->formatStateUsing(fn (string $state): string => strtoupper($state)),
                    TextEntry::make('notification')
                        ->placeholder('—'),
                    TextEntry::make('created_at')
                        ->label('Attempted')
                        ->dateTime(),
                    TextEntry::make('sent_at')
                        ->dateTime()
                        ->placeholder('—'),
                    TextEntry::make('resentFrom.id')
                        ->label('Resend of')
                        ->formatStateUsing(fn ($state): string => "#{$state}")
                        ->placeholder('—'),
                    TextEntry::make('error')
                        ->color('danger')
                        ->placeholder('—')
                        ->columnSpanFull(),
                ])
                ->columns(3),

            Section::make('Payload')
                ->schema([
                    TextEntry::make('payload')
                        ->hiddenLabel()
                        ->state(fn (OutboxMessage $record): string => json_encode(
                            $record->payload,
                            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
                        ) ?: '{}')
                        ->fontFamily(FontFamily::Mono)
                        ->copyable(),
                ])
                ->collapsible(),

            Section::make('Headers')
                ->schema([
                    KeyValueEntry::make('headers')
                        ->hiddenLabel(),
                ])
                ->collapsible()
                ->visible(fn (OutboxMessage $record): bool => filled($record->headers)),
        ]);
    }
}
