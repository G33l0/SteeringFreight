@props(['label', 'value', 'href' => null, 'tone' => 'default'])

@php
    $toneClass = [
        'default' => 'text-ink-950',
        'alert' => 'text-alert-700',
        'caution' => 'text-caution-700',
        'positive' => 'text-positive-700',
    ][$tone] ?? 'text-ink-950';
@endphp

<{{ $href ? 'a' : 'div' }} @if ($href) href="{{ $href }}" @endif
    {{ $attributes->merge(['class' => 'block border border-ink-100 bg-white p-4 '.($href ? 'transition hover:border-ink-300' : '')]) }}>
    <p class="text-xs font-semibold uppercase tracking-wide text-ink-500">{{ $label }}</p>
    <p class="mt-1.5 font-display text-2xl font-semibold {{ $toneClass }}">{{ $value }}</p>
</{{ $href ? 'a' : 'div' }}>
