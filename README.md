# Filament Outbox

Configurable notification channels for Laravel - **Discord**, **Slack**, **Microsoft Teams**, and **generic signed webhooks** - built on Laravel's native notification system. No new APIs to learn: write a normal `Notification` class, return a message object, done.

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
                ->title('LONG opened - BTCUSDT')
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
    return SlackMessage::make('Trade closed - +$42.00')
        ->section('*LONG closed - BTCUSDT*');
}
```

### Microsoft Teams (Workflows webhook)

Sends [Adaptive Cards](https://adaptivecards.io) to the webhook URL of a Power Automate workflow (the *"When a Teams webhook request is received"* trigger) - the mechanism that replaces the retired Office 365 Connector webhooks.

```php
use Stboris\FilamentOutbox\Messages\TeamsMessage;

public function via(object $notifiable): array
{
    return ['teams'];
}

public function toTeams(object $notifiable): TeamsMessage
{
    return TeamsMessage::make('Deploy finished without errors.')
        ->title('Deployment')
        ->color('good')
        ->facts(['Version' => 'v2.14.0', 'Environment' => 'production']);
}
```

`->block([...])` appends raw Adaptive Card elements; `->card([...])` replaces the generated card entirely.

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
php artisan outbox:test teams --to="https://.../workflows/.../invoke"
php artisan outbox:test webhook --message="Custom check"
```

## Resilient sending

Transient failures - connection errors, HTTP 429 rate limits, and 5xx responses - are automatically retried with exponential backoff (2 attempts by default, configurable via `http.retry` in the config). Permanent client errors (4xx) fail immediately with a descriptive `CouldNotSendNotification` exception.

## Testing

```bash
composer test
```

## License

MIT.
