<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\LoginCodeIssued;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * The one time codes that stand between a stolen master admin password and the
 * panel.
 *
 * The code itself is never stored, logged or audited: only a hash of it, the
 * moment it expires and how many times it has been guessed. Verifying a code
 * consumes it, so a code that has been used, has expired, or has been guessed
 * at too often is gone and a fresh one has to be sent.
 *
 * The code is emailed, and only emailed.
 */
class LoginCodeService
{
    /**
     * Issues a fresh code and sends it. Returns the plain code, which the
     * caller must not store: it exists only long enough to reach the message.
     */
    public function send(User $user): string
    {
        $code = $this->generate();

        $user->forceFill([
            'login_code_hash' => Hash::make($code),
            'login_code_expires_at' => now()->addMinutes($this->ttl()),
            'login_code_sent_at' => now(),
            'login_code_attempts' => 0,
        ])->save();

        $user->notify(new LoginCodeIssued($code, $this->ttl()));

        return $code;
    }

    /**
     * True when the code is right, and the code is used up either way: a wrong
     * guess counts against the account and the last allowed guess clears it.
     */
    public function verify(User $user, string $code): bool
    {
        if (! $this->hasPendingCode($user)) {
            return false;
        }

        if (Hash::check(trim($code), (string) $user->login_code_hash)) {
            $this->clear($user);

            return true;
        }

        $attempts = $user->login_code_attempts + 1;

        if ($attempts >= $this->maxAttempts()) {
            $this->clear($user);

            return false;
        }

        $user->forceFill(['login_code_attempts' => $attempts])->save();

        return false;
    }

    public function hasPendingCode(User $user): bool
    {
        return $user->login_code_hash !== null
            && $user->login_code_expires_at !== null
            && $user->login_code_expires_at->isFuture();
    }

    /**
     * How many guesses are left, so the sign in screen can say so rather than
     * silently sending the visitor back to the password form.
     */
    public function attemptsLeft(User $user): int
    {
        return max(0, $this->maxAttempts() - $user->login_code_attempts);
    }

    /**
     * When another code may be sent. Null when one can be sent right now.
     */
    public function resendAvailableAt(User $user): ?Carbon
    {
        if ($user->login_code_sent_at === null) {
            return null;
        }

        $available = $user->login_code_sent_at->copy()->addSeconds($this->resendDelay());

        return $available->isFuture() ? $available : null;
    }

    public function canResend(User $user): bool
    {
        return $this->resendAvailableAt($user) === null;
    }

    public function clear(User $user): void
    {
        $user->forceFill([
            'login_code_hash' => null,
            'login_code_expires_at' => null,
            'login_code_sent_at' => null,
            'login_code_attempts' => 0,
        ])->save();
    }

    public function ttl(): int
    {
        return (int) config('portlane.security.code_ttl');
    }

    public function maxAttempts(): int
    {
        return (int) config('portlane.security.code_attempts');
    }

    public function resendDelay(): int
    {
        return (int) config('portlane.security.code_resend');
    }

    /**
     * Six digits from the cryptographic generator, keeping the leading zero a
     * plain integer would drop.
     */
    private function generate(): string
    {
        return Str::padLeft((string) random_int(0, 999999), 6, '0');
    }
}
