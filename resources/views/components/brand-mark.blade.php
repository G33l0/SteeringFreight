@props(['class' => 'h-8 w-8'])

{{-- Abstract container stack mark, drawn rather than photographed so it stays crisp at any size. --}}
<svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 32 32" fill="none" aria-hidden="true">
    <rect x="1" y="1" width="30" height="30" rx="3" fill="currentColor" fill-opacity="0.08" stroke="currentColor" stroke-width="1.5"/>
    <rect x="6.5" y="17" width="8" height="5" fill="currentColor"/>
    <rect x="16" y="17" width="9.5" height="5" fill="currentColor" fill-opacity="0.6"/>
    <rect x="9.5" y="10.5" width="12.5" height="5" fill="currentColor" fill-opacity="0.35"/>
    <path d="M4 25.5h24" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
</svg>
