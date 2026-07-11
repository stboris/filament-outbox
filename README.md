# Filament Outbox

Configurable notification channels for Laravel — **Discord**, **Slack**, and **generic signed webhooks** — with a Filament v5 admin panel on top (endpoint management, send history with retry, test-send, model-event triggers).

> **Status: work in progress.** Channels and the Filament admin layer are functional; packaging/licensing split (free vs. Pro) is still pending.

## Requirements

- PHP 8.3+
- Laravel 13
- Filament v5 (only for the admin panel features)

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

## Filament panel (Pro)

Publish the migrations and register the plugin on your panel:

```bash
php artisan vendor:publish --tag="filament-outbox-migrations"
php artisan migrate
```

```php
use Stboris\FilamentOutbox\FilamentOutboxPlugin;

public function panel(Panel $panel): Panel
{
    return $panel->plugins([
        FilamentOutboxPlugin::make(),
    ]);
}
```

You get two resources under an **Outbox** navigation group:

- **Endpoints** — named destinations (Discord/Slack/webhook URL, per-environment scoping, enable toggle, channel defaults like bot username or extra headers, webhook signing secret) with a **Send test** action per row.
- **Send history** — every send attempt with status, HTTP code, payload preview, and a **Retry** action for failures. Retries are recorded as new, linked history rows. Old rows are pruned by `php artisan model:prune` after `history.prune_after_days` (default 30).

### Named endpoints from code

```php
public function toDiscord(object $notifiable): DiscordMessage
{
    return DiscordMessage::make('Deploy finished ✅')->endpoint('team-alerts');
}
```

The endpoint supplies the URL, channel defaults, and (for webhooks) the signing secret. Disabled endpoints and endpoints scoped to another environment skip quietly — so `->endpoint('team-alerts')` is safe to leave in code across local/staging/production.

### Model-event triggers

Send notifications automatically when models change — no notification class needed:

```php
// config/filament-outbox.php
'triggers' => [
    \App\Models\Order::class => [
        'events' => ['created', 'deleted'],
        'channels' => ['discord', 'webhook'],
        'endpoints' => ['discord' => 'team-alerts'],
        'template' => 'Order #{id} was {event} — total {total}',
        'queue' => true, // send via queue with retry/backoff (3 tries)
    ],
],
```

Templates accept `{attribute}` placeholders (dot notation supported) plus `{model}`, `{key}` and `{event}`. Webhook channels additionally receive a structured JSON payload (`event`, `model`, `key`, `attributes`, `occurred_at`).

## Testing

```bash
composer test
```

## License

MIT for the base channels. The Filament admin panel (Pro) will be licensed separately.
