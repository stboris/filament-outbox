<?php

namespace Stboris\FilamentOutbox\Filament\Resources\OutboxEndpoints\Tables;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Notifications\AnonymousNotifiable;
use Stboris\FilamentOutbox\Models\OutboxEndpoint;
use Stboris\FilamentOutbox\Notifications\OutboxTestNotification;
use Throwable;

class OutboxEndpointsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('channel')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'discord' => 'info',
                        'slack' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('url')
                    ->limit(45)
                    ->tooltip(fn (OutboxEndpoint $record): string => $record->url),
                TextColumn::make('environments')
                    ->badge()
                    ->placeholder('All environments'),
                IconColumn::make('enabled')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('channel')
                    ->options([
                        'discord' => 'Discord',
                        'slack' => 'Slack',
                        'webhook' => 'Webhook',
                    ]),
            ])
            ->recordActions([
                Action::make('testSend')
                    ->label('Send test')
                    ->icon('heroicon-o-paper-airplane')
                    ->action(fn (OutboxEndpoint $record) => static::sendTest($record)),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('name');
    }

    protected static function sendTest(OutboxEndpoint $endpoint): void
    {
        if (! $endpoint->isActive()) {
            FilamentNotification::make()
                ->warning()
                ->title('Endpoint is not active')
                ->body('It is disabled or scoped to a different environment, so sends are skipped.')
                ->send();

            return;
        }

        try {
            (new AnonymousNotifiable)->notifyNow(
                new OutboxTestNotification($endpoint->channel, endpoint: $endpoint->name),
            );

            FilamentNotification::make()
                ->success()
                ->title('Test notification sent')
                ->body("Delivered via {$endpoint->channel} — check the destination.")
                ->send();
        } catch (Throwable $exception) {
            FilamentNotification::make()
                ->danger()
                ->title('Test send failed')
                ->body($exception->getMessage())
                ->send();
        }
    }
}
