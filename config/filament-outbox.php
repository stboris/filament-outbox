<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Discord
    |--------------------------------------------------------------------------
    |
    | Default webhook URL used when neither the notification message nor the
    | notifiable (via routeNotificationForDiscord) provides one.
    |
    */

    'discord' => [
        'webhook_url' => env('OUTBOX_DISCORD_WEBHOOK_URL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Slack (incoming webhook)
    |--------------------------------------------------------------------------
    */

    'slack' => [
        'webhook_url' => env('OUTBOX_SLACK_WEBHOOK_URL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Microsoft Teams (Workflows webhook)
    |--------------------------------------------------------------------------
    |
    | URL from a Power Automate workflow using the "When a Teams webhook
    | request is received" trigger — the replacement for the retired Office
    | 365 Connector webhooks. Messages are sent as Adaptive Cards.
    |
    */

    'teams' => [
        'webhook_url' => env('OUTBOX_TEAMS_WEBHOOK_URL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Generic webhook
    |--------------------------------------------------------------------------
    |
    | When a secret is set (here or per message), every payload is signed with
    | an HMAC-SHA256 hex digest of the raw JSON body, sent in the header named
    | below, so receivers can verify authenticity.
    |
    */

    'webhook' => [
        'url' => env('OUTBOX_WEBHOOK_URL'),
        'secret' => env('OUTBOX_WEBHOOK_SECRET'),
        'signature_header' => 'X-Outbox-Signature',
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP client
    |--------------------------------------------------------------------------
    */

    'http' => [
        'timeout' => 5,
        'connect_timeout' => 3,

        /*
         | Transient failures (connection errors, HTTP 429 and 5xx) are
         | retried with exponential backoff. 'times' is the total number of
         | attempts including the first; set it to 1 to disable retries.
         | 'delay' is the base delay in milliseconds, doubled per retry.
         */
        'retry' => [
            'times' => 2,
            'delay' => 250,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Send history
    |--------------------------------------------------------------------------
    |
    | Every send attempt is recorded in the outbox_messages table (viewable in
    | the Filament panel with payload preview and a retry action). Recording
    | is skipped automatically when the table has not been migrated. Rows
    | older than 'prune_after_days' are removed by `php artisan model:prune`.
    |
    */

    'history' => [
        'enabled' => env('OUTBOX_HISTORY_ENABLED', true),
        'prune_after_days' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Model-event triggers
    |--------------------------------------------------------------------------
    |
    | Send notifications automatically when models change. Templates may use
    | {attribute} placeholders (dot notation allowed) plus {model}, {key} and
    | {event}. 'endpoints' maps each channel to an endpoint name managed in
    | the panel; without one, the channel's configured default URL is used.
    | With 'queue' => true, sending happens on the queue with retry/backoff.
    |
    */

    'triggers' => [
        // \App\Models\Order::class => [
        //     'events' => ['created', 'updated', 'deleted'],
        //     'channels' => ['discord', 'webhook'],
        //     'endpoints' => ['discord' => 'team-alerts'],
        //     'template' => 'Order #{id} was {event} — total {total}',
        //     'queue' => false,
        // ],
    ],

];
