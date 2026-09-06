@props(['title' => 'Sign in'])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title }} — {{ company_name() }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-ink-950 antialiased">
    <div class="mx-auto flex min-h-screen max-w-md flex-col justify-center px-6 py-12">
        <div class="text-center">
            <a href="{{ route('home') }}" class="inline-flex items-center gap-2.5 text-white">
                <x-brand-mark class="h-8 w-8 text-white" />
                <span class="font-display text-lg font-semibold">{{ company_name() }}</span>
            </a>
            <p class="mt-2 text-sm text-ink-400">Staff area</p>
        </div>

        <div class="mt-8 rounded border border-white/10 bg-white p-6 sm:p-8">
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
