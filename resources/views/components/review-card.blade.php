@props(['review'])

<figure {{ $attributes->merge(['class' => 'flex h-full flex-col border border-ink-100 bg-white p-6']) }}>
    <div class="flex items-center gap-1" aria-label="Rated {{ $review->rating }} out of 5">
        @for ($i = 1; $i <= 5; $i++)
            <x-icon name="star" class="h-4 w-4 {{ $i <= $review->rating ? 'text-accent-600' : 'text-ink-200' }}"
                    stroke-width="1.4" fill="{{ $i <= $review->rating ? 'currentColor' : 'none' }}" />
        @endfor
    </div>

    <blockquote class="mt-4 flex-1 text-sm leading-relaxed text-ink-700">{{ $review->body }}</blockquote>

    <figcaption class="mt-5 flex items-center gap-3 border-t border-ink-100 pt-4">
        @if ($photo = \App\Services\MediaService::url($review->photo_path))
            <img src="{{ $photo }}" alt="" class="h-10 w-10 rounded-full object-cover" loading="lazy">
        @endif
        <div class="text-sm">
            <p class="font-semibold text-ink-900">{{ $review->customer_name }}</p>
            <p class="text-ink-500">
                {{ collect([$review->company, $review->location])->filter()->implode(', ') }}
            </p>
        </div>
    </figcaption>

    @if ($review->is_sample)
        <p class="mt-3 text-xs font-semibold uppercase tracking-wide text-ink-400">Sample content</p>
    @endif
</figure>
