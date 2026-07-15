# Filament Outbox

Configurable notification channels for Laravel — **Discord**, **Slack**, and **generic signed webhooks** — built on Laravel's native notification system. No new APIs to learn: write a normal `Notification` class, return a message object, done.

Need an admin UI? [Filament Outbox Pro](#filament-outbox-pro) adds a Filament v5 panel on top: endpoint management, send history with retry, test-send buttons, and model-event triggers.

## Requirements

- PHP 8.3+
- Laravel 13

## Installation

```bash
composer require stboris/filament-outbox
```

Optionally publish the config:

```bash
php artisan vendor:publish --tag="filament-outbox-config"
```

## Usage

### Discord

```php
use Stboris\FilamentOutbox\Messages\DiscordMessage;
use Stboris\FilamentOutbox\Messages\DiscordEmbed;

class TradeOpened extends Notification
{
    public function via(object $notifiable): array
    {
        return ['discord'];
    }

    public function toDiscord(object $notifiable): DiscordMessage
    {
        return DiscordMessage::make()
            ->username('Trading Bot')
            ->embed(fn (DiscordEmbed $embed) => $embed
                ->title('LONG opened — BTCUSDT')
                ->color('#2A78D6')
                ->fields([
                    'Entry price' => '$100.00',
                    'Invested' => '$500.00',
                ])
                ->timestamp());
    }
}
```

The webhook URL is resolved in this order:

1. `->to($url)` on the message
2. `routeNotificationForDiscord()` on the notifiable
3. `config('filament-outbox.discord.webhook_url')` (env `OUTBOX_DISCORD_WEBHOOK_URL`)

### Slack (incoming webhook)

```php
use Stboris\FilamentOutbox\Messages\SlackMessage;

public function via(object $notifiable): array
{
    // The 'slack' alias is only claimed when laravel/slack-notification-channel
    // is not installed; the class name always works.
    return [\Stboris\FilamentOutbox\Channels\SlackChannel::class];
}

public function toSlack(object $notifiable): SlackMessage
{
    return SlackMessage::make('Trade closed — +$42.00')
        ->section('*LONG closed — BTCUSDT*');
}
```

### Generic webhook

```php
use Stboris\FilamentOutbox\Messages\WebhookMessage;

public function via(object $notifiable): array
{
    return ['webhook'];
}

public function toWebhook(object $notifiable): WebhookMessage
{
    return WebhookMessage::make([
        'event' => 'trade.closed',
        'symbol' => 'BTCUSDT',
        'pnl' => 42.0,
    ])->header('X-Source', 'my-app');
}
```

When a secret is configured (`OUTBOX_WEBHOOK_SECRET` or `->secret(...)` on the message), the raw JSON body is signed with HMAC-SHA256 and the hex digest is sent in the `X-Outbox-Signature` header, so receivers can verify authenticity:

```php
hash_equals($request->header('X-Outbox-Signature'), hash_hmac('sha256', $request->getContent(), $secret));
```

## Testing an endpoint

Verify any endpoint from the command line before relying on it:

```bash
php artisan outbox:test discord --to="https://discord.com/api/webhooks/..."
php artisan outbox:test slack                     # uses the configured default URL
php artisan outbox:test webhook --message="Custom check"
```

## Resilient sending

Transient failures — connection errors, HTTP 429 rate limits, and 5xx responses — are automatically retried with exponential backoff (2 attempts by default, configurable via `http.retry` in the config). Permanent client errors (4xx) fail immediately with a descriptive `CouldNotSendNotification` exception.

## Filament Outbox Pro

The Pro package adds a [Filament v5](https://filamentphp.com) admin layer on top of these channels.

<p>
  <img src="https://filamentoutbox.com/assets/demo/test-send.gif" alt="Test-send from the Filament panel" width="49%">
  <img src="https://filamentoutbox.com/assets/demo/discord-receive.gif" alt="The test notification arriving in Discord" width="49%">
</p>

- **Endpoint management** — named destinations managed in the panel instead of `.env` files: URL, per-environment scoping, enable toggle, channel defaults (bot username, extra headers, …), webhook signing secrets. Reference them from code with `->endpoint('team-alerts')` on any message.
- **Send history** — every send attempt recorded with status, HTTP code, and payload preview; failed sends can be retried from the panel, with retries recorded as new, linked rows.
- **Test-send** — a button on every endpoint to verify it end to end.
- **Model-event triggers** — notify on `created`/`updated`/`deleted` for any model, with `{placeholder}` message templates and optional queued sending with retry/backoff. No notification class needed.

Pro is a separate, commercial package that plugs into this one — nothing in your notification code changes when you add it.

## Testing

```bash
composer test
```

## License

MIT.
