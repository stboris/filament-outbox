<?php

namespace Stboris\FilamentOutbox\Channels;

use Illuminate\Notifications\Notification;
use Stboris\FilamentOutbox\Channels\Concerns\SendsWebhookRequests;
use Stboris\FilamentOutbox\Exceptions\CouldNotSendNotification;
use Stboris\FilamentOutbox\Messages\SlackMessage;

class SlackChannel
{
    use SendsWebhookRequests;

    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toSlack')) {
            throw CouldNotSendNotification::invalidMessage('slack', 'toSlack', SlackMessage::class, null);
        }

        $message = $notification->toSlack($notifiable);

        if (is_string($message)) {
            $message = SlackMessage::make($message);
        }

        if (! $message instanceof SlackMessage) {
            throw CouldNotSendNotification::invalidMessage('slack', 'toSlack', SlackMessage::class, $message);
        }

        if ($message->isEmpty()) {
            throw CouldNotSendNotification::missingContent('slack');
        }

        [$url, $endpoint] = $this->resolveDestination(
            $message->webhookUrl(),
            $message->endpointName(),
            $notifiable,
            $notification,
            'slack',
            'filament-outbox.slack.webhook_url',
        );

        if (! $url) {
            return;
        }

        $payload = $message->toArray();

        // Endpoint-level defaults fill in whatever the message left unset.
        if ($endpoint) {
            $payload += array_filter([
                'username' => $endpoint->setting('username'),
                'icon_emoji' => $endpoint->setting('icon_emoji'),
                'channel' => $endpoint->setting('channel'),
            ]);
        }

        $this->deliver('slack', $url, $payload, $notification, $endpoint);
    }
}
