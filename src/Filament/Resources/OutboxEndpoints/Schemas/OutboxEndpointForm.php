<?php

namespace Stboris\FilamentOutbox\Filament\Resources\OutboxEndpoints\Schemas;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class OutboxEndpointForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Endpoint')
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true)
                        ->helperText("Reference it from code: ->endpoint('this-name')"),
                    Select::make('channel')
                        ->options([
                            'discord' => 'Discord',
                            'slack' => 'Slack',
                            'webhook' => 'Webhook',
                        ])
                        ->required()
                        ->live()
                        ->native(false),
                    TextInput::make('url')
                        ->label('Webhook URL')
                        ->required()
                        ->url()
                        ->maxLength(2048)
                        ->columnSpanFull(),
                    TagsInput::make('environments')
                        ->placeholder('All environments')
                        ->suggestions(['production', 'staging', 'local'])
                        ->helperText('Leave empty to send from every environment.'),
                    Toggle::make('enabled')
                        ->default(true)
                        ->inline(false),
                ])
                ->columns(2),

            Section::make('Channel defaults')
                ->description('Applied when the notification message does not set them itself.')
                ->schema([
                    TextInput::make('settings.username')
                        ->label('Bot username')
                        ->visible(fn (Get $get): bool => in_array($get('channel'), ['discord', 'slack'], true)),
                    TextInput::make('settings.avatar_url')
                        ->label('Avatar URL')
                        ->url()
                        ->visible(fn (Get $get): bool => $get('channel') === 'discord'),
                    TextInput::make('settings.icon_emoji')
                        ->label('Icon emoji')
                        ->placeholder(':bell:')
                        ->visible(fn (Get $get): bool => $get('channel') === 'slack'),
                    TextInput::make('settings.channel')
                        ->label('Slack channel override')
                        ->placeholder('#alerts')
                        ->visible(fn (Get $get): bool => $get('channel') === 'slack'),
                    TextInput::make('secret')
                        ->label('Signing secret')
                        ->password()
                        ->revealable()
                        ->helperText('Payloads are signed with HMAC-SHA256 in the X-Outbox-Signature header.')
                        ->visible(fn (Get $get): bool => $get('channel') === 'webhook'),
                    KeyValue::make('settings.headers')
                        ->label('Extra headers')
                        ->keyLabel('Header')
                        ->valueLabel('Value')
                        ->visible(fn (Get $get): bool => $get('channel') === 'webhook')
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->visible(fn (Get $get): bool => filled($get('channel'))),
        ]);
    }
}
