<?php

namespace App\Console\Commands;

use App\Support\EnvFile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Throwable;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\password as promptPassword;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

/**
 * Sets up outgoing email without anybody typing credentials into a shell.
 *
 * An SMTP key pasted onto a command line is left behind in the shell history,
 * and a long block of shell pasted over SSH can arrive with characters
 * missing. This asks for the values one at a time, keeps the key off the
 * screen and out of the history, writes the file through EnvFile, and offers to
 * prove delivery before the person walks away believing it works.
 *
 * Email matters more here than on most sites: a master admin cannot sign in at
 * all without a code being delivered.
 */
class ConfigureMail extends Command
{
    protected $signature = 'portlane:configure-mail
                            {--test= : Send a test message to this address once the settings are saved}';

    protected $description = 'Set up the outgoing email account, and prove that it works';

    /**
     * Known providers, so the host and port do not have to be looked up.
     *
     * @var array<string, array{label: string, host: string, port: string, help: string}>
     */
    private const PRESETS = [
        'brevo' => [
            'label' => 'Brevo (free plan, 300 a day)',
            'host' => 'smtp-relay.brevo.com',
            'port' => '587',
            'help' => 'The password is the SMTP key from Brevo, under SMTP & API. It is NOT your Brevo account password.',
        ],
        'other' => [
            'label' => 'Another SMTP provider, or my host\'s mail server',
            'host' => '',
            'port' => '587',
            'help' => 'Your provider or hosting panel will give you the host, port, username and password.',
        ],
    ];

    public function handle(EnvFile $env): int
    {
        $this->components->info('Setting up outgoing email.');

        $choice = select(
            label: 'Who sends your email?',
            options: collect(self::PRESETS)->map(fn (array $p) => $p['label'])
                ->put('log', 'Nobody yet — write emails to the log file instead')
                ->all(),
            default: 'brevo',
        );

        if ($choice === 'log') {
            return $this->useLogDriver($env);
        }

        $preset = self::PRESETS[$choice];
        $this->components->warn($preset['help']);

        $host = text('SMTP host', default: $preset['host'], required: true);
        $port = text('Port', default: $preset['port'], required: true);
        $username = text('Username', default: (string) $env->get('MAIL_USERNAME'), required: true);
        $secret = promptPassword('Password or SMTP key (hidden as you type)', required: true);

        $from = text(
            label: 'Send from this address',
            default: (string) $env->get('MAIL_FROM_ADDRESS'),
            required: true,
            hint: 'Must be an address your provider has verified, or it will refuse the message.',
        );

        $operations = text(
            label: 'Where should quote requests and contact messages be announced?',
            default: $from,
            required: true,
        );

        foreach (['Send from' => $from, 'Operations' => $operations] as $label => $address) {
            if (filter_var($address, FILTER_VALIDATE_EMAIL) === false) {
                $this->components->error("{$label} is not a valid email address. Nothing was changed.");

                return self::FAILURE;
            }
        }

        try {
            $backup = $env->put([
                'MAIL_MAILER' => 'smtp',
                'MAIL_HOST' => trim($host),
                'MAIL_PORT' => trim($port),
                'MAIL_USERNAME' => trim($username),
                'MAIL_PASSWORD' => $secret,
                'MAIL_FROM_ADDRESS' => trim($from),
                'MAIL_ADMIN_ADDRESS' => trim($operations),
            ]);
        } catch (Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->refreshConfiguration();

        $this->components->info('Saved. The previous file is kept at '.basename($backup).'.');

        return $this->offerTest();
    }

    /**
     * No mail account yet. The log driver cannot fail, which keeps the public
     * forms working while the real account is being arranged.
     *
     * It writes at debug level, so a production LOG_LEVEL of `error` discards
     * every message silently — including the sign-in code, leaving somebody
     * searching a file that was never written to. Since the whole point of
     * choosing this is to be able to read the emails, the level is put right
     * at the same time.
     */
    private function useLogDriver(EnvFile $env): int
    {
        $values = ['MAIL_MAILER' => 'log'];
        $level = mb_strtolower((string) $env->get('LOG_LEVEL'));

        if ($level !== '' && $level !== 'debug') {
            $this->components->warn("LOG_LEVEL is '{$level}', which throws away messages written at debug level — which is every email the log driver writes.");

            if (confirm('Set LOG_LEVEL to debug so the emails are readable?', default: true)) {
                $values['LOG_LEVEL'] = 'debug';
            }
        }

        try {
            $env->put($values);
        } catch (Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->refreshConfiguration();

        $this->components->info('Emails will be written to '.$this->logFileHint().' instead of sent.');
        $this->components->warn('Sign-in codes go there too, so read the file when you sign in.');

        if (($values['LOG_LEVEL'] ?? $level) !== 'debug') {
            $this->components->error('LOG_LEVEL is still '.$level.', so the emails will be discarded and the sign-in code will not be readable anywhere. Use portlane:two-factor off to get in.');
        }

        return self::SUCCESS;
    }

    /**
     * Where to actually look. The daily driver dates the filename, and telling
     * somebody to read laravel.log when the file is laravel-2026-01-31.log
     * sends them hunting through an empty file.
     */
    private function logFileHint(): string
    {
        $channel = config('logging.default');
        $channel = $channel === 'stack'
            ? (config('logging.channels.stack.channels')[0] ?? 'single')
            : $channel;

        $path = config("logging.channels.{$channel}.path", storage_path('logs/laravel.log'));

        if (config("logging.channels.{$channel}.driver") === 'daily') {
            $path = preg_replace('/\.log$/', '-'.now()->format('Y-m-d').'.log', (string) $path);
        }

        return str_replace(base_path().'/', '', (string) $path);
    }

    private function offerTest(): int
    {
        $address = $this->option('test');

        if (! $address && ! confirm('Send a test message now?', default: true)) {
            $this->components->warn('Not tested. Run it again with --test=you@example.com before relying on it.');

            return self::SUCCESS;
        }

        $address ??= text('Send the test to', required: true);

        if (filter_var($address, FILTER_VALIDATE_EMAIL) === false) {
            $this->components->error('That is not a valid email address.');

            return self::FAILURE;
        }

        try {
            Mail::raw(
                'This is a test from '.company_name().". If you are reading it, outgoing email is working.\n",
                fn ($message) => $message->to($address)->subject('Test from '.company_name()),
            );
        } catch (Throwable $exception) {
            $this->components->error('The message was refused: '.$exception->getMessage());
            $this->components->warn('"Authentication failed" usually means the account password was used instead of the SMTP key.');
            $this->components->warn('"Sender not allowed" means the send-from address has not been verified with your provider.');

            return self::FAILURE;
        }

        $this->components->info("Sent to {$address}. Check the inbox, and the spam folder.");

        return self::SUCCESS;
    }

    /**
     * A cached configuration would otherwise keep serving the old settings.
     */
    private function refreshConfiguration(): void
    {
        Artisan::call('config:clear');
    }
}
