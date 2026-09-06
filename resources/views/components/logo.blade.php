@props(['tone' => 'dark', 'linked' => true, 'size' => 'base'])

@php
    $uploaded = \App\Services\MediaService::url(setting('company.logo'));
    $name = company_name();
    // "Portlane Shipping" is set as two words so the first carries the weight.
    [$leadWord, $restWords] = array_pad(explode(' ', trim($name), 2), 2, '');
    $markClass = $size === 'lg' ? 'h-10 w-10' : 'h-8 w-8';
    $textClass = $size === 'lg' ? 'text-xl' : 'text-base sm:text-lg';
@endphp

<{{ $linked ? 'a' : 'span' }} @if ($linked) href="{{ route('home') }}" @endif
    class="inline-flex items-center gap-2.5" aria-label="{{ $name }}{{ $linked ? ', home' : '' }}">
    @if ($uploaded)
        <img src="{{ $uploaded }}" alt="{{ $name }}" class="{{ $size === 'lg' ? 'h-11' : 'h-9' }} w-auto">
    @else
        <x-brand-mark :tone="$tone === 'light' ? 'light' : 'dark'"
                      class="{{ $markClass }} shrink-0 {{ $tone === 'light' ? 'text-white' : 'text-ink-950' }}" />
        <span class="whitespace-nowrap font-display {{ $textClass }} tracking-tight">
            <span class="font-semibold {{ $tone === 'light' ? 'text-white' : 'text-ink-950' }}">{{ $leadWord }}</span>@if ($restWords)<span class="font-normal {{ $tone === 'light' ? 'text-ink-300' : 'text-ink-500' }}"> {{ $restWords }}</span>@endif
        </span>
    @endif
</{{ $linked ? 'a' : 'span' }}>
