@props(['tone' => 'dark'])

@php
    $logo = \App\Services\MediaService::url(setting('company.logo'));
    $name = company_name();
@endphp

<a href="{{ route('home') }}" class="inline-flex items-center gap-2.5" aria-label="{{ $name }}, home">
    @if ($logo)
        <img src="{{ $logo }}" alt="{{ $name }}" class="h-9 w-auto">
    @else
        <x-brand-mark class="h-8 w-8 {{ $tone === 'light' ? 'text-white' : 'text-ink-800' }}" />
        <span class="whitespace-nowrap font-display text-base font-semibold tracking-tight sm:text-lg {{ $tone === 'light' ? 'text-white' : 'text-ink-950' }}">
            {{ $name }}
        </span>
    @endif
</a>
