<?php

use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Stboris\FilamentOutbox\Exceptions\CouldNotSendNotification;
use Stboris\FilamentOutbox\Messages\SlackMessage;

function slackNotification(mixed $message): Notification
{
    return new class($message) extends Notification
    {
        public function __construct(public mixed $message) {}

        public function via(object $notifiable): array
        {
            return ['slack'];
        }

        public function toSlack(object $notifiable): mixed
        {
            return $this->message;
        }
    };
}

it('sends text and blocks to the routed webhook url', function () {
    Http::fake(['hooks.slack.test/*' => Http::response('ok')]);

    $message = SlackMessage::make('Trade closed — +$42.00')
        ->section('*LONG closed — BTCUSDT*')
        ->username('Notifier')
        ->iconEmoji(':bell:');

    (new AnonymousNotifiable)
        ->route('slack', 'https://hooks.slack.test/services/T00/B00/xyz')
        ->notify(slackNotification($message));

    Http::assertSent(function ($request) {
        return $request->url() === 'https://hooks.slack.test/services/T00/B00/xyz'
            && $request['text'] === 'Trade closed — +$42.00'
            && $request['blocks'][0]['type'] === 'section'
            && $request['blocks'][0]['text']['text'] === '*LONG closed — BTCUSDT*'
            && $request['username'] === 'Notifier'
            && $request['icon_emoji'] === ':bell:';
    });
});

it('falls back to the configured default webhook url', function () {
    Http::fake(['*' => Http::response('ok')]);
    config()->set('filament-outbox.slack.webhook_url', 'https://config.slack.test/hook');

    (new AnonymousNotifiable)->notify(slackNotification(SlackMessage::make('Hello')));

    Http::assertSent(fn ($request) => $request->url() === 'https://config.slack.test/hook');
});

it('skips quietly when no webhook url is available anywhere', function () {
    Http::fake();

    (new AnonymousNotifiable)->notify(slackNotification(SlackMessage::make('Hello')));

    Http::assertNothingSent();
});

it('wraps a plain string return value into a message', function () {
    Http::fake(['*' => Http::response('ok')]);

    (new AnonymousNotifiable)
        ->route('slack', 'https://hooks.slack.test/hook')
        ->notify(slackNotification('Just a string'));

    Http::assertSent(fn ($request) => $request['text'] === 'Just a string');
});

it('throws when the endpoint responds with an error', function () {
    Http::fake(['*' => Http::response('invalid_payload', 400)]);

    (new AnonymousNotifiable)
        ->route('slack', 'https://hooks.slack.test/hook')
        ->notify(slackNotification(SlackMessage::make('Hello')));
})->throws(CouldNotSendNotification::class, 'HTTP 400');

it('throws when the message has no content at all', function () {
    Http::fake();

    (new AnonymousNotifiable)
        ->route('slack', 'https://hooks.slack.test/hook')
        ->notify(slackNotification(SlackMessage::make()));
})->throws(CouldNotSendNotification::class, 'no content');
