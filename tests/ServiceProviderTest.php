<?php

use Illuminate\Notifications\ChannelManager;
use Stboris\FilamentOutbox\Channels\DiscordChannel;
use Stboris\FilamentOutbox\Channels\SlackChannel;
use Stboris\FilamentOutbox\Channels\WebhookChannel;

it('registers the discord and webhook channel aliases', function () {
    $manager = app(ChannelManager::class);

    expect($manager->driver('discord'))->toBeInstanceOf(DiscordChannel::class)
        ->and($manager->driver('webhook'))->toBeInstanceOf(WebhookChannel::class);
});

it('registers the slack alias when the official slack channel is absent', function () {
    if (class_exists(\Illuminate\Notifications\Slack\SlackChannel::class)
        || class_exists(\Illuminate\Notifications\Channels\SlackWebhookChannel::class)) {
        $this->markTestSkipped('laravel/slack-notification-channel is installed.');
    }

    expect(app(ChannelManager::class)->driver('slack'))->toBeInstanceOf(SlackChannel::class);
});

it('publishes the package config', function () {
    expect(config('filament-outbox.http.timeout'))->toBe(5)
        ->and(config('filament-outbox.webhook.signature_header'))->toBe('X-Outbox-Signature');
});
