<?php

namespace Tests\Feature\Admin;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_is_reachable(): void
    {
        $this->get(route('admin.login'))->assertOk()->assertSee('Sign in');
    }

    /**
     * The emailed code has a file of its own; this is the password step with
     * the code switched off, which is how a site that has not set up email yet
     * signs in.
     */
    public function test_administrator_can_sign_in(): void
    {
        $this->withoutTwoFactor();

        $user = $this->administrator(['password' => Hash::make('correct-horse-battery')]);

        $response = $this->post(route('admin.login.store'), [
            'email' => $user->email,
            'password' => 'correct-horse-battery',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->last_login_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'auth.login', 'user_id' => $user->id]);
    }

    public function test_a_master_admin_is_asked_for_a_code_before_the_panel_opens(): void
    {
        $user = $this->administrator(['password' => Hash::make('correct-horse-battery')]);

        $this->post(route('admin.login.store'), [
            'email' => $user->email,
            'password' => 'correct-horse-battery',
        ])->assertRedirect(route('admin.login.challenge'));

        $this->assertGuest();
    }

    public function test_sign_in_fails_with_the_wrong_password(): void
    {
        $user = $this->administrator(['password' => Hash::make('correct-horse-battery')]);

        $this->post(route('admin.login.store'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_deactivated_accounts_cannot_sign_in(): void
    {
        $user = User::factory()->administrator()->inactive()->create(['password' => Hash::make('secret-password')]);

        $this->post(route('admin.login.store'), [
            'email' => $user->email,
            'password' => 'secret-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_an_account_deactivated_mid_session_is_signed_out(): void
    {
        $user = $this->administrator();

        $this->actingAs($user);
        $user->forceFill(['is_active' => false])->save();

        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
        $this->assertGuest();
    }

    public function test_repeated_failed_attempts_are_rate_limited(): void
    {
        $user = $this->administrator(['password' => Hash::make('correct-horse-battery')]);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('admin.login.store'), ['email' => $user->email, 'password' => 'nope']);
        }

        $this->post(route('admin.login.store'), ['email' => $user->email, 'password' => 'nope'])
            ->assertStatus(429);
    }

    public function test_guests_are_redirected_away_from_the_admin_panel(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
        $this->get(route('admin.shipments.index'))->assertRedirect(route('admin.login'));
    }

    public function test_signing_out_is_recorded(): void
    {
        $user = $this->administrator();

        $this->actingAs($user)->post(route('admin.logout'))->assertRedirect(route('admin.login'));

        $this->assertGuest();
        $this->assertTrue(AuditLog::where('action', 'auth.logout')->where('user_id', $user->id)->exists());
    }

    public function test_password_reset_link_request_does_not_disclose_accounts(): void
    {
        $this->post(route('admin.password.email'), ['email' => 'nobody@example.com'])
            ->assertSessionHas('status');
    }
}
