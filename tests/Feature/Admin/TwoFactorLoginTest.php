<?php

namespace Tests\Feature\Admin;

use App\Models\AuditLog;
use App\Models\User;
use App\Notifications\LoginCodeIssued;
use App\Services\LoginCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * The one time code a master admin has to enter after their password.
 *
 * A stolen password is the way an account like this is actually lost, so the
 * things worth proving are that the password alone gets nobody in, that the
 * code cannot be guessed at leisure, and that the code itself is nowhere on
 * the system in a form anyone could read.
 */
class TwoFactorLoginTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'correct-horse-battery';

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
    }

    private function admin(array $attributes = []): User
    {
        return $this->administrator(['password' => Hash::make(self::PASSWORD)] + $attributes);
    }

    private function signInWithPassword(User $user): void
    {
        $this->post(route('admin.login.store'), [
            'email' => $user->email,
            'password' => self::PASSWORD,
        ])->assertRedirect(route('admin.login.challenge'));
    }

    /** The code that was emailed, read back off the notification. */
    private function codeSentTo(User $user): string
    {
        $code = null;

        Notification::assertSentTo($user, LoginCodeIssued::class, function (LoginCodeIssued $notification) use (&$code) {
            $code = $notification->code;

            return true;
        });

        $this->assertNotNull($code);

        return $code;
    }

    public function test_the_password_alone_does_not_sign_a_master_admin_in(): void
    {
        $user = $this->admin();

        $this->signInWithPassword($user);

        $this->assertGuest();
        Notification::assertSentTo($user, LoginCodeIssued::class);
        $this->assertNotNull($user->fresh()->login_code_hash);
    }

    public function test_the_emailed_code_completes_the_sign_in(): void
    {
        $user = $this->admin();

        $this->signInWithPassword($user);

        $this->post(route('admin.login.challenge.store'), ['code' => $this->codeSentTo($user)])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->last_login_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'auth.login', 'user_id' => $user->id]);
    }

    public function test_the_code_is_used_up_once_it_has_worked(): void
    {
        $user = $this->admin();

        $this->signInWithPassword($user);
        $code = $this->codeSentTo($user);

        $this->post(route('admin.login.challenge.store'), ['code' => $code]);
        $this->post(route('admin.logout'));

        // The same code offered a second time is worth nothing.
        $this->signInWithPassword($user);
        $this->post(route('admin.login.challenge.store'), ['code' => $code])
            ->assertSessionHasErrors('code');

        $this->assertGuest();
    }

    public function test_only_a_hash_of_the_code_is_stored(): void
    {
        $user = $this->admin();

        $this->signInWithPassword($user);
        $code = $this->codeSentTo($user);

        $stored = (string) $user->fresh()->login_code_hash;

        $this->assertNotSame($code, $stored);
        $this->assertStringNotContainsString($code, $stored);
        $this->assertTrue(Hash::check($code, $stored));
    }

    public function test_the_code_never_reaches_the_audit_log(): void
    {
        $user = $this->admin();

        $this->signInWithPassword($user);
        $code = $this->codeSentTo($user);

        $this->post(route('admin.login.challenge.store'), ['code' => $code]);

        foreach (AuditLog::all() as $log) {
            $this->assertStringNotContainsString($code, json_encode($log->properties) ?: '');
            $this->assertStringNotContainsString($code, (string) $log->description);
        }

        $this->assertDatabaseHas('audit_logs', ['action' => 'auth.code_sent', 'user_id' => $user->id]);
    }

    public function test_a_wrong_code_keeps_the_account_out(): void
    {
        $user = $this->admin();

        $this->signInWithPassword($user);

        $this->post(route('admin.login.challenge.store'), ['code' => '000000'])
            ->assertSessionHasErrors('code');

        $this->assertGuest();
        $this->assertSame(1, $user->fresh()->login_code_attempts);
    }

    public function test_the_code_is_thrown_away_after_too_many_guesses(): void
    {
        $user = $this->admin();
        $attempts = (int) config('portlane.security.code_attempts');

        $this->signInWithPassword($user);
        $real = $this->codeSentTo($user);

        for ($guess = 0; $guess < $attempts - 1; $guess++) {
            $this->post(route('admin.login.challenge.store'), ['code' => '000000'])
                ->assertSessionHasErrors('code');
        }

        // The last allowed guess sends them back to the password form.
        $this->post(route('admin.login.challenge.store'), ['code' => '000000'])
            ->assertRedirect(route('admin.login'));

        $this->assertNull($user->fresh()->login_code_hash);

        // And the code that was actually right is gone with it.
        $this->post(route('admin.login.challenge.store'), ['code' => $real])
            ->assertRedirect(route('admin.login'));

        $this->assertGuest();
    }

    public function test_an_expired_code_is_refused(): void
    {
        $user = $this->admin();

        $this->signInWithPassword($user);
        $code = $this->codeSentTo($user);

        $this->travel((int) config('portlane.security.code_ttl') + 1)->minutes();

        $this->post(route('admin.login.challenge.store'), ['code' => $code])
            ->assertRedirect(route('admin.login'));

        $this->assertGuest();
    }

    public function test_the_half_finished_sign_in_expires_on_its_own(): void
    {
        $user = $this->admin();

        $this->signInWithPassword($user);
        $code = $this->codeSentTo($user);

        $this->travel((int) config('portlane.security.challenge_ttl') + 1)->minutes();

        $this->get(route('admin.login.challenge'))->assertRedirect(route('admin.login'));
        $this->post(route('admin.login.challenge.store'), ['code' => $code])
            ->assertRedirect(route('admin.login'));

        $this->assertGuest();
    }

    public function test_the_code_screen_renders_for_a_pending_sign_in(): void
    {
        $user = $this->admin();

        $this->signInWithPassword($user);

        $this->get(route('admin.login.challenge'))
            ->assertOk()
            ->assertSee('Enter your sign-in code')
            ->assertSee($user->email)
            // The code itself is in the email, never on the screen.
            ->assertDontSee($this->codeSentTo($user));
    }

    /**
     * The warning lives in the email and nowhere else. On the code screen it
     * would be addressed to whoever typed the password half a minute earlier,
     * which tells them nothing; in an inbox an unexpected code is the whole
     * signal that a password has been taken.
     */
    public function test_the_unexpected_code_warning_is_in_the_email_not_on_the_screen(): void
    {
        $user = $this->admin();

        $this->signInWithPassword($user);

        Notification::assertSentTo($user, LoginCodeIssued::class, function (LoginCodeIssued $notification) use ($user) {
            $body = collect($notification->toMail($user)->introLines)
                ->merge($notification->toMail($user)->outroLines)
                ->implode(' ');

            $this->assertStringContainsString('did not just try to sign in', $body);

            return true;
        });

        $this->get(route('admin.login.challenge'))
            ->assertOk()
            ->assertDontSee('Somebody has your password');
    }

    public function test_the_code_screen_is_useless_without_a_pending_sign_in(): void
    {
        $this->get(route('admin.login.challenge'))->assertRedirect(route('admin.login'));

        $this->post(route('admin.login.challenge.store'), ['code' => '123456'])
            ->assertRedirect(route('admin.login'));

        $this->assertGuest();
    }

    public function test_another_code_cannot_be_demanded_straight_away(): void
    {
        $user = $this->admin();

        $this->signInWithPassword($user);

        $this->post(route('admin.login.challenge.resend'))->assertSessionHasErrors('code');

        $this->travel((int) config('portlane.security.code_resend') + 1)->seconds();

        $this->post(route('admin.login.challenge.resend'))->assertSessionHas('status');
        Notification::assertSentToTimes($user, LoginCodeIssued::class, 2);
    }

    public function test_a_representative_signs_in_with_a_password_alone(): void
    {
        $rep = $this->representative(['password' => Hash::make(self::PASSWORD)]);

        $this->post(route('admin.login.store'), [
            'email' => $rep->email,
            'password' => self::PASSWORD,
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($rep);
        Notification::assertNothingSentTo($rep);
    }

    public function test_the_code_can_be_turned_off_from_the_settings(): void
    {
        $this->withoutTwoFactor();
        $user = $this->admin();

        $this->post(route('admin.login.store'), [
            'email' => $user->email,
            'password' => self::PASSWORD,
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($user);
        Notification::assertNothingSentTo($user);
    }

    public function test_the_console_command_is_the_way_back_in(): void
    {
        $user = $this->admin();

        $this->artisan('portlane:two-factor off')->assertSuccessful();

        $this->post(route('admin.login.store'), [
            'email' => $user->email,
            'password' => self::PASSWORD,
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($user);

        $this->artisan('portlane:two-factor on')->assertSuccessful();
        $this->artisan('portlane:two-factor sideways')->assertFailed();
    }

    public function test_a_disabled_account_never_reaches_the_code_step(): void
    {
        $user = User::factory()->administrator()->inactive()->create(['password' => Hash::make(self::PASSWORD)]);

        $this->post(route('admin.login.store'), [
            'email' => $user->email,
            'password' => self::PASSWORD,
        ])->assertSessionHasErrors('email');

        Notification::assertNothingSentTo($user);
        $this->assertGuest();
    }

    public function test_the_wrong_password_never_sends_a_code(): void
    {
        $user = $this->admin();

        $this->post(route('admin.login.store'), [
            'email' => $user->email,
            'password' => 'not-the-password',
        ])->assertSessionHasErrors('email');

        Notification::assertNothingSentTo($user);
        $this->assertNull($user->fresh()->login_code_hash);
    }

    public function test_a_code_issued_for_one_account_does_not_open_another(): void
    {
        $target = $this->admin();
        $other = $this->admin();

        // A code is issued to the other account out of band.
        $otherCode = app(LoginCodeService::class)->send($other);

        $this->signInWithPassword($target);

        $this->post(route('admin.login.challenge.store'), ['code' => $otherCode])
            ->assertSessionHasErrors('code');

        $this->assertGuest();
    }
}
