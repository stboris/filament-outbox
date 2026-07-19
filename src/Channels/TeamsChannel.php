<?php

namespace Stboris\FilamentOutbox\Channels;

use Illuminate\Notifications\Notification;
use Stboris\FilamentOutbox\Channels\Concerns\SendsWebhookRequests;
use Stboris\FilamentOutbox\Exceptions\CouldNotSendNotification;
use Stboris\FilamentOutbox\Messages\TeamsMessage;

class TeamsChannel
{
    use SendsWebhookRequests;

    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toTeams')) {
            throw CouldNotSendNotification::invalidMessage('teams', 'toTeams', TeamsMessage::class, null);
        }

        $message = $notification->toTeams($notifiable);

        if (is_string($message)) {
            $message = TeamsMessage::make($message);
        }

        if (! $message instanceof TeamsMessage) {
            throw CouldNotSendNotification::invalidMessage('teams', 'toTeams', TeamsMessage::class, $message);
        }

        if ($message->isEmpty()) {
            throw CouldNotSendNotification::missingContent('teams');
        }

        [$url, $endpoint] = $this->resolveDestination(
            $message->webhookUrl(),
            $message->endpointName(),
            $notifiable,
            $notification,
            'teams',
            'filament-outbox.teams.webhook_url',
        );

        if (! $url) {
            return;
        }

        // Workflows posts as the flow itself — there are no sender overrides
        // like Slack's username/icon, so endpoint settings add nothing here.
        $this->deliver('teams', $url, $message->toArray(), $notification, $endpoint);
    }
}
