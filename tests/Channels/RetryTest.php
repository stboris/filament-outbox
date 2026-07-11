<?php

use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Stboris\FilamentOutbox\Exceptions\CouldNotSendNotification;
use Stboris\FilamentOutbox\Messages\DiscordMessage;

function retryNotification(): Notification
{
    return new class extends Notification
    {
        public function via(object $notifiable): array
        {
            return ['discord'];
        }

        public function toDiscord(object $notifiable): DiscordMessage
        {
            return DiscordMessage::make('Hello');
        }
    };
}

beforeEach(function () {
    config()->set('filament-outbox.http.retry', ['times' => 3, 'delay' => 1]);
});

it('retries server errors and succeeds on a later attempt', function () {
    Http::fake([
        '*' => Http::sequence()
            ->push('boom', 500)
            ->push(null, 204),
    ]);

    (new AnonymousNotifiable)
        ->route('discord', 'https://discord.test/hook')
        ->notify(retryNotification());

    Http::assertSentCount(2);
});

it('retries rate limits (429)', function () {
    Http::fake([
        '*' => Http::sequence()
            ->push('slow down', 429)
            ->push(null, 204),
    ]);

    (new AnonymousNotifiable)
        ->route('discord', 'https://discord.test/hook')
        ->notify(retryNotification());

    Http::assertSentCount(2);
});

it('does not retry permanent client errors', function () {
    Http::fake(['*' => Http::response('bad request', 400)]);

    try {
        (new AnonymousNotifiable)
            ->route('discord', 'https://discord.test/hook')
            ->notify(retryNotification());
        $this->fail('Expected CouldNotSendNotification.');
    } catch (CouldNotSendNotification) {
        // expected
    }

    Http::assertSentCount(1);
});

it('gives up after the configured number of attempts and reports the failure', function () {
    Http::fake(['*' => Http::response('still broken', 500)]);

    try {
        (new AnonymousNotifiable)
            ->route('discord', 'https://discord.test/hook')
            ->notify(retryNotification());
        $this->fail('Expected CouldNotSendNotification.');
    } catch (CouldNotSendNotification $e) {
        expect($e->getMessage())->toContain('HTTP 500');
    }

    Http::assertSentCount(3);
});

it('sends exactly once when retries are disabled', function () {
    config()->set('filament-outbox.http.retry.times', 1);
    Http::fake(['*' => Http::response('boom', 500)]);

    try {
        (new AnonymousNotifiable)
            ->route('discord', 'https://discord.test/hook')
            ->notify(retryNotification());
        $this->fail('Expected CouldNotSendNotification.');
    } catch (CouldNotSendNotification) {
        // expected
    }

    Http::assertSentCount(1);
});
