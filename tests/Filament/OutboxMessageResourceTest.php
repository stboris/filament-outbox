<?php

use Illuminate\Support\Facades\Http;
use Stboris\FilamentOutbox\Enums\MessageStatus;
use Stboris\FilamentOutbox\Filament\Resources\OutboxMessages\Pages\ListOutboxMessages;
use Stboris\FilamentOutbox\Models\OutboxMessage;

use function Pest\Livewire\livewire;

function failedMessage(): OutboxMessage
{
    return OutboxMessage::create([
        'channel' => 'discord',
        'url' => 'https://discord.test/hook',
        'method' => 'post',
        'payload' => ['content' => 'Hello'],
        'status' => MessageStatus::Failed,
        'status_code' => 500,
        'error' => 'server exploded',
    ]);
}

it('renders the send history list', function () {
    failedMessage();

    livewire(ListOutboxMessages::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords(OutboxMessage::all());
});

it('retries a failed message from the table action', function () {
    Http::fake(['*' => Http::response(null, 204)]);

    $message = failedMessage();

    livewire(ListOutboxMessages::class)
        ->callTableAction('retry', $message)
        ->assertNotified();

    Http::assertSent(fn ($request) => $request->url() === 'https://discord.test/hook');

    expect(OutboxMessage::count())->toBe(2)
        ->and(OutboxMessage::latest('id')->first()->status)->toBe(MessageStatus::Sent)
        ->and(OutboxMessage::latest('id')->first()->resent_from_id)->toBe($message->id);
});

it('hides the retry action for sent messages', function () {
    $sent = OutboxMessage::create([
        'channel' => 'discord',
        'url' => 'https://discord.test/hook',
        'method' => 'post',
        'payload' => ['content' => 'Hello'],
        'status' => MessageStatus::Sent,
        'status_code' => 204,
    ]);

    livewire(ListOutboxMessages::class)
        ->assertTableActionHidden('retry', $sent);
});
