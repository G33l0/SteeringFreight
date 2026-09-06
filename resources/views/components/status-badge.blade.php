@props(['status' => null, 'label' => null])

@php
    $colour = $status?->colour ?? 'slate';
    $text = $label ?? $status?->publicName() ?? 'Not set';
@endphp

<span {{ $attributes->merge(['class' => "badge badge-{$colour}"]) }}>{{ $text }}</span>
