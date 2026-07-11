# Project: Filament Notification Channel Plugin — "Filament Outbox" (`stboris/filament-outbox`)

## Context / handoff

This project was planned in claude.ai with Boris. Goal: a sellable Filament plugin that
generates ~$100+/month in passive income with ~2h/week maintenance after launch.
Build effort target: ~2 weeks of evenings to a sellable v1.

Boris's background (relevant): Laravel 13 + Filament v5 experience (crypto-predictor app),
existing working Discord webhook integration code in that project (reuse it!), DDEV local
dev on Mac, deploys to cyon shared hosting via SSH/git.

## What we're building

A Laravel package + Filament v5 plugin: configurable notification channels
(Discord, Slack, generic webhook) with a Filament admin UI on top.

Key differentiator vs. free `laravel-notification-channels` packages: the **Filament admin
layer** — endpoint management UI, send history with retry, test-send button, per-model-event
triggers. Free packages only give you the raw sending channel; we sell the management UX.

## Package identity & conventions

- Composer package built on `spatie/laravel-package-tools` conventions
- Requires: Laravel 13+, Filament v5 (Livewire 4), PHP 8.3+
- Filament v5 API notes (from prior migration work): resources in namespaced subfolders,
  `Filament\Schemas\Schema` for infolists, `recordActions()` instead of `actions()`,
  unified `Filament\Actions\` namespace, nav property types `string|BackedEnum|null`

## Feature scope (v1)

### Free tier (discovery funnel)
- Single channel: Discord webhook notification channel class
- No admin UI — just the Laravel Notification channel
- Published on Packagist + listed free on filamentphp.com/plugins

### Pro tier ($49–79 one-time, sold via Lemon Squeezy — merchant of record, handles EU/CH VAT)
- Slack channel + generic webhook channel
- Filament resource: manage endpoints (CRUD, per-environment config)
- Send history table widget with status, payload preview, retry action
- Test-send button per endpoint
- Trigger notifications on model events (created/updated/deleted) via config
- Message templating (placeholders from model attributes)
- Queued sending with retry/backoff, failure logging

## Status (2026-07-11)

- **Phase 1 done:** 3 channels (Discord/Slack/signed webhook), retry/backoff, `outbox:test` command.
- **Phase 2 done:** OutboxEndpoint + OutboxMessage models/migrations, history recording in the
  channels (auto-disabled without the table), named endpoints (`->endpoint('name')`) with
  per-environment scoping + channel defaults, Resender (retries recorded as new linked rows,
  webhook bodies re-signed), config-driven model-event triggers with `{placeholder}` templating
  and optional queued sending (3 tries, 10/60/300s backoff), FilamentOutboxPlugin with
  OutboxEndpointResource (CRUD + test-send action) and OutboxMessageResource (read-only history,
  payload preview, retry). 87 Pest tests green incl. Livewire resource tests.
- **Phase 3 split done (2026-07-11):** channels decoupled via container-bound contracts
  (`Contracts\{EndpointResolver,Endpoint,HistoryRecorder,HistoryRecord}`, bound by the pro
  provider; channels degrade gracefully unbound — no history, descriptive exception for
  `->endpoint()`). Pro code physically moved to sibling repo
  `~/PhpStormProjects/filament-outbox-pro` (`stboris/filament-outbox-pro`, license
  proprietary, same `Stboris\FilamentOutbox` namespace root, path-repo dev dependency on
  the free package, provider `FilamentOutboxProServiceProvider`, migration publish tag is
  now `filament-outbox-pro-migrations`). This repo = free package: Channels/Messages/
  Commands/Exceptions/Contracts + OutboxTestNotification, no Filament dependency, 53 tests.
  Pro repo: Models/Enums/Support/Jobs/Triggers/Filament + migrations, 36 tests. READMEs
  rewritten for both.
- Test harness (pro repo): testbench + RefreshDatabase; Filament tests need the blade-icons
  providers and `#[WithMigration]` (users table) — see tests/TestCase.php +
  tests/FilamentTestCase.php in filament-outbox-pro. Free repo tests run without a database.

## Build phases

1. **Phase 1 (2–3 evenings):** package skeleton (composer.json, service provider,
   config), Discord channel class ported from crypto-predictor, Slack + generic webhook
   channels, tests (Pest)
2. **Phase 2 (3–4 evenings):** Filament plugin class, endpoint resource, send-history
   widget, test-send action, model-event trigger wiring
3. **Phase 3 (2–3 evenings):** README + docs site page, demo video/GIFs, Lemon Squeezy
   product + license key delivery
4. **Phase 4 (2–3 evenings): Product landing page.** Professional marketing site for the
   plugin — must NOT look AI-generated; benchmark against landing pages of best-selling
   Filament plugin authors (e.g. Ralph J. Smit's plugin pages at ralphjsmit.com, Kenneth
   Sese's Advanced Tables, filamentphp.com featured paid plugins — research current best
   examples at design time). Tech stack: **static HTML + Tailwind CSS** (standalone Tailwind
   CLI, no Laravel/Filament, no JS framework) — Lemon Squeezy checkout is a hosted
   link/overlay so no backend is needed, and a static site deploys anywhere (cyon shared
   hosting or any static host) with zero maintenance. Content: dark-mode hero with real
   admin-UI screenshot, feature grid, short code snippet (`->endpoint('name')` DX), demo
   GIF from Phase 3, Free-vs-Pro pricing table with Lemon Squeezy buy button, FAQ,
   changelog/docs links. Flow: build design → Boris reviews and buys a domain to match →
   deploy → then submit the filamentphp.com/plugins listing (moved here from Phase 3, since
   the listing links to this page as the purchase URL).

## Immediate next steps (start here)

Phase 3 remainder — everything scriptable is done (free repo pushed + tagged v1.0.0,
pro repo committed locally with workbench demo app + docs/LAUNCH.md launch checklist,
gh CLI installed). Blocked on Boris only:

1. `gh auth login` — then Claude can create the private `stboris/filament-outbox-pro`
   repo, push it, and make the free repo public
2. Submit free package on Packagist: https://packagist.org/packages/submit (+ webhook)
3. Record demo GIFs — shot list + seeded demo panel in pro repo's docs/LAUNCH.md
   (`composer serve`, login demo@example.com/password)
4. Lemon Squeezy store + product — full checklist and product copy in docs/LAUNCH.md
5. Then Phase 4: landing page → domain → filamentphp.com/plugins listing

## Decisions already made

- **Name: "Filament Outbox"** (`stboris/filament-outbox`, namespace `Stboris\FilamentOutbox`).
  Chosen 2026-07-11 after research: "filament-notifier" collides with
  `usamamuneerchaudhary/filament-notifier` on Packagist — an actively maintained free (MIT)
  competitor (Filament 4/5, Laravel 11–13) with templates, scheduling, analytics, preferences
  API. Its weakness: single global endpoint per channel type, own parallel notification system
  (not Laravel-native), shallow drivers with swallowed errors. Our differentiators: native
  Laravel Notification integration, multi-endpoint, signed webhooks, proper failure handling.
  "filament-outbox" had zero collisions on Packagist and GitHub. Rejected: filament-courier
  (Courier.com SaaS brand), filament-signals/relay (occupied/crowded).
- **Packaging: two packages at release.** Free `stboris/filament-outbox` on Packagist (channels
  only, MIT, no Filament dependency — usable in any Laravel app). Pro package (Filament plugin,
  requires free package + filament ^5) distributed privately via Lemon Squeezy license keys —
  paid code can't live on public Packagist anyway. During development: single repo,
  filament/filament in require-dev; split happens in Phase 3. The code layout already matches
  the split line (Channels/Messages/Commands = free, future Filament/ = pro).
- Distribution: Lemon Squeezy (not Gumroad) — handles VAT as merchant of record
- Pricing: free single-channel tier, Pro $49–79 one-time (revisit subscription later)
- Marketing channels: filamentphp.com/plugins directory, Filament Discord, Laravel News
- Deliberately NOT building: WhatsApp (existing competitor plugin covers it), full SaaS

## Constraints

- Keep maintenance surface small: minimal dependencies, no custom JS if avoidable
- Must work on typical shared hosting (Boris's users may be on similar hosts as cyon)
- Support Laravel 13 / Filament v5 only at launch (no legacy version matrix)
