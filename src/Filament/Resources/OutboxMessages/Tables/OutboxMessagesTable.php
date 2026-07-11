<?php

namespace Stboris\FilamentOutbox\Filament\Resources\OutboxMessages\Tables;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Stboris\FilamentOutbox\Enums\MessageStatus;
use Stboris\FilamentOutbox\Models\OutboxMessage;
use Stboris\FilamentOutbox\Support\Resender;

class OutboxMessagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Attempted')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('channel')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'discord' => 'info',
                        'slack' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('endpoint.name')
                    ->label('Endpoint')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('url')
                    ->limit(35)
                    ->tooltip(fn (OutboxMessage $record): string => $record->url)
                    ->toggleable(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('status_code')
                    ->label('HTTP')
                    ->placeholder('—'),
                TextColumn::make('notification')
                    ->formatStateUsing(fn (string $state): string => class_basename($state))
                    ->tooltip(fn (OutboxMessage $record): ?string => $record->notification)
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('channel')
                    ->options([
                        'discord' => 'Discord',
                        'slack' => 'Slack',
                        'webhook' => 'Webhook',
                    ]),
                SelectFilter::make('status')
                    ->options(
                        collect(MessageStatus::cases())
                            ->mapWithKeys(fn (MessageStatus $status) => [$status->value => $status->getLabel()])
                            ->all(),
                    ),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('retry')
                    ->icon('heroicon-o-arrow-path')
                    ->requiresConfirmation()
                    ->modalHeading('Resend this message?')
                    ->modalDescription('The stored payload will be delivered again to the same destination as a new history entry.')
                    ->visible(fn (OutboxMessage $record): bool => $record->status === MessageStatus::Failed)
                    ->action(fn (OutboxMessage $record) => static::retry($record)),
                DeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('10s');
    }

    protected static function retry(OutboxMessage $message): void
    {
        $result = app(Resender::class)->resend($message);

        if ($result->status === MessageStatus::Sent) {
            FilamentNotification::make()
                ->success()
                ->title('Message resent')
                ->body("Delivered with HTTP {$result->status_code}.")
                ->send();

            return;
        }

        FilamentNotification::make()
            ->danger()
            ->title('Resend failed')
            ->body($result->error ?? 'Unknown error.')
            ->send();
    }
}
