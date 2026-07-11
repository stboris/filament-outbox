<?php

namespace Stboris\FilamentOutbox\Channels\Concerns;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Stboris\FilamentOutbox\Exceptions\CouldNotSendNotification;
use Stboris\FilamentOutbox\Models\OutboxEndpoint;
use Stboris\FilamentOutbox\Support\OutboxHistory;
use Throwable;

trait SendsWebhookRequests
{
    protected function httpClient(): PendingRequest
    {
        $client = Http::timeout(config('filament-outbox.http.timeout', 5))
            ->connectTimeout(config('filament-outbox.http.connect_timeout', 3))
            ->acceptJson();

        $times = (int) config('filament-outbox.http.retry.times', 1);

        if ($times > 1) {
            $delay = (int) config('filament-outbox.http.retry.delay', 250);

            $client = $client->retry(
                $times,
                fn (int $attempt) => $delay * 2 ** ($attempt - 1),
                fn (Throwable $exception) => $this->isTransientFailure($exception),
                throw: false,
            );
        }

        return $client;
    }

    /**
     * Connection problems, rate limits (429) and server errors (5xx) are
     * worth retrying; other client errors are permanent.
     */
    protected function isTransientFailure(Throwable $exception): bool
    {
        if ($exception instanceof ConnectionException) {
            return true;
        }

        return $exception instanceof RequestException
            && ($exception->response->status() === 429 || $exception->response->serverError());
    }

    /**
     * Resolve where to send: explicit message URL first, then a named
     * endpoint, then the notifiable's route, then the configured default.
     * A disabled endpoint (or one scoped to another environment) resolves
     * to null so the send is skipped deliberately; a missing or channel-
     * mismatched endpoint name is a bug and throws.
     *
     * @return array{0: ?string, 1: ?OutboxEndpoint}
     */
    protected function resolveDestination(
        ?string $messageUrl,
        ?string $endpointName,
        object $notifiable,
        Notification $notification,
        string $channel,
        string $configKey,
    ): array {
        if ($messageUrl) {
            return [$messageUrl, null];
        }

        if ($endpointName !== null) {
            $endpoint = OutboxEndpoint::findByName($endpointName);

            if (! $endpoint || $endpoint->channel !== $channel) {
                throw CouldNotSendNotification::unknownEndpoint($channel, $endpointName);
            }

            return $endpoint->isActive() ? [$endpoint->url, $endpoint] : [null, null];
        }

        if (method_exists($notifiable, 'routeNotificationFor')) {
            $route = $notifiable->routeNotificationFor($channel, $notification);

            if ($route) {
                return [$route, null];
            }
        }

        return [config($configKey), null];
    }

    /**
     * Send the request, record the attempt in the send history, and raise a
     * descriptive exception on failure. Webhook sends pass their raw body so
     * the HMAC signature stays valid for the exact bytes on the wire.
     */
    protected function deliver(
        string $channel,
        string $url,
        array $payload,
        Notification $notification,
        ?OutboxEndpoint $endpoint = null,
        array $headers = [],
        string $method = 'post',
        ?string $rawBody = null,
    ): void {
        // Anonymous class names embed a NUL byte and the file path — strip it.
        $notificationClass = strstr($notification::class, "\0", true) ?: $notification::class;

        $record = OutboxHistory::record([
            'outbox_endpoint_id' => $endpoint?->id,
            'channel' => $channel,
            'url' => $url,
            'method' => $method,
            'headers' => $headers ?: null,
            'payload' => $payload,
            'notification' => $notificationClass,
        ]);

        try {
            $request = $this->httpClient();

            if ($headers) {
                $request = $request->withHeaders($headers);
            }

            $response = $rawBody !== null
                ? $request->withBody($rawBody, 'application/json')->{$method}($url)
                : $request->{$method}($url, $payload);
        } catch (Throwable $exception) {
            $record?->markFailed(null, $exception->getMessage());

            throw CouldNotSendNotification::connectionFailed($channel, $exception);
        }

        if ($response->failed()) {
            $record?->markFailed($response->status(), $response->body());

            throw CouldNotSendNotification::serviceRespondedWithAnError($channel, $response->status(), $response->body());
        }

        $record?->markSent($response->status());
    }
}
