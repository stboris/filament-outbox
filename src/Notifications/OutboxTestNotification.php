<?php

namespace Stboris\FilamentOutbox\Notifications;

use Illuminate\Notifications\Notification;
use Stboris\FilamentOutbox\Channels\DiscordChannel;
use Stboris\FilamentOutbox\Channels\SlackChannel;
use Stboris\FilamentOutbox\Channels\WebhookChannel;
use Stboris\FilamentOutbox\Messages\DiscordEmbed;
use Stboris\FilamentOutbox\Messages\DiscordMessage;
use Stboris\FilamentOutbox\Messages\SlackMessage;
use Stboris\FilamentOutbox\Messages\WebhookMessage;

/**
 * The message sent by `php artisan outbox:test` and (later) the panel's
 * test-send button, so users can verify an endpoint before relying on it.
 */
class OutboxTestNotification extends Notification
{
    protected const CHANNELS = [
        'discord' => DiscordChannel::class,
        'slack' => SlackChannel::class,
        'webhook' => WebhookChannel::class,
    ];

    public function __construct(
        protected string $channel,
        protected ?string $text = null,
        protected ?string $endpoint = null,
    ) {}

    public static function supports(string $channel): bool
    {
        return array_key_exists($channel, self::CHANNELS);
    }

    public function via(object $notifiable): array
    {
        return [self::CHANNELS[$this->channel]];
    }

    public function toDiscord(object $notifiable): DiscordMessage
    {
        return $this->route(DiscordMessage::make()
            ->username('Filament Outbox')
            ->embed(fn (DiscordEmbed $embed) => $embed
                ->title('🔔 Test notification')
                ->description($this->text())
                ->color(0x1BAF7A)
                ->timestamp()));
    }

    public function toSlack(object $notifiable): SlackMessage
    {
        return $this->route(SlackMessage::make('🔔 Test notification')
            ->section($this->text()));
    }

    public function toWebhook(object $notifiable): WebhookMessage
    {
        return $this->route(WebhookMessage::make([
            'event' => 'outbox.test',
            'message' => $this->text(),
            'sent_at' => now()->toIso8601String(),
        ]));
    }

    protected function route(DiscordMessage|SlackMessage|WebhookMessage $message): DiscordMessage|SlackMessage|WebhookMessage
    {
        if ($this->endpoint !== null) {
            $message->endpoint($this->endpoint);
        }

        return $message;
    }

    protected function text(): string
    {
        return $this->text ?? 'It works! 🎉 This endpoint is ready to receive notifications.';
    }
}
