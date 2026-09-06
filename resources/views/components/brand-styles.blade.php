@php
    $primary = trim((string) setting('brand.primary_colour'));
    $accent = trim((string) setting('brand.accent_colour'));
    $primaryChanged = $primary !== '' && strcasecmp($primary, (string) config('portlane.brand.primary')) !== 0;
    $accentChanged = $accent !== '' && strcasecmp($accent, (string) config('portlane.brand.accent')) !== 0;
@endphp

{{-- Brand colours are only published when they differ from the shipped palette,
     so the hand tuned default ramp is left alone unless somebody changes it. --}}
@if ($primaryChanged || $accentChanged)
    <style>
        :root {
            @if ($primaryChanged)
                --color-ink-950: {{ $primary }};
                --color-ink-900: color-mix(in srgb, {{ $primary }} 93%, #ffffff);
                --color-ink-800: color-mix(in srgb, {{ $primary }} 85%, #ffffff);
                --color-ink-700: color-mix(in srgb, {{ $primary }} 76%, #ffffff);
                --color-ink-600: color-mix(in srgb, {{ $primary }} 66%, #ffffff);
                --color-ink-500: color-mix(in srgb, {{ $primary }} 54%, #ffffff);
                --color-ink-400: color-mix(in srgb, {{ $primary }} 40%, #ffffff);
                --color-ink-300: color-mix(in srgb, {{ $primary }} 26%, #ffffff);
                --color-ink-200: color-mix(in srgb, {{ $primary }} 15%, #ffffff);
                --color-ink-100: color-mix(in srgb, {{ $primary }} 9%, #ffffff);
                --color-ink-50: color-mix(in srgb, {{ $primary }} 4%, #ffffff);
            @endif
            @if ($accentChanged)
                --color-accent-700: color-mix(in srgb, {{ $accent }} 84%, #000000);
                --color-accent-600: {{ $accent }};
                --color-accent-500: color-mix(in srgb, {{ $accent }} 86%, #ffffff);
                --color-accent-100: color-mix(in srgb, {{ $accent }} 18%, #ffffff);
                --color-accent-50: color-mix(in srgb, {{ $accent }} 8%, #ffffff);
            @endif
        }
    </style>
@endif
