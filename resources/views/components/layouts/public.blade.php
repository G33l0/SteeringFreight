@props(['metaTitle' => null, 'metaDescription' => null, 'robots' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $metaTitle ?? company_name() }}</title>
    <meta name="description" content="{{ $metaDescription ?? setting('seo.meta_description') }}">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta name="robots" content="{{ $robots ?? (setting('seo.indexable', true) ? 'index, follow' : 'noindex, nofollow') }}">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ company_name() }}">
    <meta property="og:title" content="{{ $metaTitle ?? company_name() }}">
    <meta property="og:description" content="{{ $metaDescription ?? setting('seo.meta_description') }}">
    <meta property="og:url" content="{{ url()->current() }}">
    @if ($ogImage = \App\Services\MediaService::url(setting('seo.og_image')))
        <meta property="og:image" content="{{ $ogImage }}">
        <meta name="twitter:card" content="summary_large_image">
    @endif

    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="min-h-screen bg-white antialiased">
    <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded focus:bg-ink-900 focus:px-4 focus:py-2 focus:text-white">
        Skip to content
    </a>

    <x-site.header />

    <main id="main">
        {{ $slot }}
    </main>

    <x-site.footer />
</body>
</html>
