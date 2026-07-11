<?php

use Illuminate\Support\Facades\Http;
use Stboris\FilamentOutbox\Enums\MessageStatus;
use Stboris\FilamentOutbox\Models\OutboxEndpoint;
use Stboris\FilamentOutbox\Models\OutboxMessage;
use Stboris\FilamentOutbox\Support\Resender;

it('replays a failed message as a new linked history row', function () {
    Http::fake(['*' => Http::response(null, 204)]);

    $original = OutboxMessage::create([
        'channel' => 'discord',
        'url' => 'https://discord.test/hook',
        'method' => 'post',
        'payload' => ['content' => 'Hello again'],
        'status' => MessageStatus::Failed,
        'status_code' => 500,
    ]);

    $result = app(Resender::class)->resend($original);

    expect($result->id)->not->toBe($original->id)
        ->and($result->resent_from_id)->toBe($original->id)
        ->and($result->status)->toBe(MessageStatus::Sent)
        ->and($result->status_code)->toBe(204);

    Http::assertSent(fn ($request) => $request->url() === 'https://discord.test/hook'
        && $request['content'] === 'Hello again');
});

it('re-signs webhook payloads with the endpoint secret', function () {
    Http::fake(['*' => Http::response('ok')]);

    $endpoint = OutboxEndpoint::create([
        'name' => 'billing-hook',
        'channel' => 'webhook',
        'url' => 'https://receiver.test/hooks',
        'secret' => 'rotated-secret',
    ]);

    $original = OutboxMessage::create([
        'outbox_endpoint_id' => $endpoint->id,
        'channel' => 'webhook',
        'url' => 'https://receiver.test/hooks',
        'method' => 'post',
        'headers' => ['X-Outbox-Signature' => 'stale-signature'],
        'payload' => ['event' => 'ping'],
        'status' => MessageStatus::Failed,
    ]);

    app(Resender::class)->resend($original);

    Http::assertSent(function ($request) {
        $expected = hash_hmac('sha256', $request->body(), 'rotated-secret');

        return $request->hasHeader('X-Outbox-Signature', $expected);
    });
});

it('marks the new row failed without throwing when the resend fails too', function () {
    Http::fake(['*' => Http::response('still broken', 502)]);
    config()->set('filament-outbox.http.retry.times', 1);

    $original = OutboxMessage::create([
        'channel' => 'slack',
        'url' => 'https://hooks.slack.test/hook',
        'method' => 'post',
        'payload' => ['text' => 'Hi'],
        'status' => MessageStatus::Failed,
    ]);

    $result = app(Resender::class)->resend($original);

    expect($result->status)->toBe(MessageStatus::Failed)
        ->and($result->status_code)->toBe(502)
        ->and($result->error)->toContain('still broken');
});
