@props(['class' => 'h-8 w-8', 'tone' => 'dark'])

{{-- The Portlane mark: three lane bars, the middle one carrying direction.
     Source files live in public/assets/brand. --}}
@if ($tone === 'light')
    <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 40 40" fill="none" aria-hidden="true">
        <rect x="0.75" y="0.75" width="38.5" height="38.5" rx="6.5" fill="none" stroke="currentColor" stroke-opacity="0.28" stroke-width="1.5"/>
        <rect x="9" y="10" width="17" height="6" rx="1" fill="currentColor" fill-opacity="0.95"/>
        <path d="M9 19h16.5l5 3.5-5 3.5H9z" fill="currentColor" fill-opacity="0.7"/>
        <rect x="9" y="29" width="12" height="6" rx="1" fill="var(--color-accent-500)"/>
    </svg>
@else
    <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 40 40" fill="none" aria-hidden="true">
        <rect width="40" height="40" rx="7" fill="currentColor"/>
        <rect x="9" y="10" width="17" height="6" rx="1" fill="#ffffff" fill-opacity="0.92"/>
        <path d="M9 19h16.5l5 3.5-5 3.5H9z" fill="#ffffff" fill-opacity="0.72"/>
        <rect x="9" y="29" width="12" height="6" rx="1" fill="var(--color-accent-600)"/>
    </svg>
@endif
