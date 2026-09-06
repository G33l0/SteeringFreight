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
