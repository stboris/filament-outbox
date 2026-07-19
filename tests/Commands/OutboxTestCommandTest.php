<?php

use Illuminate\Support\Facades\Http;

it('sends a discord test notification to the given url', function () {
    Http::fake(['*' => Http::response(null, 204)]);

    $this->artisan('outbox:test', ['channel' => 'discord', '--to' => 'https://discord.test/hook'])
        ->expectsOutputToContain('Test notification sent via [discord]')
        ->assertSuccessful();

    Http::assertSent(fn ($request) => $request->url() === 'https://discord.test/hook'
        && $request['embeds'][0]['title'] === '🔔 Test notification');
});

it('sends a webhook test notification with a custom message', function () {
    Http::fake(['*' => Http::response('ok')]);

    $this->artisan('outbox:test', [
        'channel' => 'webhook',
        '--to' => 'https://receiver.test/hooks',
        '--message' => 'Custom check',
    ])->assertSuccessful();

    Http::assertSent(fn ($request) => $request['event'] === 'outbox.test'
        && $request['message'] === 'Custom check');
});

it('sends a teams test notification as an adaptive card', function () {
    Http::fake(['*' => Http::response(null, 202)]);

    $this->artisan('outbox:test', ['channel' => 'teams', '--to' => 'https://teams.test/workflows/abc'])
        ->expectsOutputToContain('Test notification sent via [teams]')
        ->assertSuccessful();

    Http::assertSent(fn ($request) => $request->url() === 'https://teams.test/workflows/abc'
        && $request['attachments'][0]['content']['body'][0]['text'] === '🔔 Test notification');
});

it('uses the configured default url when --to is omitted', function () {
    Http::fake(['*' => Http::response('ok')]);
    config()->set('filament-outbox.slack.webhook_url', 'https://config.slack.test/hook');

    $this->artisan('outbox:test', ['channel' => 'slack'])->assertSuccessful();

    Http::assertSent(fn ($request) => $request->url() === 'https://config.slack.test/hook');
});

it('fails with a helpful error when no url is available', function () {
    Http::fake();

    $this->artisan('outbox:test', ['channel' => 'discord'])
        ->expectsOutputToContain('No destination URL')
        ->assertFailed();

    Http::assertNothingSent();
});

it('fails when the endpoint rejects the notification', function () {
    Http::fake(['*' => Http::response('Invalid webhook token', 401)]);

    $this->artisan('outbox:test', ['channel' => 'discord', '--to' => 'https://discord.test/hook'])
        ->expectsOutputToContain('Sending failed')
        ->assertFailed();
});

it('rejects unknown channels', function () {
    Http::fake();

    $this->artisan('outbox:test', ['channel' => 'pigeon'])
        ->expectsOutputToContain('Unknown channel [pigeon]')
        ->assertFailed();

    Http::assertNothingSent();
});
