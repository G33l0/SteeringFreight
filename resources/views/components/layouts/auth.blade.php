@props(['title' => 'Sign in'])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title }} — {{ company_name() }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="icon" href="{{ asset('favicon-32.png') }}" sizes="32x32" type="image/png">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <x-brand-styles />
</head>
<body class="min-h-screen bg-ink-950 antialiased">
    <div class="mx-auto flex min-h-screen max-w-md flex-col justify-center px-gutter py-12">
        <div class="text-center">
            <x-logo tone="light" size="lg" />
            <p class="mt-2 text-sm text-ink-400">Staff area</p>
        </div>

        <div class="mt-8 rounded border border-white/10 bg-white p-6 shadow-raised sm:p-8">
            <h1 class="font-display text-xl font-semibold">{{ $title }}</h1>

            @if (session('status'))
                <x-alert class="mt-4">{{ session('status') }}</x-alert>
            @endif

            {{ $slot }}
        </div>

        <p class="mt-6 text-center text-xs text-ink-500">
            <a href="{{ route('home') }}" class="hover:text-ink-300">Back to the website</a>
        </p>
    </div>
</body>
</html>
