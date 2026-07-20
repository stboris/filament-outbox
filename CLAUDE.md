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

## Merchant of record: switching Lemon Squeezy → Polar (decided 2026-07-20)

Lemon Squeezy store activation, requested 2026-07-14, sat unactivated with zero support
response for 6+ days. Research before switching: Trustpilot/Product Hunt/Reddit show a
consistent, high-volume pattern of opaque "risk" account holds and multi-week support
silence — not isolated complaints (one aggregator rates support-unresponsiveness 8/10 and
unexplained-suspension 10/10 severity across dozens of sources). Structural cause found:
Stripe (which acquired Lemon Squeezy in 2024) launched its own native MoR product,
**Stripe Managed Payments**, in public preview Feb 2026, and Lemon Squeezy's own blog now
frames itself as a migration path *into* that product — strong signal LS is being wound
down, plausibly explaining the stalled, unanswered activation.

Stripe Managed Payments itself was ruled out: it has **no native license-key or file-
delivery support** — would need a bolted-on third-party service (Keyforge/CheckoutKeys),
adding a dependency against this project's "minimal dependencies" constraint.

**Decision: move to [Polar.sh](https://polar.sh).** Still a genuine merchant of record
(handles VAT/tax — the same requirement that ruled out Anystack/Privato originally), has
native License Keys + File Downloads benefits matching the current LS setup feature-for-
feature, cheaper (4% + $0.40 vs LS's 5% + $0.50), and currently has a materially better
support/trust reputation than LS post-acquisition. Caveat: Polar's own docs say initial
account review can also take up to 14 days, and their own guidance is to fully build the
product/checkout *before* submitting for review — do that first to not lose the time twice.
KYC is individual-friendly: passport/ID + selfie via Stripe Identity, no company entity
needed (this was Boris's blocker for a Microsoft 365 tenant test account, but is not one
here).

**Switching cost right now: zero.** No live sales, no issued license keys, no order
history on Lemon Squeezy — this is the cheapest point to switch. Lemon Squeezy store can
simply be abandoned (deactivate later; no urgency, nothing to migrate).

**Setup steps (Boris — account creation/KYC/payout only he can do):**
1. Sign up at polar.sh (GitHub/Google/email), create an organization.
2. Build the product FIRST, before submitting for review (their own advice for faster
   approval): one-time purchase, $59, License Keys benefit (unlimited activations or match
   LS's 10 — Polar's config may differ, check at setup time), File Downloads benefit —
   upload `filament-outbox-pro-v1.1.0.zip` (already built, see Phase 5a below). Reuse the
   Lemon Squeezy product description drafted 2026-07-20 (mentions Teams).
3. Submit for review; complete identity verification (Stripe Identity) and connect a
   payout account (Stripe Connect Express).
4. Once approved and a real checkout link exists, tell Claude — the site swap (buy button,
   FAQ copy, impressum.html + privacy.html merchant-of-record sections, README pricing
   copy) is a batch of file edits ready to run in one pass. Do NOT update the legal pages
   to name Polar before the relationship is real.
5. Deactivate (don't delete) the Lemon Squeezy store/product once Polar is live.

## Immediate next steps (start here)

Done so far: free repo public on GitHub + tagged v1.0.0; pro repo private on GitHub
(`stboris/filament-outbox-pro`, workbench demo app + docs/LAUNCH.md); **Phase 4 landing
page built** in `~/PhpStormProjects/filament-outbox-site` (private repo
`stboris/filament-outbox-site`, static HTML + Tailwind v4, privacy/impressum pages with
cookieless-Matomo wording, deploy.sh for cyon). **Domain bought: filamentoutbox.com**
(2026-07-13). Distribution decision re-confirmed: Lemon Squeezy MoR (Anystack/Privato
rejected — not merchants of record, own-Stripe VAT burden).

**LAUNCHED (2026-07-14):** Packagist live; demo GIFs recorded (docs/demo in pro repo,
asset map in docs/LAUNCH.md); Lemon Squeezy store + product live ($59 USD, license keys
unlimited length/10 activations, dist zip v1.0.0 attached, test purchase verified);
site deployed with real checkout URL + Matomo + demo videos.

filamentphp.com listing: **APPROVED and live (2026-07-16)** after one image rework.

Lemon Squeezy store: **awaiting LS activation** — checkout still opens in test mode, so
the site's Buy button is temporarily disabled (2026-07-16): disabled-look button +
notify-me mailto (info@filamentoutbox.com), original link kept in `TEMP(store-approval)`
comment markers in filament-outbox-site/index.html. To reinstate when LS approves:
delete the temp block, uncomment the original, `./deploy.sh`.

Remaining (Boris):
1. When Lemon Squeezy activates the store — reinstate the Buy button (see above) and
   email anyone who wrote to info@filamentoutbox.com asking to be notified
2. Announce: Filament Discord #plugins, Laravel News links form
3. Regenerate the Discord webhook used for demo recording (URL was shared in chat)

## Phase 5 — Outbox v1.1/v1.2 roadmap (planned 2026-07-19)

Decision: extend Outbox instead of starting a second product (no sales data yet; existing
funnel is live; marginal cost low). Market research 2026-07-19: ~79 paid of 921 directory
plugins; Outbox is still the only paid outbound-delivery plugin (Ralph J. Smit's
Notifications Pro is in-app/database only — no overlap). Second-product candidate parked:
unified ops suite (schedule+queue monitoring w/ alert rules, synergy with Outbox channels).

**5a. Teams channel — SHIPPED (2026-07-20).** Free v1.1.0 tagged + pushed, live on
Packagist within seconds via the GitHub webhook. Pro v1.1.0 tagged + pushed; dist zip
built at filament-outbox-pro/filament-outbox-pro-v1.1.0.zip (Teams code confirmed present
inside). Validated without a Teams business tenant (Boris didn't have one and didn't want
to fake a company signup): Adaptive Cards Designer (adaptivecards.io/designer, schema-valid
render) + webhook.site wire-level capture (byte-exact JSON, emoji/encoding intact). Real
Microsoft Workflows acceptance still unconfirmed — low risk, it's their documented schema.

**Remaining for this release (Boris):** upload `filament-outbox-pro-v1.1.0.zip` to the
Lemon Squeezy product as a new file version (Products → Filament Outbox Pro → Files) —
manual step, no LS API access configured. Fine to do even before store activation.

**5a original spec (~2 evenings) — free repo + pro touchpoints**
- Target the NEW mechanism: Power Automate Workflows webhook URLs + Adaptive Card payload
  (`{"type":"message","attachments":[{"contentType":"application/vnd.microsoft.card.adaptive",
  "content":{AdaptiveCard 1.4}}]}`). Classic Office 365 Connector webhooks retire
  2026-05-18..22 — old `outlook.office.com/webhook/` URLs die. MessageCard is deprecated
  (Workflows accepts it, but buttons don't render). Marketing angle: the free
  laravel-notification-channels/microsoft-teams package is MessageCard-era; we're
  Workflows-native.
- Free repo: `Messages/TeamsMessage` (fluent: make/title/facts/color + RoutesToEndpoint),
  `Channels/TeamsChannel` (mirror SlackChannel: `toTeams()`, string coercion,
  resolveDestination with `filament-outbox.teams.webhook_url`, endpoint setting defaults),
  config `teams.webhook_url` (OUTBOX_TEAMS_WEBHOOK_URL), provider alias `teams`,
  `outbox:test` + OutboxTestNotification support. Workflows replies 202/empty — any 2xx is
  success; existing 429/5xx retry applies. Pest tests w/ Http::fake (card shape, routing,
  invalid-message exceptions).
- Pro repo: add `teams` to OutboxEndpointForm channel Select (+ visible() settings if any),
  test-send + triggers accept it (channel column is a plain string — no migration),
  Resender needs nothing (re-signing is webhook-only). Livewire tests.

**5b. Telegram channel (~2 evenings)**
- Bot API `POST https://api.telegram.org/bot{token}/sendMessage` with chat_id/text/
  parse_mode (HTML to start; MarkdownV2 escaping is a minefield — punt). No buttons in v1.
- CAUTION: the bot token is IN the URL — history recording and exceptions must store a
  redacted URL (`bot***:***/sendMessage`). Pro form: token as password input, chat_id field.
- Config `telegram.{bot_token,chat_id}`; endpoint settings override both.

**5c. Alert hygiene: throttle/dedup (~2–3 evenings, pro — needs DB)**
- `->dedupKey('deploy-failed')` on messages + per-trigger `throttle` config (max 1 send
  per key per N minutes). Implement via outbox_messages lookup (cache fallback);
  suppressed sends recorded with new MessageStatus::Suppressed so the history shows them.
  This is the feature no competitor has; justifies later $59→$79 price move.

**5d. Endpoint health (~1–2 evenings, pro)**
- Success-rate widget per endpoint (24h/7d from outbox_messages), consecutive-failure
  alarm: "when endpoint X fails N times in a row, notify via endpoint Y".

**5e. Digests/batching (optional — only if users ask; cut by default)**

Release train per phase: free repo tag minor → rebuild pro dist zip → upload to Lemon
Squeezy release → README both repos → site feature grid + og copy if needed → announce.

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
