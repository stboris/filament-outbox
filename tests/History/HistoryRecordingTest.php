<?php

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Stboris\FilamentOutbox\Enums\MessageStatus;
use Stboris\FilamentOutbox\Exceptions\CouldNotSendNotification;
use Stboris\FilamentOutbox\Messages\DiscordMessage;
use Stboris\FilamentOutbox\Models\OutboxEndpoint;
use Stboris\FilamentOutbox\Models\OutboxMessage;

function historyNotification(): Notification
{
    return new class extends Notification
    {
        public function via(object $notifiable): array
        {
            return ['discord'];
        }

        public function toDiscord(object $notifiable): DiscordMessage
        {
            return DiscordMessage::make('History test');
        }
    };
}

it('records a successful send', function () {
    Http::fake(['*' => Http::response(null, 204)]);

    (new AnonymousNotifiable)
        ->route('discord', 'https://discord.test/hook')
        ->notify(historyNotification());

    expect(OutboxMessage::count())->toBe(1);

    $message = OutboxMessage::first();

    expect($message->status)->toBe(MessageStatus::Sent)
        ->and($message->status_code)->toBe(204)
        ->and($message->channel)->toBe('discord')
        ->and($message->url)->toBe('https://discord.test/hook')
        ->and($message->payload)->toBe(['content' => 'History test'])
        ->and($message->sent_at)->not->toBeNull()
        ->and($message->notification)->toContain('@anonymous');
});

it('records a failed send with status code and error body', function () {
    Http::fake(['*' => Http::response('Invalid webhook token', 401)]);

    try {
        (new AnonymousNotifiable)
            ->route('discord', 'https://discord.test/hook')
            ->notify(historyNotification());
        $this->fail('Expected CouldNotSendNotification.');
    } catch (CouldNotSendNotification) {
        // expected
    }

    $message = OutboxMessage::first();

    expect($message->status)->toBe(MessageStatus::Failed)
        ->and($message->status_code)->toBe(401)
        ->and($message->error)->toContain('Invalid webhook token')
        ->and($message->sent_at)->toBeNull();
});

it('records connection failures and wraps them in a descriptive exception', function () {
    config()->set('filament-outbox.http.retry.times', 1);
    Http::fake(fn () => throw new ConnectionException('Could not resolve host'));

    try {
        (new AnonymousNotifiable)
            ->route('discord', 'https://unreachable.test/hook')
            ->notify(historyNotification());
        $this->fail('Expected CouldNotSendNotification.');
    } catch (CouldNotSendNotification $e) {
        expect($e->getMessage())->toContain('Could not reach the [discord] endpoint');
    }

    expect(OutboxMessage::first()->status)->toBe(MessageStatus::Failed)
        ->and(OutboxMessage::first()->error)->toContain('Could not resolve host');
});

it('records nothing when history is disabled', function () {
    config()->set('filament-outbox.history.enabled', false);
    Http::fake(['*' => Http::response(null, 204)]);

    (new AnonymousNotifiable)
        ->route('discord', 'https://discord.test/hook')
        ->notify(historyNotification());

    expect(OutboxMessage::count())->toBe(0);
});

it('links the record to the endpoint that was used', function () {
    Http::fake(['*' => Http::response(null, 204)]);

    $endpoint = OutboxEndpoint::create([
        'name' => 'team-alerts',
        'channel' => 'discord',
        'url' => 'https://endpoint.test/hook',
    ]);

    (new AnonymousNotifiable)->notifyNow(new class extends Notification
    {
        public function via(object $notifiable): array
        {
            return ['discord'];
        }

        public function toDiscord(object $notifiable): DiscordMessage
        {
            return DiscordMessage::make('Hello')->endpoint('team-alerts');
        }
    });

    expect(OutboxMessage::first()->outbox_endpoint_id)->toBe($endpoint->id);
});
