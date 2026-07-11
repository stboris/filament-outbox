<?php

namespace Stboris\FilamentOutbox\Support;

use Stboris\FilamentOutbox\Channels\Concerns\SendsWebhookRequests;
use Stboris\FilamentOutbox\Enums\MessageStatus;
use Stboris\FilamentOutbox\Models\OutboxMessage;
use Throwable;

/**
 * Replays a recorded send as a new history row, so retries stay auditable.
 * Never throws — the caller (the panel's retry action) reads the outcome
 * off the returned record.
 */
class Resender
{
    use SendsWebhookRequests;

    public function resend(OutboxMessage $original): OutboxMessage
    {
        $payload = $original->payload ?? [];
        $headers = $original->headers ?? [];
        $method = $original->method ?: 'post';
        $rawBody = null;

        if ($original->channel === 'webhook') {
            $rawBody = json_encode($payload);

            // Re-sign when the endpoint still knows the secret; otherwise the
            // stored signature remains valid for the identical body.
            if ($secret = $original->endpoint?->secret) {
                $header = config('filament-outbox.webhook.signature_header', 'X-Outbox-Signature');
                $headers[$header] = hash_hmac('sha256', $rawBody, $secret);
            }
        }

        $record = OutboxMessage::create([
            'outbox_endpoint_id' => $original->outbox_endpoint_id,
            'resent_from_id' => $original->id,
            'channel' => $original->channel,
            'url' => $original->url,
            'method' => $method,
            'headers' => $headers ?: null,
            'payload' => $payload,
            'notification' => $original->notification,
            'status' => MessageStatus::Pending,
        ]);

        try {
            $request = $this->httpClient();

            if ($headers) {
                $request = $request->withHeaders($headers);
            }

            $response = $rawBody !== null
                ? $request->withBody($rawBody, 'application/json')->{$method}($original->url)
                : $request->{$method}($original->url, $payload);
        } catch (Throwable $exception) {
            $record->markFailed(null, $exception->getMessage());

            return $record;
        }

        $response->failed()
            ? $record->markFailed($response->status(), $response->body())
            : $record->markSent($response->status());

        return $record;
    }
}
