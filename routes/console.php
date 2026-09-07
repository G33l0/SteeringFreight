<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Scheduled tasks
|--------------------------------------------------------------------------
|
| These run from a single cron entry:
|   * * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
|
*/

// Clear the customer chat. Conversations are deleted once they are past the
// retention window in config/portlane.php, which is 24 hours by default.
Schedule::command('portlane:purge-chat')->hourly();

// Nightly database backup, for installs running on SQLite where the database is
// a file nobody else is looking after. On MySQL the hosting panel does this.
if (config('database.default') === 'sqlite') {
    Schedule::command('portlane:backup-database')->dailyAt('02:30');
}

// Remove expired password reset tokens.
Schedule::command('auth:clear-resets')->daily();

// Tidy the queue tables when the database queue driver is in use.
Schedule::command('queue:prune-failed --hours=168')->weekly();
Schedule::command('queue:prune-batches --hours=168')->weekly();
