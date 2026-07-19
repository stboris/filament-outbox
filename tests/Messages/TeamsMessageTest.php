<?php

use Stboris\FilamentOutbox\Messages\TeamsMessage;

it('builds an adaptive card payload', function () {
    $message = TeamsMessage::make('Deploy finished')
        ->title('Deployment')
        ->color('good')
        ->fact('Version', 'v2.14.0')
        ->fact('Environment', 'production');

    expect($message->toArray())->toBe([
        'type' => 'message',
        'attachments' => [
            [
                'contentType' => 'application/vnd.microsoft.card.adaptive',
                'content' => [
                    '$schema' => 'http://adaptivecards.io/schemas/adaptive-card.json',
                    'type' => 'AdaptiveCard',
                    'version' => '1.4',
                    'msteams' => ['width' => 'Full'],
                    'body' => [
                        [
                            'type' => 'TextBlock',
                            'size' => 'Large',
                            'weight' => 'Bolder',
                            'text' => 'Deployment',
                            'wrap' => true,
                            'color' => 'good',
                        ],
                        [
                            'type' => 'TextBlock',
                            'text' => 'Deploy finished',
                            'wrap' => true,
                        ],
                        [
                            'type' => 'FactSet',
                            'facts' => [
                                ['title' => 'Version', 'value' => 'v2.14.0'],
                                ['title' => 'Environment', 'value' => 'production'],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ]);
});

it('omits unset elements from the card body', function () {
    $content = TeamsMessage::make('Just text')->toArray()['attachments'][0]['content'];

    expect($content['body'])->toBe([
        ['type' => 'TextBlock', 'text' => 'Just text', 'wrap' => true],
    ]);
});

it('accepts facts as an associative array and raw body blocks', function () {
    $content = TeamsMessage::make()
        ->facts(['Order' => '#1001', 'Total' => '$42.00'])
        ->block(['type' => 'TextBlock', 'text' => 'extra', 'isSubtle' => true])
        ->toArray()['attachments'][0]['content'];

    expect($content['body'])->toBe([
        [
            'type' => 'FactSet',
            'facts' => [
                ['title' => 'Order', 'value' => '#1001'],
                ['title' => 'Total', 'value' => '$42.00'],
            ],
        ],
        ['type' => 'TextBlock', 'text' => 'extra', 'isSubtle' => true],
    ]);
});

it('lets a raw card replace the generated content but keeps the envelope', function () {
    $card = [
        'type' => 'AdaptiveCard',
        'version' => '1.5',
        'body' => [['type' => 'TextBlock', 'text' => 'custom']],
    ];

    $payload = TeamsMessage::make()->card($card)->toArray();

    expect($payload['type'])->toBe('message')
        ->and($payload['attachments'][0]['contentType'])->toBe('application/vnd.microsoft.card.adaptive')
        ->and($payload['attachments'][0]['content'])->toBe($card);
});

it('knows when it is empty', function () {
    expect(TeamsMessage::make()->isEmpty())->toBeTrue()
        ->and(TeamsMessage::make('x')->isEmpty())->toBeFalse()
        ->and(TeamsMessage::make()->title('x')->isEmpty())->toBeFalse()
        ->and(TeamsMessage::make()->fact('a', 'b')->isEmpty())->toBeFalse()
        ->and(TeamsMessage::make()->block(['type' => 'TextBlock'])->isEmpty())->toBeFalse()
        ->and(TeamsMessage::make()->card(['type' => 'AdaptiveCard'])->isEmpty())->toBeFalse();
});
