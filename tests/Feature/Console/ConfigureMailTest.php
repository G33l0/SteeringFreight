<?php

namespace Tests\Feature\Console;

use App\Support\EnvFile;
use Dotenv\Dotenv;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

/**
 * Writing mail credentials into .env.
 *
 * The cases here are the ones that bite in the field: an SMTP key containing
 * the characters a shell or an env parser treats as special, and a key that
 * would be silently mangled rather than loudly refused. A password that is
 * quietly not the one that was typed produces "authentication failed" with no
 * clue why, which is the worst kind of bug to hand somebody setting up a
 * server.
 */
class ConfigureMailTest extends TestCase
{
    use RefreshDatabase;

    private string $dir;

    private string $path;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dir = sys_get_temp_dir().'/portlane-env-'.uniqid();
        mkdir($this->dir);
        $this->path = $this->dir.'/.env';

        $this->app->bind(EnvFile::class, fn () => new EnvFile($this->path));

        file_put_contents($this->path, implode("\n", [
            'APP_NAME="Portlane Shipping"',
            'MAIL_MAILER=log',
            'MAIL_HOST=127.0.0.1',
            'MAIL_USERNAME=',
            'MAIL_PASSWORD=',
            'MAIL_FROM_NAME="${APP_NAME}"',
        ])."\n");
    }

    protected function tearDown(): void
    {
        foreach (glob($this->dir.'/.env*') ?: [] as $file) {
            unlink($file);
        }
        @rmdir($this->dir);

        parent::tearDown();
    }

    /** What the env parser actually makes of the file we wrote. */
    private function parsed(): array
    {
        return Dotenv::createArrayBacked($this->dir)->load();
    }

    public function test_it_replaces_existing_lines_and_appends_missing_ones(): void
    {
        (new EnvFile($this->path))->put([
            'MAIL_MAILER' => 'smtp',
            'MAIL_HOST' => 'smtp-relay.brevo.com',
            'MAIL_ADMIN_ADDRESS' => 'operations@example.test',
        ]);

        $values = $this->parsed();

        $this->assertSame('smtp', $values['MAIL_MAILER']);
        $this->assertSame('smtp-relay.brevo.com', $values['MAIL_HOST']);
        $this->assertSame('operations@example.test', $values['MAIL_ADMIN_ADDRESS']);

        // One line per key, not a second MAIL_HOST appended below the first.
        $this->assertSame(1, substr_count((string) file_get_contents($this->path), 'MAIL_HOST='));
    }

    /**
     * The one that matters. Written between double quotes, a value containing
     * ${...} has it expanded away by the parser, so the stored password is not
     * the one that was typed.
     */
    public function test_a_key_containing_a_brace_expression_survives_intact(): void
    {
        $key = 'xsmtpsib-${APP_NAME}-abc123';

        (new EnvFile($this->path))->put(['MAIL_PASSWORD' => $key]);

        $this->assertSame($key, $this->parsed()['MAIL_PASSWORD']);
        $this->assertStringNotContainsString('Portlane Shipping', $this->parsed()['MAIL_PASSWORD']);
    }

    public function test_keys_containing_shell_and_regex_characters_survive_intact(): void
    {
        foreach ([
            'xsmtpsib-a$b-c',
            'key/with/slashes',
            'key\\with\\backslashes',
            'key"with"doublequotes',
            'key|with|pipes&and&ampersands',
            'key.with.$0.backreference',
        ] as $key) {
            file_put_contents($this->path, "MAIL_PASSWORD=\n");
            (new EnvFile($this->path))->put(['MAIL_PASSWORD' => $key]);

            $this->assertSame($key, $this->parsed()['MAIL_PASSWORD'], "mangled: {$key}");
        }
    }

    public function test_a_value_it_cannot_store_safely_is_refused_and_nothing_changes(): void
    {
        $before = file_get_contents($this->path);

        try {
            (new EnvFile($this->path))->put(['MAIL_PASSWORD' => "key'with'singlequotes"]);
            $this->fail('a value that cannot be stored should be refused');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('single quote', $exception->getMessage());
        }

        $this->assertSame($before, file_get_contents($this->path), 'the file must be untouched');
    }

    public function test_the_previous_file_is_kept(): void
    {
        $before = file_get_contents($this->path);

        $backup = (new EnvFile($this->path))->put(['MAIL_HOST' => 'smtp.example.test']);

        $this->assertFileExists($backup);
        $this->assertSame($before, file_get_contents($backup));
    }

    public function test_untouched_lines_are_left_exactly_as_they_were(): void
    {
        (new EnvFile($this->path))->put(['MAIL_HOST' => 'smtp.example.test']);

        // MAIL_FROM_NAME relies on ${APP_NAME} expanding, and must keep doing so.
        $this->assertStringContainsString('MAIL_FROM_NAME="${APP_NAME}"', (string) file_get_contents($this->path));
        $this->assertSame('Portlane Shipping', $this->parsed()['MAIL_FROM_NAME']);
    }

    public function test_it_reads_a_current_value_back(): void
    {
        $env = new EnvFile($this->path);

        $this->assertSame('127.0.0.1', $env->get('MAIL_HOST'));
        $this->assertNull($env->get('MAIL_USERNAME'), 'an empty value reads as nothing set');
        $this->assertNull($env->get('NOT_PRESENT_AT_ALL'));
    }

    public function test_the_command_can_fall_back_to_the_log_driver(): void
    {
        $this->artisan('portlane:configure-mail')
            ->expectsQuestion('Who sends your email?', 'log')
            ->assertSuccessful();

        $this->assertSame('log', $this->parsed()['MAIL_MAILER']);
    }

    /**
     * The suite must never touch the .env of the installation it runs on. A
     * server owner running `php artisan test` should not lose their mail
     * settings to it.
     */
    public function test_the_command_writes_where_it_is_pointed_and_nowhere_else(): void
    {
        $realEnv = base_path('.env');
        $before = is_file($realEnv) ? file_get_contents($realEnv) : null;

        $this->artisan('portlane:configure-mail')
            ->expectsQuestion('Who sends your email?', 'log')
            ->assertSuccessful();

        if ($before !== null) {
            $this->assertSame($before, file_get_contents($realEnv), 'the real .env was modified by a test');
        }

        $this->assertSame([], glob(base_path('.env.backup.*')) ?: []);
    }
}
