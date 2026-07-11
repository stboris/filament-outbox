<?php

namespace Stboris\FilamentOutbox\Channels;

use Illuminate\Notifications\Notification;
use Stboris\FilamentOutbox\Channels\Concerns\SendsWebhookRequests;
use Stboris\FilamentOutbox\Exceptions\CouldNotSendNotification;
use Stboris\FilamentOutbox\Messages\WebhookMessage;

class WebhookChannel
{
    use SendsWebhookRequests;

    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toWebhook')) {
            throw CouldNotSendNotification::invalidMessage('webhook', 'toWebhook', WebhookMessage::class, null);
        }

        $message = $notification->toWebhook($notifiable);

        if (is_array($message)) {
            $message = WebhookMessage::make($message);
        }

        if (! $message instanceof WebhookMessage) {
            throw CouldNotSendNotification::invalidMessage('webhook', 'toWebhook', WebhookMessage::class, $message);
        }

        if ($message->isEmpty()) {
            throw CouldNotSendNotification::missingContent('webhook');
        }

        [$url, $endpoint] = $this->resolveDestination(
            $message->url(),
            $message->endpointName(),
            $notifiable,
            $notification,
            'webhook',
            'filament-outbox.webhook.url',
        );

        if (! $url) {
            return;
        }

        // The body is encoded once so the signature is computed over the
        // exact bytes that go on the wire.
        $payload = $message->toArray();
        $body = json_encode($payload);

        $headers = ($endpoint?->setting('headers') ?? []) + [];
        $headers = array_merge($headers, $message->getHeaders());

        $secret = $message->getSecret()
            ?? $endpoint?->secret()
            ?? config('filament-outbox.webhook.secret');

        if ($secret) {
            $headers[config('filament-outbox.webhook.signature_header', 'X-Outbox-Signature')] = hash_hmac('sha256', $body, $secret);
        }

        $this->deliver(
            'webhook',
            $url,
            $payload,
            $notification,
            $endpoint,
            headers: $headers,
            method: $message->getMethod(),
            rawBody: $body,
        );
    }
}
