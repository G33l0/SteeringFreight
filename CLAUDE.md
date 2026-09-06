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
  (safe rendering of administrator written copy; never renders raw HTML).
- `app/Http/Controllers` — public controllers at the root, tracking under `Tracking/`,
  admin under `Admin/`.
- `resources/views/public` and `resources/views/admin`, with shared Blade components in
  `resources/views/components`.

## Conventions

- Authorisation goes through gates named `area.action` (`shipments.manage`,
  `settings.manage`, …), resolved from `UserRole::permissions()`. Model level rules live
  in `app/Policies`.
- Every administrative write is recorded through `AuditLogger`. Never log credentials.
- Customer facing text comes from the database: site settings, services, pages, FAQs and
  shipment records. Do not hard code company details in templates.
- Private files (shipment documents, chat attachments) live on the `local` disk and are
  only ever streamed by a controller that checks authorisation.
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
