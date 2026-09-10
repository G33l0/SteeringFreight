<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks the admin area for anyone who is not a signed in, active member of
 * staff.
 *
 * A deactivated account is signed out. A suspended one — paused by hand, or
 * past the date its access was granted until — is not: it is sent to the notice
 * telling the person who to contact, and is allowed nowhere else. Suspension
 * also fails every gate through User::hasPermission(), so this redirect is the
 * second lock rather than the only one.
 */
class EnsureUserIsStaff
{
    /**
     * The only routes a suspended account may reach.
     *
     * @var list<string>
     */
    private const SUSPENDED_ROUTES = ['admin.suspended', 'admin.logout'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->guest(route('admin.login'));
        }

        if (! $user->is_active) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('admin.login')
                ->withErrors(['email' => 'This account has been deactivated.']);
        }

        if ($user->isSuspended() && ! $request->routeIs(self::SUSPENDED_ROUTES)) {
            return redirect()->route('admin.suspended');
        }

        return $next($request);
    }
}
