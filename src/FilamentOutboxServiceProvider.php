<?php

namespace Stboris\FilamentOutbox;

use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Notification;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Stboris\FilamentOutbox\Channels\DiscordChannel;
use Stboris\FilamentOutbox\Channels\SlackChannel;
use Stboris\FilamentOutbox\Channels\WebhookChannel;
use Stboris\FilamentOutbox\Commands\OutboxTestCommand;

class FilamentOutboxServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('filament-outbox')
            ->hasConfigFile()
            ->hasCommand(OutboxTestCommand::class);
    }

    public function packageRegistered(): void
    {
        Notification::resolved(function (ChannelManager $service) {
            $service->extend('discord', fn ($app) => $app->make(DiscordChannel::class));
            $service->extend('webhook', fn ($app) => $app->make(WebhookChannel::class));

            // Only claim the 'slack' alias when the official
            // laravel/slack-notification-channel package is absent — never
            // clobber an existing Slack setup. SlackChannel::class in via()
            // always works regardless.
            if (! class_exists(\Illuminate\Notifications\Slack\SlackChannel::class)
                && ! class_exists(\Illuminate\Notifications\Channels\SlackWebhookChannel::class)) {
                $service->extend('slack', fn ($app) => $app->make(SlackChannel::class));
            }
        });
    }
}
