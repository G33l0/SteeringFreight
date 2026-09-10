<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\LoginCodeService;
use App\Support\TwoFactor;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class SessionController extends Controller
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly LoginCodeService $codes,
    ) {}

    public function create(): View
    {
        return view('admin.auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email:filter', 'max:180'],
            'password' => ['required', 'string'],
        ]);

        $user = $this->findByCredentials($credentials);

        // One message for a wrong address, a wrong password and a disabled
        // account: which of the three it was is not the visitor's business.
        if (! $user) {
            throw ValidationException::withMessages([
                'email' => 'Those details do not match our records.',
            ]);
        }

        // A suspended account is let through to the notice screen rather than
        // turned away here, so the person can read why and who to speak to.
        if (! TwoFactor::requiredFor($user)) {
            return $this->completeLogin($request, $user, $request->boolean('remember'));
        }

        return $this->beginChallenge($request, $user, $request->boolean('remember'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user) {
            $this->audit->record('auth.logout', $user, "{$user->name} signed out", [], $user);
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')->with('status', 'You have been signed out.');
    }

    /**
     * The account matching an email and password, or null. Deactivated
     * accounts never match, so they cannot reach the code step either.
     *
     * @param  array{email: string, password: string}  $credentials
     */
    private function findByCredentials(array $credentials): ?User
    {
        $provider = Auth::guard('web')->getProvider();

        /** @var User|null $user */
        $user = $provider->retrieveByCredentials($credentials);

        if (! $user || ! $user->is_active) {
            return null;
        }

        return $provider->validateCredentials($user, $credentials) ? $user : null;
    }

    /**
     * Password accepted, code still to come. Nothing is signed in yet: the
     * pending sign in lives in the session and expires on its own.
     */
    private function beginChallenge(Request $request, User $user, bool $remember): RedirectResponse
    {
        try {
            $this->codes->send($user);
        } catch (Throwable $exception) {
            // Fail closed. Letting somebody in because the mail server is down
            // would mean the code is only ever asked for when it is convenient.
            Log::error('Could not send an admin sign-in code.', ['exception' => $exception]);

            $this->audit->record('auth.code_failed', $user, "Could not send a sign-in code to {$user->name}", [], $user);

            throw ValidationException::withMessages([
                'email' => 'We could not send your sign-in code. Try again in a moment, and ask whoever administers this site to check the email settings.',
            ]);
        }

        // A new session id before the pending sign in is written to it, so a
        // session id planted on the visitor beforehand cannot be the one that
        // ends up holding a half finished sign in.
        $request->session()->regenerate();

        $request->session()->put(TwoFactor::SESSION_KEY, [
            'id' => $user->getKey(),
            'remember' => $remember,
            'expires_at' => now()->addMinutes((int) config('portlane.security.challenge_ttl'))->getTimestamp(),
        ]);

        $this->audit->record('auth.code_sent', $user, "Sent a sign-in code to {$user->name}", [], $user);

        return redirect()->route('admin.login.challenge');
    }

    /**
     * Shared by the password step and the code step, so a sign in is recorded
     * the same way whichever route it came in through.
     */
    public function completeLogin(Request $request, User $user, bool $remember, bool $withCode = false): RedirectResponse
    {
        $request->session()->forget(TwoFactor::SESSION_KEY);

        Auth::guard('web')->login($user, $remember);
        $request->session()->regenerate();

        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->save();

        $this->audit->record(
            'auth.login',
            $user,
            "{$user->name} signed in",
            $withCode ? ['two_factor' => true] : [],
            $user,
        );

        if ($user->isSuspended()) {
            return redirect()->route('admin.suspended');
        }

        return redirect()->intended(route('admin.dashboard'));
    }
}
