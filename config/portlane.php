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
        'legal_name' => env('APP_NAME', 'Portlane Shipping'),
        'tagline' => 'Reliable freight handling from origin to destination.',
        'email' => env('MAIL_ADMIN_ADDRESS', 'operations@example.com'),
        'phone' => '',
        'address_line_1' => '',
        'address_line_2' => '',
        'city' => '',
        'region' => '',
        'postal_code' => '',
        'country' => '',
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
    */

    'chat' => [
        'poll_interval' => (int) env('CHAT_POLL_INTERVAL', 8000),
        'message_max_length' => 4000,
        'attachments' => true,
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
