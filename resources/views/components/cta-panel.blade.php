<section class="bg-ink-950">
    <div class="mx-auto flex max-w-6xl flex-col items-start gap-6 px-6 py-14 sm:flex-row sm:items-center sm:justify-between">
        <div class="max-w-xl">
            <h2 class="text-2xl font-semibold text-white">{{ setting('home.cta_heading') }}</h2>
            <p class="mt-2 text-ink-300">{{ setting('home.cta_body') }}</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('quote.create') }}" class="btn btn-primary">Request a quote</a>
            <a href="{{ route('track.index') }}" class="btn btn-outline-light">Track a shipment</a>
        </div>
    </div>
</section>
