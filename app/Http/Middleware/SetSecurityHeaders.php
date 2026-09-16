<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Conservative security headers that do not need any server configuration,
 * so they also apply on shared hosting.
 *
 * Strict-Transport-Security is the one with teeth, and the one that needs
 * thinking about. Without it a visitor's first request of the day travels over
 * plain HTTP before the redirect to HTTPS happens, and that request is the one
 * worth intercepting. With it, the browser refuses to speak HTTP to this site
 * at all for `hsts_max_age` seconds.
 *
 * That refusal is not something the site can take back: a browser that has been
 * told to remember it will not ask again until the time runs out. So the header
 * is only sent over a connection that is already secure, and the duration is
 * configurable, so a new install can start with a few minutes and raise it once
 * the certificate is known to renew cleanly.
 */
class SetSecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');

        $maxAge = (int) config('portlane.security.hsts_max_age');

        // Never over plain HTTP: a site still being set up, or running behind a
        // certificate that has not been issued yet, would otherwise lock its own
        // visitors out of it.
        if ($maxAge > 0 && $request->secure()) {
            $response->headers->set('Strict-Transport-Security', "max-age={$maxAge}");
        }

        return $response;
    }
}
