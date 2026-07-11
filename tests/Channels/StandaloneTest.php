<?php

use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Stboris\FilamentOutbox\Exceptions\CouldNotSendNotification;
use Stboris\FilamentOutbox\Messages\DiscordMessage;

/**
 * Without the admin package there are no EndpointResolver/HistoryRecorder
 * bindings: sends still go out, history recording is skipped, and named
 * endpoints fail with a descriptive exception.
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

it('sends without the admin bindings', function () {
    Http::fake(['*' => Http::response(null, 204)]);

    (new AnonymousNotifiable)->notifyNow(
        standaloneNotification(DiscordMessage::make('Hello')->to('https://discord.test/hook')),
    );

    Http::assertSent(fn ($request) => $request->url() === 'https://discord.test/hook');
});

it('throws a descriptive exception for named endpoints without a resolver', function () {
    Http::fake();

    (new AnonymousNotifiable)->notifyNow(
        standaloneNotification(DiscordMessage::make('Hello')->endpoint('team-alerts')),
    );
})->throws(CouldNotSendNotification::class, 'require the Filament Outbox admin package');
