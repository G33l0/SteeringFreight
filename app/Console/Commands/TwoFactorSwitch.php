<?php

namespace App\Console\Commands;

use App\Support\Settings;
use Illuminate\Console\Command;

/**
 * The way back in when the sign in code cannot be delivered.
 *
 * Two factor is a site setting, so it is normally turned on and off in the
 * panel — but if the mail server stops working, nobody can reach the panel to
 * do it. This does the same thing from the server's own shell, which is a
 * place only somebody with the deployment already has.
 */
class TwoFactorSwitch extends Command
{
    protected $signature = 'portlane:two-factor {state? : on, off, or leave empty to see the current setting}';

    protected $description = 'Turn the sign-in code for master admin accounts on or off';

    public function handle(Settings $settings): int
    {
        $state = mb_strtolower(trim((string) $this->argument('state')));

        if ($state === '') {
            $this->components->info('Sign-in codes are currently '.($settings->bool('security.two_factor', true) ? 'ON' : 'OFF').'.');

            return self::SUCCESS;
        }

        if (! in_array($state, ['on', 'off'], true)) {
            $this->components->error('Say "on" or "off".');

            return self::FAILURE;
        }

        $settings->set('security.two_factor', $state === 'on');

        $this->components->info($state === 'on'
            ? 'Master admins will be asked for an emailed code from the next sign in.'
            : 'Sign-in codes are off. Turn them back on as soon as email is working again: a password on its own is all that protects every customer record on this system.');

        return self::SUCCESS;
    }
}
