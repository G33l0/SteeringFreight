@props(['title' => 'Dashboard'])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title }} — {{ company_name() }} admin</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-ink-50 antialiased">
<div x-data="{ nav: false }" class="lg:flex">
    {{-- Sidebar --}}
    <div x-show="nav" x-cloak class="fixed inset-0 z-30 bg-ink-950/50 lg:hidden" @click="nav = false" aria-hidden="true"></div>

    <aside :class="nav ? 'translate-x-0' : '-translate-x-full'"
           class="fixed inset-y-0 left-0 z-40 w-64 shrink-0 overflow-y-auto bg-ink-950 px-3 py-4 transition-transform lg:sticky lg:top-0 lg:h-screen lg:translate-x-0">
        <div class="flex items-center justify-between px-2 pb-4">
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2 text-white">
                <x-brand-mark class="h-7 w-7 text-white" />
                <span class="font-display text-sm font-semibold">{{ company_name() }}</span>
            </a>
            <button type="button" @click="nav = false" class="rounded p-1 text-ink-300 lg:hidden" aria-label="Close navigation">
                <x-icon name="close" class="h-5 w-5" />
            </button>
        </div>

        <nav class="space-y-6" aria-label="Admin">
            <div class="space-y-0.5">
                <x-admin.nav-link :href="route('admin.dashboard')" icon="dashboard" :active="request()->routeIs('admin.dashboard')">Dashboard</x-admin.nav-link>
                <x-admin.nav-link :href="route('admin.shipments.index')" icon="container" :active="request()->routeIs('admin.shipments.*')">Shipments</x-admin.nav-link>
                <x-admin.nav-link :href="route('admin.customers.index')" icon="user" :active="request()->routeIs('admin.customers.*')">Customers</x-admin.nav-link>
                @can('statuses.view')
                    <x-admin.nav-link :href="route('admin.statuses.index')" icon="list" :active="request()->routeIs('admin.statuses.*')">Tracking statuses</x-admin.nav-link>
                @endcan
            </div>

            <div class="space-y-0.5">
                <p class="px-3 pb-1 text-[11px] font-semibold uppercase tracking-wider text-ink-500">Customer contact</p>
                <x-admin.nav-link :href="route('admin.messages.index')" icon="chat" :active="request()->routeIs('admin.messages.*')" :badge="$unreadMessages ?? null">Messages</x-admin.nav-link>
                <x-admin.nav-link :href="route('admin.quotes.index')" icon="quote" :active="request()->routeIs('admin.quotes.*')">Quote requests</x-admin.nav-link>
                <x-admin.nav-link :href="route('admin.contact-messages.index')" icon="mail" :active="request()->routeIs('admin.contact-messages.*')">Contact messages</x-admin.nav-link>
                <x-admin.nav-link :href="route('admin.documents.index')" icon="documents" :active="request()->routeIs('admin.documents.*')">Documents</x-admin.nav-link>
            </div>

            @canany(['services.view', 'pages.view', 'reviews.view', 'faqs.view'])
                <div class="space-y-0.5">
                    <p class="px-3 pb-1 text-[11px] font-semibold uppercase tracking-wider text-ink-500">Website</p>
                    @can('services.view')
                        <x-admin.nav-link :href="route('admin.services.index')" icon="consolidation" :active="request()->routeIs('admin.services.*')">Services</x-admin.nav-link>
                    @endcan
                    @can('pages.view')
                        <x-admin.nav-link :href="route('admin.pages.index')" icon="documents" :active="request()->routeIs('admin.pages.*')">Pages</x-admin.nav-link>
                    @endcan
                    @can('faqs.view')
                        <x-admin.nav-link :href="route('admin.faqs.index')" icon="list" :active="request()->routeIs('admin.faqs.*')">FAQ</x-admin.nav-link>
                    @endcan
                    @can('reviews.view')
                        <x-admin.nav-link :href="route('admin.reviews.index')" icon="star" :active="request()->routeIs('admin.reviews.*')">Reviews</x-admin.nav-link>
                    @endcan
                </div>
            @endcanany

            @canany(['settings.manage', 'users.manage', 'audit.view'])
                <div class="space-y-0.5">
                    <p class="px-3 pb-1 text-[11px] font-semibold uppercase tracking-wider text-ink-500">Administration</p>
                    @can('settings.manage')
                        <x-admin.nav-link :href="route('admin.settings.edit')" icon="settings" :active="request()->routeIs('admin.settings.*')">Site settings</x-admin.nav-link>
                    @endcan
                    @can('users.manage')
                        <x-admin.nav-link :href="route('admin.users.index')" icon="user" :active="request()->routeIs('admin.users.*')">Admin users</x-admin.nav-link>
                    @endcan
                    @can('audit.view')
                        <x-admin.nav-link :href="route('admin.audit-logs.index')" icon="log" :active="request()->routeIs('admin.audit-logs.*')">Audit logs</x-admin.nav-link>
                    @endcan
                </div>
            @endcanany
        </nav>

        <div class="mt-8 border-t border-white/10 px-3 pt-4">
            <a href="{{ route('home') }}" target="_blank" rel="noopener" class="text-xs text-ink-400 hover:text-white">View public website</a>
        </div>
    </aside>

    <div class="min-w-0 flex-1">
        <header class="sticky top-0 z-20 border-b border-ink-100 bg-white">
            <div class="flex items-center justify-between gap-4 px-4 py-3 sm:px-6">
                <div class="flex min-w-0 items-center gap-3">
                    <button type="button" @click="nav = true" class="rounded p-1.5 text-ink-600 lg:hidden" aria-label="Open navigation">
                        <x-icon name="menu" class="h-6 w-6" />
                    </button>
                    <h1 class="truncate font-display text-lg font-semibold">{{ $title }}</h1>
                </div>

                <div class="flex items-center gap-3">
                    @isset($actions)
                        <div class="hidden items-center gap-2 sm:flex">{{ $actions }}</div>
                    @endisset

                    <div x-data="{ open: false }" class="relative">
                        <button type="button" @click="open = ! open" class="flex items-center gap-2 rounded border border-ink-100 px-2.5 py-1.5 text-sm hover:bg-ink-50">
                            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-ink-800 text-[11px] font-semibold text-white">
                                {{ auth()->user()->initials() }}
                            </span>
                            <span class="hidden sm:inline">{{ auth()->user()->name }}</span>
                            <x-icon name="chevron-down" class="h-3.5 w-3.5 text-ink-400" />
                        </button>

                        <div x-show="open" x-cloak @click.outside="open = false"
                             class="absolute right-0 top-full z-30 mt-1 w-52 border border-ink-100 bg-white py-1 shadow-lg">
                            <p class="px-4 py-2 text-xs text-ink-500">{{ auth()->user()->role->label() }}</p>
                            <a href="{{ route('admin.profile.edit') }}" class="block px-4 py-2 text-sm hover:bg-ink-50">Your profile</a>
                            <form method="POST" action="{{ route('admin.logout') }}">
                                @csrf
                                <button type="submit" class="w-full px-4 py-2 text-left text-sm text-alert-700 hover:bg-ink-50">Sign out</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            @isset($actions)
                <div class="flex flex-wrap gap-2 border-t border-ink-50 px-4 py-2 sm:hidden">{{ $actions }}</div>
            @endisset
        </header>

        <main class="px-4 py-6 sm:px-6 lg:px-8">
            @if (session('status'))
                <x-alert class="mb-5">{{ session('status') }}</x-alert>
            @endif

            @if ($errors->any())
                <x-alert type="error" class="mb-5">
                    <p class="font-semibold">There is a problem with the information supplied.</p>
                    <ul class="mt-1.5 list-disc space-y-0.5 pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </x-alert>
            @endif

            {{ $slot }}
        </main>
    </div>
</div>
</body>
</html>
