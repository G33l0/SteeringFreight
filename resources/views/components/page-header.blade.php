@props(['title', 'intro' => null, 'eyebrow' => null, 'align' => 'left'])

<section class="border-b border-ink-100 bg-ink-50">
    <div class="mx-auto max-w-6xl px-gutter py-section-sm {{ $align === 'center' ? 'text-center' : '' }}">
        @if ($eyebrow)
            <p class="text-sm font-semibold uppercase tracking-[0.14em] text-accent-700">{{ $eyebrow }}</p>
        @endif
        <h1 class="mt-3 text-heading font-semibold">{{ $title }}</h1>
        @if ($intro)
            <p class="mt-4 max-w-2xl text-lg leading-relaxed text-ink-600 {{ $align === 'center' ? 'mx-auto' : '' }}">{{ $intro }}</p>
        @endif
        {{ $slot ?? '' }}
    </div>
</section>
