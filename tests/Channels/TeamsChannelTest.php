<?php

use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Stboris\FilamentOutbox\Exceptions\CouldNotSendNotification;
use Stboris\FilamentOutbox\Messages\TeamsMessage;

function teamsNotification(mixed $message): Notification
{
    return new class($message) extends Notification
    {
        public function __construct(public mixed $message) {}

        public function via(object $notifiable): array
        {
            return ['teams'];
        }

        public function toTeams(object $notifiable): mixed
        {
            return $this->message;
        }
    };
}

it('sends an adaptive card to the routed workflow url', function () {
    Http::fake(['prod-42.westeurope.logic.azure.test/*' => Http::response(null, 202)]);

    $message = TeamsMessage::make('Order #1001 was created')
        ->title('New order')
        ->fact('Total', '$42.00');

    (new AnonymousNotifiable)
        ->route('teams', 'https://prod-42.westeurope.logic.azure.test/workflows/abc/triggers/manual/paths/invoke')
        ->notify(teamsNotification($message));

    Http::assertSent(function ($request) {
        $card = $request['attachments'][0]['content'];

        return str_contains($request->url(), 'logic.azure.test')
            && $request['type'] === 'message'
            && $request['attachments'][0]['contentType'] === 'application/vnd.microsoft.card.adaptive'
            && $card['body'][0]['text'] === 'New order'
            && $card['body'][1]['text'] === 'Order #1001 was created'
            && $card['body'][2]['facts'][0] === ['title' => 'Total', 'value' => '$42.00'];
    });
});

it('falls back to the configured default webhook url', function () {
    Http::fake(['*' => Http::response(null, 202)]);
    config()->set('filament-outbox.teams.webhook_url', 'https://config.teams.test/hook');

    (new AnonymousNotifiable)->notify(teamsNotification(TeamsMessage::make('Hello')));

    Http::assertSent(fn ($request) => $request->url() === 'https://config.teams.test/hook');
});

it('skips quietly when no webhook url is available anywhere', function () {
    Http::fake();

    (new AnonymousNotifiable)->notify(teamsNotification(TeamsMessage::make('Hello')));

    Http::assertNothingSent();
});

it('wraps a plain string return value into a message', function () {
    Http::fake(['*' => Http::response(null, 202)]);

    (new AnonymousNotifiable)
        ->route('teams', 'https://teams.test/hook')
        ->notify(teamsNotification('Just a string'));

    Http::assertSent(fn ($request) => $request['attachments'][0]['content']['body'][0]['text'] === 'Just a string');
});

it('throws when the endpoint responds with an error', function () {
    Http::fake(['*' => Http::response('Workflow not found', 404)]);

    (new AnonymousNotifiable)
        ->route('teams', 'https://teams.test/hook')
        ->notify(teamsNotification(TeamsMessage::make('Hello')));
})->throws(CouldNotSendNotification::class, 'HTTP 404');

it('throws when the message has no content at all', function () {
    Http::fake();

    (new AnonymousNotifiable)
        ->route('teams', 'https://teams.test/hook')
        ->notify(teamsNotification(TeamsMessage::make()));
})->throws(CouldNotSendNotification::class, 'no content');
