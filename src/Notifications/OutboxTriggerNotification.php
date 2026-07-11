<?php

namespace Stboris\FilamentOutbox\Notifications;

use Illuminate\Notifications\Notification;
use Stboris\FilamentOutbox\Channels\DiscordChannel;
use Stboris\FilamentOutbox\Channels\SlackChannel;
use Stboris\FilamentOutbox\Channels\WebhookChannel;
use Stboris\FilamentOutbox\Messages\DiscordMessage;
use Stboris\FilamentOutbox\Messages\SlackMessage;
use Stboris\FilamentOutbox\Messages\WebhookMessage;

/**
 * Sent by the model-event trigger system. Carries only plain, pre-rendered
 * values so it serializes safely onto queues — even for deleted models.
 */
class OutboxTriggerNotification extends Notification
{
    protected const CHANNELS = [
        'discord' => DiscordChannel::class,
        'slack' => SlackChannel::class,
        'webhook' => WebhookChannel::class,
    ];

    /**
     * @param  array<int, string>  $channels  channel names: discord, slack, webhook
     * @param  array<string, string>  $endpoints  channel => endpoint name
     */
    public function __construct(
        public array $channels,
        public array $endpoints,
        public string $text,
        public array $payload,
    ) {}

    public function via(object $notifiable): array
    {
        return array_values(array_intersect_key(self::CHANNELS, array_flip($this->channels)));
    }

    public function toDiscord(object $notifiable): DiscordMessage
    {
        return $this->route('discord', DiscordMessage::make($this->text));
    }

    public function toSlack(object $notifiable): SlackMessage
    {
        return $this->route('slack', SlackMessage::make($this->text));
    }

    public function toWebhook(object $notifiable): WebhookMessage
    {
        return $this->route('webhook', WebhookMessage::make($this->payload));
    }

    protected function route(string $channel, DiscordMessage|SlackMessage|WebhookMessage $message): DiscordMessage|SlackMessage|WebhookMessage
    {
        if (isset($this->endpoints[$channel])) {
            $message->endpoint($this->endpoints[$channel]);
        }

        return $message;
    }
}
