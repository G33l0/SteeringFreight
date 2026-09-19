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
  `public/assets/photos` — photography that ships with the application: the homepage hero,
  the About page banner, one per service, and the picture behind the social card. Each has a
  setting that replaces it (`home.hero_image`, `company.about_image`, a service's own image
  upload). `Service::bundledPhotoPath()` finds a service's photograph by slug.
  `public/assets/illustrations` — one generic drawing, shown by a service added after the
  application shipped until a picture is uploaded for it.
- `app/Http/Controllers` — public controllers at the root, tracking under `Tracking/`,
  admin under `Admin/`.
- `resources/views/public` and `resources/views/admin`, with shared Blade components in
  `resources/views/components`.

## Conventions

- Two staff roles, in `UserRole`: `Administrator` (master admin, `['*']`) and
  `Representative` (customer representative, chat only). Authorisation goes through gates
  named `area.action` (`shipments.manage`, `settings.manage`, …) resolved from
  `UserRole::permissions()`; model level rules live in `app/Policies`.
- Suspension is answered in one place: `User::hasPermission()` returns false when the
  account is paused (`suspended_at`) or its access period has run out
  (`access_expires_at` in the past), so a suspended account fails every gate at once and
  there is no screen left where it could still change a shipment or a tracking number.
  `EnsureUserIsStaff` is the second lock, sending it to `admin.suspended` and nowhere
  else; `User::scopeUsable()` is the matching query scope, used wherever staff are
  offered for selection. A suspended account can still sign in — it has to, to read the
  renewal notice — but a *deactivated* one (`is_active` false) is signed out instead.
- A master admin signs in with a password and then a six digit code emailed to the
  account. `TwoFactor::requiredFor()` decides (role plus the `security.two_factor`
  setting), `LoginCodeService` issues and verifies, `LoginCodeIssued` delivers. Email is
  the only channel. Only a hash of the code is stored; the code is never audited or
  logged, and `portlane:two-factor off` is the way back in when mail breaks.
- A representative may open a conversation only when it is assigned to them or unassigned;
  `ChatConversationPolicy` is the single place that decides this, and
  `ChatConversation::scopeForRepresentative()` is the matching query scope. Replying to an
  unassigned conversation claims it.
- The customer chat shows a first name, never a real member of staff. `AgentNames` holds
  fifty of them; `ChatService::assignAgentAlias()` stamps one on the conversation the first
  time somebody replies or is assigned, avoiding names in use on other live threads, and it
  never changes afterwards. Until it is set the customer sees "A live agent will join you
  shortly". The panel always shows the real account, so assignment and the audit trail are
  unaffected.
- Opening hours go through `App\Support\BusinessHours`. When the three day settings agree,
  the site prints one line ("Every day / 24 hours") instead of three identical rows, and
  `sentence()` is the phrase for the places that write it into prose. No view reads the
  three hour settings directly.
- The tracking page answers "where is my cargo" with a picture. `x-tracking-journey` draws a
  rail with the vehicle sitting at the point reached; `TrackingIcons::vehicleFor()` picks it
  (ship, aircraft, train, van) and draws a van for any shipment whose origin and destination
  country match, because that is the leg the customer can picture.
  `TrackingIcons::forStatus()` gives each status a drawing that carries its meaning, so a
  customs hold reads differently from a weather delay. All SVG, never emoji. The stage
  checklist and the raw percentage are operational detail, off for customers behind
  `tracking.show_stages` and `tracking.show_percentage`; staff always see both in the panel.
- The public site has no staff login link and no utility strip above the header: the branded
  header is the first thing on the page, and the homepage tab is the company name alone.
- The customer chat is a window, not a record. It accepts no files at all, and a conversation
  is deleted with its messages `portlane.chat.retention_hours` (24) after its last message —
  `portlane:purge-chat` hourly, plus `ChatService::sweepExpired()` while the chat is used.
  Past that window `hasExpired()` makes it unreadable to everyone, so a missed cron run never
  keeps a thread alive; `scopeWithinRetention()` is the matching query scope. Message bodies
  are never copied into notification emails or the audit log, and the audit descriptions
  reference the tracking number rather than the customer's name.
- Every administrative write is recorded through `AuditLogger`. Never log credentials:
  `changes()` records which fields changed, never their values, and `redact()` masks
  sensitive keys.
- Staff passwords go through `Password::defaults()`, set in `AppServiceProvider` to at least
  twelve characters, capped at bcrypt's 72 byte limit, and checked against known breaches in
  production. Laravel's own default is eight characters and would accept "password" for an
  account that can read every customer record.
- Customer facing text comes from the database: site settings, services, pages, FAQs and
  shipment records. Do not hard code company details in templates.
- Countries come from `App\Support\Countries` and are validated against it everywhere they
  are entered, so a quote and a shipment for the same lane read identically.
  `Countries::flag()` turns a name (or a common short form) into its flag; the homepage
  destination lanes use it through `x-country-name` / `x-country-list`. The flag is always
  shown beside the country name, never instead of it, because Windows draws flag emoji as the
  two letter code. This is the one place the public site uses emoji.
- `.env` is only ever edited from the console through `App\Support\EnvFile`, resolved from
  the container so a test can point it at a temporary file: running the suite must never
  rewrite the installation's own `.env`. Values are written single quoted, because the
  parser expands `${...}` inside double quotes and would silently store a password that is
  not the one typed; a value carrying a single quote is refused rather than written broken,
  and the file is copied first. `portlane:configure-mail` is the way mail credentials get
  set — an SMTP key typed at a prompt stays out of the shell's history, and a long block of
  shell pasted over SSH can arrive with characters missing.
- Notification email goes through `App\Services\Notifier`, never the `Notification` facade
  directly. The queue connection is `sync` on shared hosting, so a send happens inside the
  web request: an unreachable SMTP server would otherwise throw and show a customer a 500
  for a message that was already saved. `toAddress()` validates, catches, logs without the
  recipient's address, and returns whether it went. Customer facing forms ignore the result
  and carry on; a screen whose purpose was to send an email (a quotation) reports the
  failure to the member of staff; a shipment event only records `notified_customer` when
  the email actually went. The one exception is `LoginCodeService`, which must fail closed.
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
php artisan portlane:configure-mail   # outgoing email, and a test message to prove it
php artisan portlane:create-admin
php artisan portlane:two-factor off   # escape hatch if the sign-in code cannot be sent
php artisan test
npm run build
```
