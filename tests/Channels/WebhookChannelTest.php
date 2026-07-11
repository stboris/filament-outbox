<?php

use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Stboris\FilamentOutbox\Exceptions\CouldNotSendNotification;
use Stboris\FilamentOutbox\Messages\WebhookMessage;

function webhookNotification(mixed $message): Notification
{
    return new class($message) extends Notification
    {
        public function __construct(public mixed $message) {}

        public function via(object $notifiable): array
        {
            return ['webhook'];
        }

        public function toWebhook(object $notifiable): mixed
        {
            return $this->message;
        }
    };
}

it('posts the payload as json to the routed url', function () {
    Http::fake(['receiver.test/*' => Http::response('ok')]);

    $message = WebhookMessage::make(['event' => 'order.created', 'id' => 42])
        ->header('X-Custom', 'yes');

    (new AnonymousNotifiable)
        ->route('webhook', 'https://receiver.test/hooks')
        ->notify(webhookNotification($message));

    Http::assertSent(function ($request) {
        return $request->url() === 'https://receiver.test/hooks'
            && $request->method() === 'POST'
            && $request->hasHeader('Content-Type', 'application/json')
            && $request->hasHeader('X-Custom', 'yes')
            && $request['event'] === 'order.created'
            && $request['id'] === 42;
    });
});

it('accepts a plain array return value', function () {
    Http::fake(['*' => Http::response('ok')]);

    (new AnonymousNotifiable)
        ->route('webhook', 'https://receiver.test/hooks')
        ->notify(webhookNotification(['event' => 'ping']));

    Http::assertSent(fn ($request) => $request['event'] === 'ping');
});

it('signs the raw body with the configured secret', function () {
    Http::fake(['*' => Http::response('ok')]);
    config()->set('filament-outbox.webhook.secret', 'shhh');

    (new AnonymousNotifiable)
        ->route('webhook', 'https://receiver.test/hooks')
        ->notify(webhookNotification(['event' => 'ping']));

    Http::assertSent(function ($request) {
        $expected = hash_hmac('sha256', $request->body(), 'shhh');

        return $request->hasHeader('X-Outbox-Signature', $expected);
    });
});

it('lets the message secret override the configured one', function () {
    Http::fake(['*' => Http::response('ok')]);
    config()->set('filament-outbox.webhook.secret', 'config-secret');

    $message = WebhookMessage::make(['event' => 'ping'])->secret('message-secret');

    (new AnonymousNotifiable)
        ->route('webhook', 'https://receiver.test/hooks')
        ->notify(webhookNotification($message));

    Http::assertSent(function ($request) {
        $expected = hash_hmac('sha256', $request->body(), 'message-secret');

        return $request->hasHeader('X-Outbox-Signature', $expected);
    });
});

it('sends no signature header when no secret is set', function () {
    Http::fake(['*' => Http::response('ok')]);

    (new AnonymousNotifiable)
        ->route('webhook', 'https://receiver.test/hooks')
        ->notify(webhookNotification(['event' => 'ping']));

    Http::assertSent(fn ($request) => ! $request->hasHeader('X-Outbox-Signature'));
});

it('supports other http methods', function () {
    Http::fake(['*' => Http::response('ok')]);

    $message = WebhookMessage::make(['event' => 'ping'])->method('put');

    (new AnonymousNotifiable)
        ->route('webhook', 'https://receiver.test/hooks')
        ->notify(webhookNotification($message));

    Http::assertSent(fn ($request) => $request->method() === 'PUT');
});

it('falls back to the configured default url', function () {
    Http::fake(['*' => Http::response('ok')]);
    config()->set('filament-outbox.webhook.url', 'https://config.test/hooks');

    (new AnonymousNotifiable)->notify(webhookNotification(['event' => 'ping']));

    Http::assertSent(fn ($request) => $request->url() === 'https://config.test/hooks');
});

it('skips quietly when no url is available anywhere', function () {
    Http::fake();

    (new AnonymousNotifiable)->notify(webhookNotification(['event' => 'ping']));

    Http::assertNothingSent();
});

it('throws when the endpoint responds with an error', function () {
    Http::fake(['*' => Http::response('server error', 500)]);

    (new AnonymousNotifiable)
        ->route('webhook', 'https://receiver.test/hooks')
        ->notify(webhookNotification(['event' => 'ping']));
})->throws(CouldNotSendNotification::class, 'HTTP 500');

it('throws when the payload is empty', function () {
    Http::fake();

    (new AnonymousNotifiable)
        ->route('webhook', 'https://receiver.test/hooks')
        ->notify(webhookNotification(WebhookMessage::make()));
})->throws(CouldNotSendNotification::class, 'no content');
