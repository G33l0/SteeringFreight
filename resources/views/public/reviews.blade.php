<x-layouts.public :metaTitle="$metaTitle" :metaDescription="$metaDescription">
    <x-page-header
        eyebrow="Client reviews"
        title="What our clients say"
        intro="Feedback from the importers, exporters and forwarding partners we work with." />

    <div class="mx-auto max-w-6xl px-6 py-12 sm:py-16">
        @if ($reviews->isEmpty())
            <p class="text-ink-600">No reviews have been published yet.</p>
        @else
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($reviews as $review)
                    <x-review-card :review="$review" />
                @endforeach
            </div>

            <div class="mt-10">
                {{ $reviews->links('components.pagination') }}
            </div>
        @endif
    </div>

    <x-cta-panel />
</x-layouts.public>
