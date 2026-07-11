<?php

use Illuminate\Support\Facades\Http;
use Stboris\FilamentOutbox\Filament\Resources\OutboxEndpoints\Pages\CreateOutboxEndpoint;
use Stboris\FilamentOutbox\Filament\Resources\OutboxEndpoints\Pages\ListOutboxEndpoints;
use Stboris\FilamentOutbox\Models\OutboxEndpoint;
use Stboris\FilamentOutbox\Models\OutboxMessage;

use function Pest\Livewire\livewire;

it('renders the endpoint list', function () {
    OutboxEndpoint::create([
        'name' => 'team-alerts',
        'channel' => 'discord',
        'url' => 'https://discord.test/hook',
    ]);

    livewire(ListOutboxEndpoints::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords(OutboxEndpoint::all());
});

it('creates an endpoint from the form', function () {
    livewire(CreateOutboxEndpoint::class)
        ->fillForm([
            'name' => 'billing-hook',
            'channel' => 'webhook',
            'url' => 'https://receiver.test/hooks',
            'secret' => 'shhh',
            'enabled' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $endpoint = OutboxEndpoint::first();

    expect($endpoint->name)->toBe('billing-hook')
        ->and($endpoint->channel)->toBe('webhook')
        ->and($endpoint->secret)->toBe('shhh')
        ->and($endpoint->enabled)->toBeTrue();
});

it('rejects duplicate endpoint names', function () {
    OutboxEndpoint::create([
        'name' => 'team-alerts',
        'channel' => 'discord',
        'url' => 'https://discord.test/hook',
    ]);

    livewire(CreateOutboxEndpoint::class)
        ->fillForm([
            'name' => 'team-alerts',
            'channel' => 'discord',
            'url' => 'https://discord.test/other',
        ])
        ->call('create')
        ->assertHasFormErrors(['name']);
});

it('sends a test notification from the table action', function () {
    Http::fake(['*' => Http::response(null, 204)]);

    $endpoint = OutboxEndpoint::create([
        'name' => 'team-alerts',
        'channel' => 'discord',
        'url' => 'https://discord.test/hook',
    ]);

    livewire(ListOutboxEndpoints::class)
        ->callTableAction('testSend', $endpoint)
        ->assertNotified();

    Http::assertSent(fn ($request) => $request->url() === 'https://discord.test/hook'
        && $request['embeds'][0]['title'] === '🔔 Test notification');

    expect(OutboxMessage::count())->toBe(1);
});

it('warns instead of sending when the endpoint is disabled', function () {
    Http::fake();

    $endpoint = OutboxEndpoint::create([
        'name' => 'team-alerts',
        'channel' => 'discord',
        'url' => 'https://discord.test/hook',
        'enabled' => false,
    ]);

    livewire(ListOutboxEndpoints::class)
        ->callTableAction('testSend', $endpoint)
        ->assertNotified();

    Http::assertNothingSent();
});
