<?php

use Stboris\FilamentOutbox\Messages\DiscordEmbed;
use Stboris\FilamentOutbox\Messages\DiscordMessage;

it('builds a full payload', function () {
    $message = DiscordMessage::make('Hello')
        ->username('Notifier')
        ->avatarUrl('https://example.test/avatar.png')
        ->tts()
        ->embed(DiscordEmbed::make()->title('Title'));

    expect($message->toArray())->toBe([
        'content' => 'Hello',
        'username' => 'Notifier',
        'avatar_url' => 'https://example.test/avatar.png',
        'tts' => true,
        'embeds' => [['title' => 'Title']],
    ]);
});

it('omits unset fields and a false tts flag', function () {
    expect(DiscordMessage::make('Hi')->toArray())->toBe(['content' => 'Hi']);
});

it('accepts raw embed arrays and closures', function () {
    $message = DiscordMessage::make()
        ->embed(['title' => 'Raw'])
        ->embed(fn (DiscordEmbed $embed) => $embed->title('Built'));

    expect($message->toArray()['embeds'])->toBe([
        ['title' => 'Raw'],
        ['title' => 'Built'],
    ]);
    expect($message->isEmpty())->toBeFalse();
});

it('knows when it is empty', function () {
    expect(DiscordMessage::make()->isEmpty())->toBeTrue()
        ->and(DiscordMessage::make('x')->isEmpty())->toBeFalse();
});

it('parses embed colors from hex strings and integers', function () {
    expect(DiscordEmbed::make()->color('#1BAF7A')->toArray()['color'])->toBe(0x1BAF7A)
        ->and(DiscordEmbed::make()->color('E34948')->toArray()['color'])->toBe(0xE34948)
        ->and(DiscordEmbed::make()->color(0x2A78D6)->toArray()['color'])->toBe(0x2A78D6);
});

it('builds embed fields from a label => value map', function () {
    $embed = DiscordEmbed::make()->fields([
        'Entry price' => '$100.00',
        'Invested' => '$500.00',
    ]);

    expect($embed->toArray()['fields'])->toBe([
        ['name' => 'Entry price', 'value' => '$100.00', 'inline' => false],
        ['name' => 'Invested', 'value' => '$500.00', 'inline' => false],
    ]);
});

it('formats datetime timestamps as iso 8601', function () {
    $embed = DiscordEmbed::make()->timestamp(new DateTimeImmutable('2026-07-10 12:00:00', new DateTimeZone('UTC')));

    expect($embed->toArray()['timestamp'])->toBe('2026-07-10T12:00:00+00:00');
});

it('builds a footer with an optional icon', function () {
    expect(DiscordEmbed::make()->footer('sent by filament-outbox')->toArray()['footer'])
        ->toBe(['text' => 'sent by filament-outbox']);
});
