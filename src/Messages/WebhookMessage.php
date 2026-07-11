<?php

namespace Stboris\FilamentOutbox\Messages;

use Stboris\FilamentOutbox\Messages\Concerns\RoutesToEndpoint;

class WebhookMessage
{
    use RoutesToEndpoint;

    protected array $data = [];

    /** @var array<string, string> */
    protected array $headers = [];

    protected ?string $secret = null;

    protected ?string $url = null;

    protected string $method = 'post';

    public static function make(array $data = []): static
    {
        return (new static)->data($data);
    }

    public function data(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function header(string $name, string $value): static
    {
        $this->headers[$name] = $value;

        return $this;
    }

    public function headers(array $headers): static
    {
        foreach ($headers as $name => $value) {
            $this->header($name, $value);
        }

        return $this;
    }

    /**
     * Sign the JSON body with HMAC-SHA256 using this secret (overrides the
     * configured default secret).
     */
    public function secret(string $secret): static
    {
        $this->secret = $secret;

        return $this;
    }

    public function getSecret(): ?string
    {
        return $this->secret;
    }

    /**
     * Send to this URL instead of the notifiable route / config default.
     */
    public function to(string $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function url(): ?string
    {
        return $this->url;
    }

    public function method(string $method): static
    {
        $this->method = strtolower($method);

        return $this;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function isEmpty(): bool
    {
        return $this->data === [];
    }

    public function toArray(): array
    {
        return $this->data;
    }
}
