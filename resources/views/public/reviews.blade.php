<x-layouts.public :metaTitle="$metaTitle" :metaDescription="$metaDescription">
    <x-page-header
        eyebrow="Client reviews"
        title="What our clients say"
        intro="Feedback from the importers, exporters and forwarding partners we work with, published as we receive it." />

    <div class="mx-auto max-w-6xl px-6 py-12 sm:py-16">
        @if ($reviews->isEmpty())
            <div class="mx-auto max-w-2xl border border-ink-100 bg-ink-50 p-8 text-center">
                <h2 class="font-display text-lg font-semibold">No reviews published yet</h2>
                <p class="mt-3 text-sm leading-relaxed text-ink-600">
                    We ask clients for feedback once a shipment has been delivered, and publish it here with their
                    permission. Nothing appears on this page unless a client has actually said it.
                </p>
                <div class="mt-6 flex flex-wrap justify-center gap-3">
                    <a href="{{ route('quote.create') }}" class="btn btn-primary btn-sm">Request a quote</a>
                    <a href="{{ route('contact.create') }}" class="btn btn-outline btn-sm">Talk to the team</a>
                </div>
            </div>
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
