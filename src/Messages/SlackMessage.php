<?php

namespace Stboris\FilamentOutbox\Messages;

use Stboris\FilamentOutbox\Messages\Concerns\RoutesToEndpoint;

class SlackMessage
{
    use RoutesToEndpoint;

    protected ?string $text = null;

    /** @var array<int, array> */
    protected array $blocks = [];

    /** @var array<int, array> */
    protected array $attachments = [];

    protected ?string $channel = null;

    protected ?string $username = null;

    protected ?string $iconEmoji = null;

    protected ?string $webhookUrl = null;

    public static function make(?string $text = null): static
    {
        $message = new static;

        if ($text !== null) {
            $message->text($text);
        }

        return $message;
    }

    public function text(string $text): static
    {
        $this->text = $text;

        return $this;
    }

    /**
     * Append a Block Kit block (raw payload array).
     */
    public function block(array $block): static
    {
        $this->blocks[] = $block;

        return $this;
    }

    /**
     * Convenience: a section block with mrkdwn text.
     */
    public function section(string $markdown): static
    {
        return $this->block([
            'type' => 'section',
            'text' => ['type' => 'mrkdwn', 'text' => $markdown],
        ]);
    }

    public function attachment(array $attachment): static
    {
        $this->attachments[] = $attachment;

        return $this;
    }

    /**
     * Only honored by legacy incoming webhooks.
     */
    public function channel(string $channel): static
    {
        $this->channel = $channel;

        return $this;
    }

    /**
     * Only honored by legacy incoming webhooks.
     */
    public function username(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Only honored by legacy incoming webhooks.
     */
    public function iconEmoji(string $iconEmoji): static
    {
        $this->iconEmoji = $iconEmoji;

        return $this;
    }

    /**
     * Send to this webhook URL instead of the notifiable route / config default.
     */
    public function to(string $webhookUrl): static
    {
        $this->webhookUrl = $webhookUrl;

        return $this;
    }

    public function webhookUrl(): ?string
    {
        return $this->webhookUrl;
    }

    public function isEmpty(): bool
    {
        return $this->text === null && $this->blocks === [] && $this->attachments === [];
    }

    public function toArray(): array
    {
        return array_filter([
            'text' => $this->text,
            'blocks' => $this->blocks ?: null,
            'attachments' => $this->attachments ?: null,
            'channel' => $this->channel,
            'username' => $this->username,
            'icon_emoji' => $this->iconEmoji,
        ], fn ($value) => $value !== null);
    }
}
