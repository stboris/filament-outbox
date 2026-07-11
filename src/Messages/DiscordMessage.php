<?php

namespace Stboris\FilamentOutbox\Messages;

use Closure;
use Stboris\FilamentOutbox\Messages\Concerns\RoutesToEndpoint;

class DiscordMessage
{
    use RoutesToEndpoint;

    protected ?string $content = null;

    protected ?string $username = null;

    protected ?string $avatarUrl = null;

    protected bool $tts = false;

    /** @var array<int, DiscordEmbed|array> */
    protected array $embeds = [];

    protected ?string $webhookUrl = null;

    public static function make(?string $content = null): static
    {
        $message = new static;

        if ($content !== null) {
            $message->content($content);
        }

        return $message;
    }

    public function content(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function username(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    public function avatarUrl(string $avatarUrl): static
    {
        $this->avatarUrl = $avatarUrl;

        return $this;
    }

    public function tts(bool $tts = true): static
    {
        $this->tts = $tts;

        return $this;
    }

    /**
     * Add an embed: a DiscordEmbed, a raw payload array, or a closure that
     * receives a fresh DiscordEmbed to configure.
     */
    public function embed(DiscordEmbed|array|Closure $embed): static
    {
        if ($embed instanceof Closure) {
            $embed($configured = DiscordEmbed::make());
            $embed = $configured;
        }

        $this->embeds[] = $embed;

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
        return $this->content === null && $this->embeds === [];
    }

    public function toArray(): array
    {
        $embeds = array_map(
            fn (DiscordEmbed|array $embed) => $embed instanceof DiscordEmbed ? $embed->toArray() : $embed,
            $this->embeds,
        );

        return array_filter([
            'content' => $this->content,
            'username' => $this->username,
            'avatar_url' => $this->avatarUrl,
            'tts' => $this->tts ?: null,
            'embeds' => $embeds ?: null,
        ], fn ($value) => $value !== null);
    }
}
