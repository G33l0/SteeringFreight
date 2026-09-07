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
    | Pagination
    |--------------------------------------------------------------------------
    */

    'per_page' => [
        'admin' => 20,
        'public' => 12,
    ],
];
