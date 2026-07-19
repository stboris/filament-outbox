<?php

namespace Stboris\FilamentOutbox\Commands;

use Illuminate\Console\Command;
use Illuminate\Notifications\AnonymousNotifiable;
use Stboris\FilamentOutbox\Notifications\OutboxTestNotification;
use Throwable;

class OutboxTestCommand extends Command
{
    protected $signature = 'outbox:test
        {channel : The channel to test: discord, slack, teams or webhook}
        {--to= : Destination URL (defaults to the configured one)}
        {--message= : Custom text for the test notification}';

    protected $description = 'Send a test notification through an Outbox channel to verify an endpoint';

    public function handle(): int
    {
        $channel = strtolower((string) $this->argument('channel'));

        if (! OutboxTestNotification::supports($channel)) {
            $this->error("Unknown channel [{$channel}]. Available: discord, slack, teams, webhook.");

            return self::FAILURE;
        }

        $url = $this->option('to') ?: $this->defaultUrl($channel);

        if (! $url) {
            $this->error("No destination URL: pass --to=<url> or set a default in config/filament-outbox.php ({$channel}).");

            return self::FAILURE;
        }

        try {
            (new AnonymousNotifiable)
                ->route($channel, $url)
                ->notifyNow(new OutboxTestNotification($channel, $this->option('message')));
        } catch (Throwable $e) {
            $this->error("Sending failed: {$e->getMessage()}");

            return self::FAILURE;
        }

        $this->info("Test notification sent via [{$channel}] to {$url}");

        return self::SUCCESS;
    }

    protected function defaultUrl(string $channel): ?string
    {
        return match ($channel) {
            'webhook' => config('filament-outbox.webhook.url'),
            default => config("filament-outbox.{$channel}.webhook_url"),
        };
    }
}
