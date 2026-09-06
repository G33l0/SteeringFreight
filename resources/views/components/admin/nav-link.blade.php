@props(['href', 'icon' => 'list', 'active' => false, 'badge' => null])

<a href="{{ $href }}" class="admin-nav-link" @if ($active) aria-current="page" @endif>
    <x-icon :name="$icon" class="h-4.5 w-4.5 shrink-0 opacity-80" />
    <span class="flex-1">{{ $slot }}</span>
    @if ($badge)
        <span class="rounded-full bg-accent-600 px-1.5 py-0.5 text-[11px] font-semibold text-white">{{ $badge }}</span>
    @endif
</a>
