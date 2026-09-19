<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Brand defaults
    |--------------------------------------------------------------------------
    |
    | These values are only used the first time the application runs, before
    | anything has been saved in the site settings screen, and as a fallback
    | if a setting row is missing. Everything here is editable in the admin
    | panel under Site Settings.
    |
    */

    'company' => [
        'name' => env('APP_NAME', 'Portlane Shipping'),
        'tagline' => 'Freight handled with care, from origin to destination.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Brand colours
    |--------------------------------------------------------------------------
    |
    | The starting palette. Both values are editable under Site settings, and
    | the layouts publish them as CSS custom properties, so the colours can be
    | changed without rebuilding the front end.
    |
    */

    'brand' => [
        'primary' => env('BRAND_PRIMARY_COLOUR', '#0c1f2e'),
        'accent' => env('BRAND_ACCENT_COLOUR', '#ab4c17'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    |
    | Where internal alerts (quote requests, contact messages, new customer
    | chats) are sent before an address has been saved in the settings screen.
    |
    */

    'notifications' => [
        'admin_email' => env('MAIL_ADMIN_ADDRESS'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Tracking numbers
    |--------------------------------------------------------------------------
    |
    | Tracking numbers are built as PREFIX-NNNNNNNN. Both the prefix and the
    | number of digits can be changed in the admin settings; the values below
    | are the defaults used when no setting has been saved yet.
    |
    */

    'tracking' => [
        'prefix' => env('TRACKING_PREFIX', 'PLS'),
        'digits' => (int) env('TRACKING_DIGITS', 8),
        'separator' => '-',
    ],

    /*
    |--------------------------------------------------------------------------
    | Uploads
    |--------------------------------------------------------------------------
    */

    'uploads' => [
        'max_kb' => (int) env('UPLOAD_MAX_KB', 8192),
        'document_mimes' => ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'doc', 'docx', 'xls', 'xlsx', 'csv'],
        'image_mimes' => ['jpg', 'jpeg', 'png', 'webp'],
        'image_max_kb' => 4096,
    ],

    /*
    |--------------------------------------------------------------------------
    | Customer chat
    |--------------------------------------------------------------------------
    |
    | The customer chat uses short polling so it runs on plain shared hosting
    | without a websocket server. The transport is isolated behind the chat
    | controller, so it can be swapped for broadcasting later on.
    |
    | The chat is deliberately short lived. A conversation is readable for
    | `retention_hours` after its last message and is then deleted, messages and
    | all, so nothing a customer types stays on the server. Files cannot be sent
    | through the chat at all; shipment documents are uploaded by staff and are
    | kept with the shipment instead.
    |
    */

    'chat' => [
        'poll_interval' => (int) env('CHAT_POLL_INTERVAL', 8000),
        'message_max_length' => 4000,
        'retention_hours' => max(1, (int) env('CHAT_RETENTION_HOURS', 24)),
    ],

    /*
    |--------------------------------------------------------------------------
    | Staff access and sign in
    |--------------------------------------------------------------------------
    |
    | A master admin signs in with a password and then a one time code sent to
    | their email address. Whether the code is asked for at all is a site
    | setting (`security.two_factor`) so it can be turned off from the panel;
    | `php artisan portlane:two-factor off` does the same from the console if
    | email breaks and nobody can get in.
    |
    | The code is six digits, stored only as a hash, valid for `code_ttl`
    | minutes and thrown away after `code_attempts` wrong guesses. `code_resend`
    | is how long the account must wait before another code is sent, which
    | stops the login form being used to post mail at somebody.
    |
    */

    'security' => [
        'code_ttl' => max(1, (int) env('LOGIN_CODE_TTL', 10)),
        'code_attempts' => max(1, (int) env('LOGIN_CODE_ATTEMPTS', 5)),
        'code_resend' => max(15, (int) env('LOGIN_CODE_RESEND', 60)),
        // How long the half finished sign in survives before the password has
        // to be entered again.
        'challenge_ttl' => max(1, (int) env('LOGIN_CHALLENGE_TTL', 15)),

        // How long a browser should refuse to speak plain HTTP to this site,
        // in seconds. Only sent over a connection that is already secure.
        //
        // A browser that has been told this will not ask again until the time
        // runs out, so a fresh install should start small — HSTS_MAX_AGE=300
        // while the certificate is being proved — and raise it to a year once
        // HTTPS is known to be working and renewing. Set to 0 to send nothing.
        'hsts_max_age' => max(0, (int) env('HSTS_MAX_AGE', 31536000)),

        // Content Security Policy. 'enforce' blocks anything it disallows,
        // 'report' only complains in the browser console, 'off' sends nothing.
        //
        // The policy allows 'unsafe-eval' because Alpine evaluates the x-data
        // and @click expressions in the markup, and without it every dropdown,
        // the mobile menu and the chat polling stop working. That is a real
        // weakening and worth naming: what the policy still buys is that no
        // script can be loaded from another origin and no injected <script>
        // block will run, which is how cross-site scripting normally arrives.
        // Closing the eval gap means moving to Alpine's CSP build and
        // rewriting every inline expression as a registered component.
        'csp' => env('SECURITY_CSP', 'enforce'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    */

    'per_page' => [
        'admin' => 20,
        'public' => 12,
    ],
];
