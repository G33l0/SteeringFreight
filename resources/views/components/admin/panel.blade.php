@props(['title' => null, 'description' => null, 'compact' => false])

<section {{ $attributes->merge(['class' => 'border border-ink-100 bg-white']) }}>
    @if ($title)
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-ink-100 px-4 py-3 sm:px-5">
            <div>
                <h2 class="font-display text-base font-semibold">{{ $title }}</h2>
                @if ($description)
                    <p class="mt-0.5 text-sm text-ink-500">{{ $description }}</p>
                @endif
            </div>
            @isset($actions)
                <div class="flex flex-wrap gap-2">{{ $actions }}</div>
            @endisset
        </div>
    @endif

    <div class="{{ $compact ? '' : 'p-4 sm:p-5' }}">
        {{ $slot }}
    </div>
</section>
