<?php

use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Stboris\FilamentOutbox\Exceptions\CouldNotSendNotification;
use Stboris\FilamentOutbox\Messages\DiscordMessage;
use Stboris\FilamentOutbox\Messages\WebhookMessage;
use Stboris\FilamentOutbox\Models\OutboxEndpoint;

function endpointNotification(string $channel, mixed $message): Notification
{
    return new class($channel, $message) extends Notification
    {
        public function __construct(public string $channel, public mixed $message) {}

        public function via(object $notifiable): array
        {
            return [$this->channel];
        }

        public function toDiscord(object $notifiable): mixed
        {
            return $this->message;
        }

        public function toWebhook(object $notifiable): mixed
        {
            return $this->message;
        }
    };
}

it('sends through a named endpoint', function () {
    Http::fake(['*' => Http::response(null, 204)]);

    OutboxEndpoint::create([
        'name' => 'team-alerts',
        'channel' => 'discord',
        'url' => 'https://endpoint.test/hook',
    ]);

    (new AnonymousNotifiable)->notifyNow(
        endpointNotification('discord', DiscordMessage::make('Hello')->endpoint('team-alerts')),
    );

    Http::assertSent(fn ($request) => $request->url() === 'https://endpoint.test/hook');
});

it('applies endpoint channel defaults the message left unset', function () {
    Http::fake(['*' => Http::response(null, 204)]);

    OutboxEndpoint::create([
        'name' => 'team-alerts',
        'channel' => 'discord',
        'url' => 'https://endpoint.test/hook',
        'settings' => ['username' => 'Endpoint Bot', 'avatar_url' => 'https://endpoint.test/a.png'],
    ]);

    (new AnonymousNotifiable)->notifyNow(
        endpointNotification('discord', DiscordMessage::make('Hello')->username('Message Bot')->endpoint('team-alerts')),
    );

    Http::assertSent(fn ($request) => $request['username'] === 'Message Bot'
        && $request['avatar_url'] === 'https://endpoint.test/a.png');
});

it('skips quietly when the endpoint is disabled', function () {
    Http::fake();

    OutboxEndpoint::create([
        'name' => 'team-alerts',
        'channel' => 'discord',
        'url' => 'https://endpoint.test/hook',
        'enabled' => false,
    ]);

    (new AnonymousNotifiable)->notifyNow(
        endpointNotification('discord', DiscordMessage::make('Hello')->endpoint('team-alerts')),
    );

    Http::assertNothingSent();
});

it('skips quietly when the endpoint targets another environment', function () {
    Http::fake();

    OutboxEndpoint::create([
        'name' => 'team-alerts',
        'channel' => 'discord',
        'url' => 'https://endpoint.test/hook',
        'environments' => ['production'],
    ]);

    (new AnonymousNotifiable)->notifyNow(
        endpointNotification('discord', DiscordMessage::make('Hello')->endpoint('team-alerts')),
    );

    Http::assertNothingSent();
});

it('throws for an unknown endpoint name', function () {
    Http::fake();

    (new AnonymousNotifiable)->notifyNow(
        endpointNotification('discord', DiscordMessage::make('Hello')->endpoint('nope')),
    );
})->throws(CouldNotSendNotification::class, 'nope');

it('throws when the endpoint belongs to a different channel', function () {
    Http::fake();

    OutboxEndpoint::create([
        'name' => 'slack-endpoint',
        'channel' => 'slack',
        'url' => 'https://endpoint.test/hook',
    ]);

    (new AnonymousNotifiable)->notifyNow(
        endpointNotification('discord', DiscordMessage::make('Hello')->endpoint('slack-endpoint')),
    );
})->throws(CouldNotSendNotification::class, 'slack-endpoint');

it('signs webhook payloads with the endpoint secret and merges endpoint headers', function () {
    Http::fake(['*' => Http::response('ok')]);

    OutboxEndpoint::create([
        'name' => 'billing-hook',
        'channel' => 'webhook',
        'url' => 'https://endpoint.test/hooks',
        'secret' => 'endpoint-secret',
        'settings' => ['headers' => ['X-From-Endpoint' => 'yes', 'X-Both' => 'endpoint']],
    ]);

    (new AnonymousNotifiable)->notifyNow(
        endpointNotification('webhook', WebhookMessage::make(['event' => 'ping'])
            ->header('X-Both', 'message')
            ->endpoint('billing-hook')),
    );

    Http::assertSent(function ($request) {
        $expected = hash_hmac('sha256', $request->body(), 'endpoint-secret');

        return $request->url() === 'https://endpoint.test/hooks'
            && $request->hasHeader('X-Outbox-Signature', $expected)
            && $request->hasHeader('X-From-Endpoint', 'yes')
            && $request->hasHeader('X-Both', 'message');
    });
});
