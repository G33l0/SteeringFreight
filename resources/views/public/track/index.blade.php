<x-layouts.public :metaTitle="$metaTitle" :metaDescription="$metaDescription">
    <section class="bg-ink-950">
        <div class="mx-auto max-w-3xl px-6 py-16 sm:py-20">
            <h1 class="text-3xl font-semibold text-white sm:text-4xl">Track your shipment</h1>
            <p class="mt-3 text-ink-300">{{ setting('tracking.intro') }}</p>

            <div class="mt-8 rounded border border-white/15 bg-white/5 p-5">
                <x-track-form tone="dark" :example="$example" :autofocus="true" />
            </div>

            <p class="mt-4 text-sm text-ink-400">
                Tracking numbers are issued when a booking is confirmed and look like
                <span class="font-mono text-ink-200">{{ $example }}</span>.
            </p>
        </div>
    </section>

    <div class="mx-auto grid max-w-5xl gap-8 px-6 py-14 sm:grid-cols-2">
        <div class="border border-ink-100 p-6">
            <h2 class="font-display text-lg font-semibold">Can't find your number?</h2>
            <p class="mt-2 text-sm leading-relaxed text-ink-600">
                The tracking number is on your booking confirmation. If you no longer have it, send us the reference,
                the route and the approximate booking date and we will look it up.
            </p>
            <a href="{{ route('contact.create') }}" class="btn btn-outline btn-sm mt-4">Contact the operations desk</a>
        </div>

        <div class="border border-ink-100 p-6">
            <h2 class="font-display text-lg font-semibold">What you will see</h2>
            <ul class="mt-3 space-y-2 text-sm text-ink-600">
                <li class="flex items-start gap-2"><x-icon name="check" class="mt-0.5 h-4 w-4 shrink-0 text-accent-600" />Current status and location</li>
                <li class="flex items-start gap-2"><x-icon name="check" class="mt-0.5 h-4 w-4 shrink-0 text-accent-600" />Origin, destination and shipping method</li>
                <li class="flex items-start gap-2"><x-icon name="check" class="mt-0.5 h-4 w-4 shrink-0 text-accent-600" />Estimated delivery and the full tracking history</li>
                <li class="flex items-start gap-2"><x-icon name="check" class="mt-0.5 h-4 w-4 shrink-0 text-accent-600" />A direct line to the team handling the shipment</li>
            </ul>
        </div>
    </div>
</x-layouts.public>
