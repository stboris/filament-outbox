<?php

use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Stboris\FilamentOutbox\Contracts\EndpointResolver;
use Stboris\FilamentOutbox\Contracts\HistoryRecorder;
use Stboris\FilamentOutbox\Exceptions\CouldNotSendNotification;
use Stboris\FilamentOutbox\Messages\DiscordMessage;
use Stboris\FilamentOutbox\Models\OutboxMessage;

/**
 * The channels must keep working when the admin package's container bindings
 * are absent (the standalone free install): sends go out, history recording
 * is skipped, and named endpoints fail with a descriptive exception.
 */
function standaloneNotification(mixed $message): Notification
{
    return new class($message) extends Notification
    {
        public function __construct(public mixed $message) {}

        public function via(object $notifiable): array
        {
            return ['discord'];
        }

        public function toDiscord(object $notifiable): mixed
        {
            return $this->message;
        }
    };
}

beforeEach(function () {
    app()->offsetUnset(EndpointResolver::class);
    app()->offsetUnset(HistoryRecorder::class);
});

it('sends without the admin bindings and records no history', function () {
    Http::fake(['*' => Http::response(null, 204)]);

    (new AnonymousNotifiable)->notifyNow(
        standaloneNotification(DiscordMessage::make('Hello')->to('https://discord.test/hook')),
    );

    Http::assertSent(fn ($request) => $request->url() === 'https://discord.test/hook');
    expect(OutboxMessage::count())->toBe(0);
});

it('throws a descriptive exception for named endpoints without a resolver', function () {
    Http::fake();

    (new AnonymousNotifiable)->notifyNow(
        standaloneNotification(DiscordMessage::make('Hello')->endpoint('team-alerts')),
    );
})->throws(CouldNotSendNotification::class, 'require the Filament Outbox admin package');
