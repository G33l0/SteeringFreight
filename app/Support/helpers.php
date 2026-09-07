<?php

use App\Support\Settings;

if (! function_exists('setting')) {
    /**
     * Read an editable site setting, falling back to its defined default.
     */
    function setting(string $key, mixed $default = null): mixed
    {
        return app(Settings::class)->get($key, $default);
    }
}

if (! function_exists('settings')) {
    function settings(): Settings
    {
        return app(Settings::class);
    }
}

if (! function_exists('company_name')) {
    function company_name(): string
    {
        return app(Settings::class)->string('company.name', (string) config('portlane.company.name'));
    }
}

if (! function_exists('upload_max_kb')) {
    /**
     * Largest accepted upload, in kilobytes. Editable in the admin settings.
     */
    function upload_max_kb(): int
    {
        return max(64, app(Settings::class)->int('uploads.max_kb', (int) config('portlane.uploads.max_kb')));
    }
}

if (! function_exists('chat_poll_interval')) {
    /**
     * How often an open tracking page checks for new chat messages, in milliseconds.
     */
    function chat_poll_interval(): int
    {
        return max(2000, app(Settings::class)->int('tracking.chat_poll_interval', (int) config('portlane.chat.poll_interval')));
    }
}

if (! function_exists('chat_retention_hours')) {
    /**
     * How long a customer conversation stays readable before it is deleted.
     *
     * This is a privacy commitment rather than a preference, so it lives in
     * configuration and is not editable from the admin panel.
     */
    function chat_retention_hours(): int
    {
        return max(1, (int) config('portlane.chat.retention_hours', 24));
    }
}
