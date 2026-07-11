<?php

use Stboris\FilamentOutbox\Messages\SlackMessage;

it('builds a full payload', function () {
    $message = SlackMessage::make('Fallback text')
        ->section('*Bold section*')
        ->attachment(['color' => '#1BAF7A', 'text' => 'attached'])
        ->channel('#alerts')
        ->username('Notifier')
        ->iconEmoji(':bell:');

    expect($message->toArray())->toBe([
        'text' => 'Fallback text',
        'blocks' => [
            ['type' => 'section', 'text' => ['type' => 'mrkdwn', 'text' => '*Bold section*']],
        ],
        'attachments' => [
            ['color' => '#1BAF7A', 'text' => 'attached'],
        ],
        'channel' => '#alerts',
        'username' => 'Notifier',
        'icon_emoji' => ':bell:',
    ]);
});

it('omits unset fields', function () {
    expect(SlackMessage::make('Hi')->toArray())->toBe(['text' => 'Hi']);
});

it('knows when it is empty', function () {
    expect(SlackMessage::make()->isEmpty())->toBeTrue()
        ->and(SlackMessage::make()->section('x')->isEmpty())->toBeFalse()
        ->and(SlackMessage::make('x')->isEmpty())->toBeFalse();
});
