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
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * The second step of a master admin sign in: the code that was emailed when
 * the password was accepted.
 *
 * Nobody is signed in while this screen is on show. The pending sign in is a
 * user id and a remember flag in the session, it expires on its own, and it is
 * thrown away when the code runs out of guesses — at which point the password
 * has to be entered again.
 */
class TwoFactorChallengeController extends Controller
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly LoginCodeService $codes,
        private readonly SessionController $sessions,
    ) {}

    public function create(Request $request): View|RedirectResponse
    {
        $user = $this->pendingUser($request);

        if (! $user) {
            return $this->restart($request, 'Your sign-in timed out. Enter your details again.');
        }

        return view('admin.auth.challenge', [
            'email' => $user->email,
            'resendAvailableAt' => $this->codes->resendAvailableAt($user),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $this->pendingUser($request);

        if (! $user) {
            return $this->restart($request, 'Your sign-in timed out. Enter your details again.');
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'digits:6'],
        ]);

        if ($this->codes->verify($user, $validated['code'])) {
            return $this->sessions->completeLogin($request, $user, $this->pendingRemember($request), withCode: true);
        }

        // Out of guesses, or the code expired while the form was open: the
        // pending sign in goes with it.
        if (! $this->codes->hasPendingCode($user)) {
            $this->audit->record('auth.code_failed', $user, "Sign-in code for {$user->name} was refused", [], $user);

            return $this->restart($request, 'That code is no longer valid. Enter your details again to be sent a new one.');
        }

        throw ValidationException::withMessages([
            'code' => 'That code is not right. '.trans_choice(
                '{1} You have one more attempt.|[2,*] You have :count attempts left.',
                $this->codes->attemptsLeft($user),
            ),
        ]);
    }

    public function resend(Request $request): RedirectResponse
    {
        $user = $this->pendingUser($request);

        if (! $user) {
            return $this->restart($request, 'Your sign-in timed out. Enter your details again.');
        }

        if (! $this->codes->canResend($user)) {
            return back()->withErrors([
                'code' => 'A code was just sent. Wait a moment before asking for another.',
            ]);
        }

        try {
            $this->codes->send($user);
        } catch (Throwable $exception) {
            Log::error('Could not resend an admin sign-in code.', ['exception' => $exception]);

            return back()->withErrors(['code' => 'We could not send the code. Try again in a moment.']);
        }

        return back()->with('status', 'A new code is on its way to '.$this->maskEmail($user->email).'.');
    }

    /**
     * The account part way through signing in, or null when there is no
     * pending sign in, it has expired, or the account has been disabled since
     * the password was accepted.
     */
    private function pendingUser(Request $request): ?User
    {
        $pending = $request->session()->get(TwoFactor::SESSION_KEY);

        if (! is_array($pending) || ! isset($pending['id'], $pending['expires_at'])) {
            return null;
        }

        if (now()->getTimestamp() > (int) $pending['expires_at']) {
            return null;
        }

        $user = User::find($pending['id']);

        return $user && $user->is_active ? $user : null;
    }

    private function pendingRemember(Request $request): bool
    {
        return (bool) ($request->session()->get(TwoFactor::SESSION_KEY)['remember'] ?? false);
    }

    private function restart(Request $request, string $message): RedirectResponse
    {
        $request->session()->forget(TwoFactor::SESSION_KEY);

        return redirect()->route('admin.login')->withErrors(['email' => $message]);
    }

    /**
     * Enough of the address to recognise it, not enough to learn it from the
     * screen of somebody who has only guessed a password.
     */
    private function maskEmail(string $email): string
    {
        [$name, $domain] = array_pad(explode('@', $email, 2), 2, '');

        return mb_substr($name, 0, 1).str_repeat('*', max(1, mb_strlen($name) - 1)).'@'.$domain;
    }
}
