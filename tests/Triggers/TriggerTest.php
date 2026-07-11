<?php

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Stboris\FilamentOutbox\Jobs\SendOutboxNotification;
use Stboris\FilamentOutbox\Models\OutboxEndpoint;
use Stboris\FilamentOutbox\Tests\Fixtures\Order;
use Stboris\FilamentOutbox\Triggers\TriggerManager;

beforeEach(function () {
    Order::migrate();
});

it('sends a notification when a configured model event fires', function () {
    Http::fake(['*' => Http::response(null, 204)]);
    config()->set('filament-outbox.discord.webhook_url', 'https://discord.test/hook');
    config()->set('filament-outbox.triggers', [
        Order::class => [
            'events' => ['created'],
            'channels' => ['discord'],
            'template' => 'Order #{id} for {customer} — total {total} ({event})',
        ],
    ]);

    TriggerManager::register();

    Order::create(['customer' => 'Boris', 'total' => 49.5]);

    Http::assertSent(fn ($request) => $request->url() === 'https://discord.test/hook'
        && $request['content'] === 'Order #1 for Boris — total 49.5 (created)');
});

it('does not fire for events that are not configured', function () {
    Http::fake();
    config()->set('filament-outbox.discord.webhook_url', 'https://discord.test/hook');
    config()->set('filament-outbox.triggers', [
        Order::class => [
            'events' => ['deleted'],
            'channels' => ['discord'],
        ],
    ]);

    TriggerManager::register();

    Order::create(['customer' => 'Boris']);

    Http::assertNothingSent();
});

it('fires on delete with the default template', function () {
    Http::fake(['*' => Http::response(null, 204)]);
    config()->set('filament-outbox.discord.webhook_url', 'https://discord.test/hook');
    config()->set('filament-outbox.triggers', [
        Order::class => [
            'events' => ['deleted'],
            'channels' => ['discord'],
        ],
    ]);

    TriggerManager::register();

    Order::create(['customer' => 'Boris'])->delete();

    Http::assertSent(fn ($request) => $request['content'] === 'Order #1 deleted');
});

it('routes through configured endpoints per channel', function () {
    Http::fake(['*' => Http::response(null, 204)]);

    OutboxEndpoint::create([
        'name' => 'order-alerts',
        'channel' => 'discord',
        'url' => 'https://endpoint.test/hook',
    ]);

    config()->set('filament-outbox.triggers', [
        Order::class => [
            'events' => ['created'],
            'channels' => ['discord'],
            'endpoints' => ['discord' => 'order-alerts'],
        ],
    ]);

    TriggerManager::register();

    Order::create(['customer' => 'Boris']);

    Http::assertSent(fn ($request) => $request->url() === 'https://endpoint.test/hook');
});

it('sends a structured payload to webhook channels', function () {
    Http::fake(['*' => Http::response('ok')]);
    config()->set('filament-outbox.webhook.url', 'https://receiver.test/hooks');
    config()->set('filament-outbox.triggers', [
        Order::class => [
            'events' => ['created'],
            'channels' => ['webhook'],
        ],
    ]);

    TriggerManager::register();

    Order::create(['customer' => 'Boris', 'total' => 10]);

    Http::assertSent(fn ($request) => $request['event'] === 'order.created'
        && $request['model'] === Order::class
        && $request['key'] === 1
        && $request['attributes']['customer'] === 'Boris');
});

it('queues the send when configured, with retry and backoff', function () {
    Bus::fake();
    config()->set('filament-outbox.triggers', [
        Order::class => [
            'events' => ['created'],
            'channels' => ['discord'],
            'queue' => true,
        ],
    ]);

    TriggerManager::register();

    Order::create(['customer' => 'Boris']);

    Bus::assertDispatched(SendOutboxNotification::class, function (SendOutboxNotification $job) {
        return $job->tries === 3
            && $job->backoff() === [10, 60, 300]
            && $job->notification->channels === ['discord'];
    });
});

it('the queued job delivers the notification when handled', function () {
    Http::fake(['*' => Http::response(null, 204)]);
    config()->set('filament-outbox.discord.webhook_url', 'https://discord.test/hook');
    config()->set('filament-outbox.triggers', [
        Order::class => [
            'events' => ['created'],
            'channels' => ['discord'],
            'queue' => true,
        ],
    ]);

    Bus::fake();
    TriggerManager::register();
    Order::create(['customer' => 'Boris']);

    $job = null;
    Bus::assertDispatched(SendOutboxNotification::class, function ($dispatched) use (&$job) {
        $job = $dispatched;

        return true;
    });

    $job->handle();

    Http::assertSent(fn ($request) => $request->url() === 'https://discord.test/hook');
});
