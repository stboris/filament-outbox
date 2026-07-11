<?php

use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Stboris\FilamentOutbox\Exceptions\CouldNotSendNotification;
use Stboris\FilamentOutbox\Messages\DiscordEmbed;
use Stboris\FilamentOutbox\Messages\DiscordMessage;

function discordNotification(mixed $message): Notification
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

it('sends a message to the webhook url routed by the notifiable', function () {
    Http::fake(['discord.test/*' => Http::response(null, 204)]);

    $message = DiscordMessage::make('Trade opened')
        ->username('Notifier')
        ->embed(fn (DiscordEmbed $embed) => $embed
            ->title('LONG opened — BTCUSDT')
            ->color('#1BAF7A')
            ->field('Entry price', '$100.00'));

    (new AnonymousNotifiable)
        ->route('discord', 'https://discord.test/api/webhooks/123/abc')
        ->notify(discordNotification($message));

    Http::assertSent(function ($request) {
        return $request->url() === 'https://discord.test/api/webhooks/123/abc'
            && $request['content'] === 'Trade opened'
            && $request['username'] === 'Notifier'
            && $request['embeds'][0]['title'] === 'LONG opened — BTCUSDT'
            && $request['embeds'][0]['color'] === 0x1BAF7A
            && $request['embeds'][0]['fields'][0] === ['name' => 'Entry price', 'value' => '$100.00', 'inline' => false];
    });
});

it('prefers the webhook url set on the message itself', function () {
    Http::fake(['*' => Http::response(null, 204)]);

    $message = DiscordMessage::make('Hello')->to('https://message.test/hook');

    (new AnonymousNotifiable)
        ->route('discord', 'https://route.test/hook')
        ->notify(discordNotification($message));

    Http::assertSent(fn ($request) => $request->url() === 'https://message.test/hook');
});

it('falls back to the configured default webhook url', function () {
    Http::fake(['*' => Http::response(null, 204)]);
    config()->set('filament-outbox.discord.webhook_url', 'https://config.test/hook');

    (new AnonymousNotifiable)->notify(discordNotification(DiscordMessage::make('Hello')));

    Http::assertSent(fn ($request) => $request->url() === 'https://config.test/hook');
});

it('skips quietly when no webhook url is available anywhere', function () {
    Http::fake();

    (new AnonymousNotifiable)->notify(discordNotification(DiscordMessage::make('Hello')));

    Http::assertNothingSent();
});

it('wraps a plain string return value into a message', function () {
    Http::fake(['*' => Http::response(null, 204)]);

    (new AnonymousNotifiable)
        ->route('discord', 'https://discord.test/hook')
        ->notify(discordNotification('Just a string'));

    Http::assertSent(fn ($request) => $request['content'] === 'Just a string');
});

it('throws when the endpoint responds with an error', function () {
    Http::fake(['*' => Http::response('Invalid webhook token', 401)]);

    (new AnonymousNotifiable)
        ->route('discord', 'https://discord.test/hook')
        ->notify(discordNotification(DiscordMessage::make('Hello')));
})->throws(CouldNotSendNotification::class, 'HTTP 401');

it('throws when toDiscord returns something unusable', function () {
    Http::fake();

    (new AnonymousNotifiable)
        ->route('discord', 'https://discord.test/hook')
        ->notify(discordNotification(12345));
})->throws(CouldNotSendNotification::class, 'toDiscord');

it('throws when the message has no content at all', function () {
    Http::fake();

    (new AnonymousNotifiable)
        ->route('discord', 'https://discord.test/hook')
        ->notify(discordNotification(DiscordMessage::make()));
})->throws(CouldNotSendNotification::class, 'no content');
