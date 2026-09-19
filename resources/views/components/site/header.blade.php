@php
    $navServices = \App\Models\Service::published()->ordered()->get(['title', 'slug']);
@endphp

{{--
    The branded bar is the first thing on the page. There is deliberately no
    utility strip above it: a tagline repeated over the logo is the sort of
    thing that dates a site, and the telephone and email already have a home in
    the footer and on the contact page.

    It stays with the page as it scrolls, so "Track Shipment" is one tap away
    from anywhere, and takes a hairline shadow once the page has moved so the
    bar reads as sitting above the content rather than printed on it.
--}}
<header x-data="{ open: false, services: false, scrolled: false }"
        @scroll.window="scrolled = window.scrollY > 4"
        @keydown.escape.window="open = false; services = false"
        class="sticky top-0 z-40">
    <div class="border-b border-ink-100 bg-white transition-shadow" :class="scrolled ? 'shadow-card' : ''">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-gutter py-3.5">
            <x-logo />

            <nav class="hidden items-center gap-1 lg:flex" aria-label="Main">
                <a href="{{ route('home') }}" @class(['px-3 py-2 text-sm font-medium text-ink-700 hover:text-ink-950', 'text-ink-950' => request()->routeIs('home')])>Home</a>
                <a href="{{ route('about') }}" @class(['px-3 py-2 text-sm font-medium text-ink-700 hover:text-ink-950', 'text-ink-950' => request()->routeIs('about')])>About</a>

                <div class="relative"
                     @mouseenter="services = true"
                     @mouseleave="services = false"
                     @focusin="services = true"
                     @focusout="if (! $el.contains($event.relatedTarget)) services = false">
                    <a href="{{ route('services.index') }}"
                       @click="services = false"
                       aria-haspopup="true"
                       :aria-expanded="services.toString()"
                       @class(['inline-flex items-center gap-1 px-3 py-2 text-sm font-medium text-ink-700 hover:text-ink-950', 'text-ink-950' => request()->routeIs('services.*')])>
                        Services
                        <x-icon name="chevron-down" class="h-3.5 w-3.5 transition-transform" ::class="services ? 'rotate-180' : ''" />
                    </a>

                    @if ($navServices->isNotEmpty())
                        <div x-show="services" x-cloak x-transition.opacity.duration.120ms
                             class="absolute left-0 top-full w-64 rounded-b border border-t-0 border-ink-100 bg-white py-2 shadow-raised">
                            @foreach ($navServices as $navService)
                                <a href="{{ route('services.show', $navService) }}"
                                   class="block px-4 py-2 text-sm text-ink-700 hover:bg-ink-50 hover:text-ink-950">
                                    {{ $navService->title }}
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>

                <a href="{{ route('quote.create') }}" @class(['px-3 py-2 text-sm font-medium text-ink-700 hover:text-ink-950', 'text-ink-950' => request()->routeIs('quote.*')])>Request a Quote</a>
                <a href="{{ route('contact.create') }}" @class(['px-3 py-2 text-sm font-medium text-ink-700 hover:text-ink-950', 'text-ink-950' => request()->routeIs('contact.*')])>Contact</a>
            </nav>

            <div class="flex items-center gap-2 sm:gap-3">
                <a href="{{ route('track.index') }}" class="btn btn-primary btn-sm hidden sm:inline-flex">
                    <x-icon name="search" class="h-4 w-4" />
                    Track Shipment
                </a>

                <a href="{{ route('track.index') }}" class="btn btn-primary btn-sm w-11 px-0 sm:hidden" aria-label="Track a shipment">
                    <x-icon name="search" class="h-4 w-4" />
                </a>

                <button type="button" @click="open = ! open" class="flex h-11 w-11 items-center justify-center rounded text-ink-700 lg:hidden"
                        :aria-expanded="open.toString()" aria-controls="mobile-nav" aria-label="Toggle navigation">
                    <x-icon name="menu" class="h-6 w-6" x-show="! open" />
                    <x-icon name="close" class="h-6 w-6" x-show="open" x-cloak />
                </button>
            </div>
        </div>
    </div>

    <div id="mobile-nav" x-show="open" x-cloak x-transition.opacity.duration.120ms
         class="max-h-[calc(100vh-4.5rem)] overflow-y-auto border-b border-ink-100 bg-white shadow-card lg:hidden">
        <nav class="mx-auto max-w-6xl px-gutter py-3" aria-label="Mobile">
            <a href="{{ route('home') }}" class="block border-b border-ink-50 py-3 text-sm font-medium text-ink-800">Home</a>
            <a href="{{ route('about') }}" class="block border-b border-ink-50 py-3 text-sm font-medium text-ink-800">About</a>
            <a href="{{ route('services.index') }}" class="block py-3 text-sm font-medium text-ink-800">Services</a>
            @foreach ($navServices as $navService)
                <a href="{{ route('services.show', $navService) }}" class="block border-b border-ink-50 py-2 pl-4 text-sm text-ink-600">
                    {{ $navService->title }}
                </a>
            @endforeach
            <a href="{{ route('quote.create') }}" class="block border-b border-ink-50 py-3 text-sm font-medium text-ink-800">Request a Quote</a>
            <a href="{{ route('contact.create') }}" class="block border-b border-ink-50 py-3 text-sm font-medium text-ink-800">Contact</a>
            <a href="{{ route('track.index') }}" class="btn btn-primary mt-4 w-full">Track Shipment</a>
        </nav>
    </div>
</header>
