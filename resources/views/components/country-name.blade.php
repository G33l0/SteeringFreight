@props(['name'])

@php
    $label = trim((string) $name);
    $flag = \App\Support\Countries::flag($label);
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-baseline gap-1.5']) }}>
    @if ($flag)
        <span role="img" aria-label="Flag of {{ $label }}" class="not-italic leading-none">{{ $flag }}</span>
    @endif
    <span>{{ $label }}</span>
</span>
