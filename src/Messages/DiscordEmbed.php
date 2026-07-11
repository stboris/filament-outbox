<?php

namespace Stboris\FilamentOutbox\Messages;

use DateTimeInterface;

class DiscordEmbed
{
    protected ?string $title = null;

    protected ?string $description = null;

    protected ?string $url = null;

    protected ?int $color = null;

    protected ?string $timestamp = null;

    protected ?array $footer = null;

    /** @var array<int, array{name: string, value: string, inline: bool}> */
    protected array $fields = [];

    public static function make(): static
    {
        return new static;
    }

    public function title(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function description(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function url(string $url): static
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Accepts an integer (0x1BAF7A) or a hex string ('#1BAF7A' / '1BAF7A').
     */
    public function color(int|string $color): static
    {
        $this->color = is_int($color) ? $color : (int) hexdec(ltrim($color, '#'));

        return $this;
    }

    public function timestamp(DateTimeInterface|string|null $timestamp = null): static
    {
        $timestamp ??= now();

        $this->timestamp = $timestamp instanceof DateTimeInterface
            ? $timestamp->format(DateTimeInterface::ATOM)
            : $timestamp;

        return $this;
    }

    public function footer(string $text, ?string $iconUrl = null): static
    {
        $this->footer = array_filter([
            'text' => $text,
            'icon_url' => $iconUrl,
        ]);

        return $this;
    }

    public function field(string $name, string $value, bool $inline = false): static
    {
        $this->fields[] = ['name' => $name, 'value' => $value, 'inline' => $inline];

        return $this;
    }

    /**
     * Add many non-inline fields at once from a [label => value] map.
     */
    public function fields(array $fields): static
    {
        foreach ($fields as $name => $value) {
            $this->field((string) $name, (string) $value);
        }

        return $this;
    }

    public function toArray(): array
    {
        return array_filter([
            'title' => $this->title,
            'description' => $this->description,
            'url' => $this->url,
            'color' => $this->color,
            'timestamp' => $this->timestamp,
            'footer' => $this->footer,
            'fields' => $this->fields ?: null,
        ], fn ($value) => $value !== null);
    }
}
