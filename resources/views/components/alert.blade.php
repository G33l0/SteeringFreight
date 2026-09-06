@props(['type' => 'success'])

@php
    $styles = [
        'success' => 'border-positive-700/25 bg-positive-100 text-positive-700',
        'error' => 'border-alert-700/25 bg-alert-100 text-alert-700',
        'info' => 'border-ink-200 bg-ink-50 text-ink-700',
    ][$type] ?? 'border-ink-200 bg-ink-50 text-ink-700';
@endphp

<div {{ $attributes->merge(['class' => "rounded border px-4 py-3 text-sm {$styles}"]) }} role="status">
    {{ $slot }}
</div>
