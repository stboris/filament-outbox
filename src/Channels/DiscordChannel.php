<?php

namespace Stboris\FilamentOutbox\Channels;

use Illuminate\Notifications\Notification;
use Stboris\FilamentOutbox\Channels\Concerns\SendsWebhookRequests;
use Stboris\FilamentOutbox\Exceptions\CouldNotSendNotification;
use Stboris\FilamentOutbox\Messages\DiscordMessage;

class DiscordChannel
{
    use SendsWebhookRequests;

    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toDiscord')) {
            throw CouldNotSendNotification::invalidMessage('discord', 'toDiscord', DiscordMessage::class, null);
        }

        $message = $notification->toDiscord($notifiable);

        if (is_string($message)) {
            $message = DiscordMessage::make($message);
        }

        if (! $message instanceof DiscordMessage) {
            throw CouldNotSendNotification::invalidMessage('discord', 'toDiscord', DiscordMessage::class, $message);
        }

        if ($message->isEmpty()) {
            throw CouldNotSendNotification::missingContent('discord');
        }

        [$url, $endpoint] = $this->resolveDestination(
            $message->webhookUrl(),
            $message->endpointName(),
            $notifiable,
            $notification,
            'discord',
            'filament-outbox.discord.webhook_url',
        );

        if (! $url) {
            return;
        }

        $payload = $message->toArray();

        // Endpoint-level defaults fill in whatever the message left unset.
        if ($endpoint) {
            $payload += array_filter([
                'username' => $endpoint->setting('username'),
                'avatar_url' => $endpoint->setting('avatar_url'),
            ]);
        }

        $this->deliver('discord', $url, $payload, $notification, $endpoint);
    }
}
