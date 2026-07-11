<?php

use Stboris\FilamentOutbox\Messages\WebhookMessage;

it('carries data, headers, secret, url and method', function () {
    $message = WebhookMessage::make(['a' => 1])
        ->headers(['X-One' => '1'])
        ->header('X-Two', '2')
        ->secret('shhh')
        ->to('https://receiver.test/hooks')
        ->method('PATCH');

    expect($message->toArray())->toBe(['a' => 1])
        ->and($message->getHeaders())->toBe(['X-One' => '1', 'X-Two' => '2'])
        ->and($message->getSecret())->toBe('shhh')
        ->and($message->url())->toBe('https://receiver.test/hooks')
        ->and($message->getMethod())->toBe('patch');
});

it('defaults to post with no headers or secret', function () {
    $message = WebhookMessage::make();

    expect($message->getMethod())->toBe('post')
        ->and($message->getHeaders())->toBe([])
        ->and($message->getSecret())->toBeNull()
        ->and($message->isEmpty())->toBeTrue();
});
