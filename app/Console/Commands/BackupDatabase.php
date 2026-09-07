<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Takes a consistent copy of a SQLite database while the site is running.
 *
 * On MySQL the hosting control panel does this for you. On SQLite the database
 * is a file you are responsible for, and copying it with cp while a write is in
 * flight can produce a corrupt copy, so this uses SQLite's own VACUUM INTO,
 * which writes a complete and consistent database no matter what else is
 * happening at the time.
 */
class BackupDatabase extends Command
{
    protected $signature = 'portlane:backup-database
                            {--path= : Directory to write the backup into (default database/backups)}
                            {--keep=14 : How many backups to keep; older ones are deleted}';

    protected $description = 'Write a consistent copy of the SQLite database, and prune old copies';

    public function handle(): int
    {
        $connection = DB::connection();

        if ($connection->getDriverName() !== 'sqlite') {
            $this->components->error('This command backs up SQLite only. On MySQL use your hosting panel\'s backup tool or mysqldump.');

            return self::FAILURE;
        }

        $source = $connection->getConfig('database');

        if ($source === ':memory:') {
            $this->components->error('There is nothing to back up: this connection is an in memory database.');

            return self::FAILURE;
        }

        $directory = $this->option('path') ?: database_path('backups');
        File::ensureDirectoryExists($directory);

        $target = rtrim($directory, '/').'/portlane-'.now()->format('Y-m-d-His').'.sqlite';

        // VACUUM INTO refuses to overwrite an existing file, which is what we
        // want; two runs inside the same second would otherwise collide.
        if (File::exists($target)) {
            $this->components->error('A backup for this second already exists: '.$target);

            return self::FAILURE;
        }

        $connection->statement('vacuum into ?', [$target]);
        @chmod($target, 0600);

        $this->components->info('Backup written to '.$target.' ('.$this->size($target).').');

        $this->prune($directory, max(1, (int) $this->option('keep')));

        return self::SUCCESS;
    }

    private function prune(string $directory, int $keep): void
    {
        $backups = collect(File::files($directory))
            ->filter(fn ($file) => str_ends_with($file->getFilename(), '.sqlite'))
            ->sortByDesc(fn ($file) => $file->getFilename())
            ->values();

        $stale = $backups->slice($keep);

        foreach ($stale as $file) {
            File::delete($file->getPathname());
        }

        if ($stale->isNotEmpty()) {
            $this->components->info('Removed '.$stale->count().' backup(s) older than the last '.$keep.'.');
        }
    }

    private function size(string $path): string
    {
        $bytes = (int) File::size($path);

        return $bytes > 1048576
            ? round($bytes / 1048576, 1).' MB'
            : max(1, (int) round($bytes / 1024)).' KB';
    }
}
