<?php

namespace Stboris\FilamentOutbox\Messages;

use Stboris\FilamentOutbox\Messages\Concerns\RoutesToEndpoint;

/**
 * A Microsoft Teams message for Power Automate Workflows webhooks — the
 * mechanism replacing the retired Office 365 Connector webhooks. The payload
 * is an Adaptive Card wrapped in a message attachment, which both Workflows
 * and legacy incoming webhooks accept.
 */
class TeamsMessage
{
    use RoutesToEndpoint;

    protected ?string $title = null;

    protected ?string $text = null;

    /**
     * Adaptive Card TextBlock color applied to the title:
     * default, dark, light, accent, good, warning or attention.
     */
    protected ?string $color = null;

    /** @var array<int, array{title: string, value: string}> */
    protected array $facts = [];

    /** @var array<int, array> */
    protected array $blocks = [];

    /** @var ?array Full Adaptive Card override */
    protected ?array $card = null;

    protected ?string $webhookUrl = null;

    public static function make(?string $text = null): static
    {
        $message = new static;

        if ($text !== null) {
            $message->text($text);
        }

        return $message;
    }

    public function title(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function text(string $text): static
    {
        $this->text = $text;

        return $this;
    }

    public function color(string $color): static
    {
        $this->color = $color;

        return $this;
    }

    public function fact(string $title, string $value): static
    {
        $this->facts[] = ['title' => $title, 'value' => $value];

        return $this;
    }

    /**
     * @param  array<string, string>  $facts
     */
    public function facts(array $facts): static
    {
        foreach ($facts as $title => $value) {
            $this->fact($title, $value);
        }

        return $this;
    }

    /**
     * Append a raw Adaptive Card body element.
     */
    public function block(array $block): static
    {
        $this->blocks[] = $block;

        return $this;
    }

    /**
     * Replace the generated card entirely with a raw Adaptive Card payload
     * (the card content only — the message/attachment envelope stays ours).
     */
    public function card(array $card): static
    {
        $this->card = $card;

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
        return $this->title === null
            && $this->text === null
            && $this->facts === []
            && $this->blocks === []
            && $this->card === null;
    }

    public function toArray(): array
    {
        return [
            'type' => 'message',
            'attachments' => [
                [
                    'contentType' => 'application/vnd.microsoft.card.adaptive',
                    'content' => $this->card ?? $this->buildCard(),
                ],
            ],
        ];
    }

    protected function buildCard(): array
    {
        $body = [];

        if ($this->title !== null) {
            $body[] = array_filter([
                'type' => 'TextBlock',
                'size' => 'Large',
                'weight' => 'Bolder',
                'text' => $this->title,
                'wrap' => true,
                'color' => $this->color,
            ], fn ($value) => $value !== null);
        }

        if ($this->text !== null) {
            $body[] = [
                'type' => 'TextBlock',
                'text' => $this->text,
                'wrap' => true,
            ];
        }

        if ($this->facts !== []) {
            $body[] = [
                'type' => 'FactSet',
                'facts' => $this->facts,
            ];
        }

        return [
            '$schema' => 'http://adaptivecards.io/schemas/adaptive-card.json',
            'type' => 'AdaptiveCard',
            'version' => '1.4',
            'msteams' => ['width' => 'Full'],
            'body' => [...$body, ...$this->blocks],
        ];
    }
}
