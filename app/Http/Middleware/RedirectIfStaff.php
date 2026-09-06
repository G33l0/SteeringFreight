<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps signed in staff away from the login and password reset screens.
 */
class RedirectIfStaff
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()) {
            return redirect()->route('admin.dashboard');
        }

        return $next($request);
    }
}
