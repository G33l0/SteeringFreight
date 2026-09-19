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
    /**
     * Everything the application legitimately loads, and nothing else.
     *
     * Scripts, styles, images and fonts are all served from this origin: the
     * front end is built and committed, the fonts are bundled, and uploaded
     * images are streamed from the site's own storage. So an injected
     * <script src="https://somewhere.else/..."> is refused by the browser
     * before it is fetched.
     *
     * Two allowances are deliberate. 'unsafe-inline' for styles, because the
     * brand colours are published as an inline <style> block and the progress
     * bar sets a width; an injected stylesheet is a far smaller problem than an
     * injected script. And 'unsafe-eval' for scripts, because Alpine evaluates
     * the expressions written in the markup — without it the navigation, the
     * dropdowns and the chat all stop. Both are named here rather than left for
     * somebody to discover.
     *
     * @var array<string, string>
     */
    private const POLICY = [
        'default-src' => "'self'",
        'script-src' => "'self' 'unsafe-eval'",
        'style-src' => "'self' 'unsafe-inline'",
        'img-src' => "'self' data:",
        'font-src' => "'self'",
        'connect-src' => "'self'",
        'form-action' => "'self'",
        'base-uri' => "'self'",
        'frame-ancestors' => "'self'",
        'object-src' => "'none'",
    ];

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

        $this->applyContentSecurityPolicy($response);

        return $response;
    }

    /**
     * Report-only is the way to introduce this on a site already serving
     * customers: the browser complains in its console and blocks nothing, so a
     * missed dependency shows up without anybody's page breaking.
     */
    private function applyContentSecurityPolicy(Response $response): void
    {
        $mode = mb_strtolower((string) config('portlane.security.csp', 'enforce'));

        if (! in_array($mode, ['enforce', 'report'], true)) {
            return;
        }

        $policy = collect(self::POLICY)
            ->map(fn (string $value, string $directive) => "{$directive} {$value}")
            ->implode('; ');

        $response->headers->set(
            $mode === 'report' ? 'Content-Security-Policy-Report-Only' : 'Content-Security-Policy',
            $policy,
        );
    }
}
