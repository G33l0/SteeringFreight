# Portlane Shipping — working notes

A Laravel application with two halves: a public freight company website with shipment
tracking, and an admin panel where staff manage shipments, customers and site content.

## Layout

- `app/Models` — Eloquent models. `Shipment` is the centre of the domain; `ShipmentStatus`
  drives both the customer timeline and the exception statuses.
- `app/Services` — anything that writes across more than one table:
  `ShipmentService` (shipments, events, progress), `ChatService`, `DocumentService`,
  `MediaService`, `TrackingNumberGenerator`, `AuditLogger`.
- `app/Support` — `Settings` (cached, database backed site settings),
  `SettingDefinitions` (the editable settings and their defaults), `ContentFormatter`
  (safe rendering of administrator written copy; never renders raw HTML),
  `ContentTokens` ([[placeholders]] in page copy, resolved from settings, with unset
  lines dropped), `LaunchChecklist` (what a fresh install still needs).
- `public/assets/brand` — the logo suite, favicons and social card.
  `public/assets/illustrations` — service artwork used until real photography is uploaded.
- `app/Http/Controllers` — public controllers at the root, tracking under `Tracking/`,
  admin under `Admin/`.
- `resources/views/public` and `resources/views/admin`, with shared Blade components in
  `resources/views/components`.

## Conventions

- Two staff roles, in `UserRole`: `Administrator` (master admin, `['*']`) and
  `Representative` (customer representative, chat only). Authorisation goes through gates
  named `area.action` (`shipments.manage`, `settings.manage`, …) resolved from
  `UserRole::permissions()`; model level rules live in `app/Policies`.
- A representative may open a conversation only when it is assigned to them or unassigned;
  `ChatConversationPolicy` is the single place that decides this, and
  `ChatConversation::scopeForRepresentative()` is the matching query scope. Replying to an
  unassigned conversation claims it.
- The customer chat is a window, not a record. It accepts no files at all, and a conversation
  is deleted with its messages `portlane.chat.retention_hours` (24) after its last message —
  `portlane:purge-chat` hourly, plus `ChatService::sweepExpired()` while the chat is used.
  Past that window `hasExpired()` makes it unreadable to everyone, so a missed cron run never
  keeps a thread alive; `scopeWithinRetention()` is the matching query scope. Message bodies
  are never copied into notification emails or the audit log, and the audit descriptions
  reference the tracking number rather than the customer's name.
- Every administrative write is recorded through `AuditLogger`. Never log credentials.
- Customer facing text comes from the database: site settings, services, pages, FAQs and
  shipment records. Do not hard code company details in templates.
- Countries come from `App\Support\Countries` and are validated against it everywhere they
  are entered, so a quote and a shipment for the same lane read identically.
  `Countries::flag()` turns a name (or a common short form) into its flag; the homepage
  destination lanes use it through `x-country-name` / `x-country-list`. The flag is always
  shown beside the country name, never instead of it, because Windows draws flag emoji as the
  two letter code. This is the one place the public site uses emoji.
- Quote requests reach the dashboard and the operations mailbox at once. The alert email sets
  reply-to to the customer, a quotation sent from the panel sets reply-to to the operations
  address and is stored as a `QuoteReply`.
- Never invent business facts. Address, telephone, registration number and operating lanes
  ship empty; the views hide those sections until they are filled in.
- Brand colours are published as CSS custom properties from the settings, so the palette is
  changeable without a front end rebuild.
- Private files (shipment documents) live on the `local` disk and are only ever streamed
  by a controller that checks authorisation. The chat stores no files at all.
- SQLite is a supported production database, not only the test harness: WAL, a busy timeout
  and `synchronous NORMAL` are set in `config/database.php`, `portlane:backup-database` takes
  a consistent copy with `VACUUM INTO`, and `database/.htaccess` keeps the file off the web.
  Keep per request writes off the database file — the chat marks messages read only when
  there is something to mark.
- Tests run on SQLite in memory. `Tests\TestCase` provides `seedCoreData()`,
  `administrator()`, `agent()` and `trackingStatus()`.

## Commands

```sh
composer install && npm install
php artisan migrate --seed
php artisan portlane:create-admin
php artisan test
npm run build
```
