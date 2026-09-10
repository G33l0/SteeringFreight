<?php

use App\Http\Middleware\EnsureUserIsStaff;
use App\Http\Middleware\RedirectIfStaff;
use App\Http\Middleware\SetSecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // On a platform host (Render, Railway, Fly and the like) the site sits
        // behind the platform's TLS proxy: the request reaches PHP over plain
        // HTTP, and the real scheme and client address arrive in X-Forwarded-*
        // headers. Set TRUSTED_PROXIES to "*" there, so rate limiting and the
        // audit log record the visitor rather than the load balancer.
        //
        // Leave it unset on shared hosting, where the request reaches PHP
        // directly and a forwarded header would be a lie anyone could tell.
        // Read from the environment rather than config() because this runs
        // before the configuration is loaded.
        $proxies = getenv('TRUSTED_PROXIES') ?: null;

        if ($proxies) {
            $middleware->trustProxies(at: $proxies === '*'
                ? '*'
                : array_map('trim', explode(',', $proxies)));
        }

        $middleware->web(append: [
            SetSecurityHeaders::class,
        ]);

        $middleware->alias([
            'staff' => EnsureUserIsStaff::class,
            'guest.staff' => RedirectIfStaff::class,
        ]);

        $middleware->redirectGuestsTo(fn () => route('admin.login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
