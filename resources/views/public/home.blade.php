<x-layouts.public :metaTitle="$metaTitle" :metaDescription="$metaDescription">
    @php
        $heroImage = \App\Services\MediaService::url(setting('home.hero_image'));
        $whyPoints = settings()->list('home.why_points');
        $destinations = settings()->list('home.destinations');
        $trackingSteps = [
            ['title' => 'Booking is confirmed', 'body' => 'We issue your tracking number as soon as the booking is on file.'],
            ['title' => 'The file is updated as cargo moves', 'body' => 'Our coordinators record each milestone: collection, departure, arrival, clearance and delivery.'],
            ['title' => 'You check it whenever you need to', 'body' => 'Enter the tracking number to see the current status, location and delivery estimate.'],
        ];
    @endphp

    {{-- Hero --}}
    <section class="relative isolate overflow-hidden bg-ink-950">
        <div class="absolute inset-0 -z-10">
            @if ($heroImage)
                <img src="{{ $heroImage }}" alt="" class="h-full w-full object-cover opacity-40">
            @else
                <x-art.harbour class="h-full w-full object-cover" />
            @endif
            <div class="absolute inset-0 bg-gradient-to-r from-ink-950 via-ink-950/75 to-ink-950/10"></div>
        </div>

        <div class="mx-auto max-w-6xl px-6 py-20 sm:py-28">
            <div class="max-w-2xl">
                <p class="text-sm font-semibold uppercase tracking-[0.14em] text-accent-500">{{ setting('home.hero_eyebrow') }}</p>
                <h1 class="mt-4 text-4xl font-semibold leading-[1.1] text-white sm:text-5xl">{{ setting('home.hero_heading') }}</h1>
                <p class="mt-5 max-w-xl text-lg leading-relaxed text-ink-200">{{ setting('home.hero_intro') }}</p>

                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('quote.create') }}" class="btn btn-primary">Request a quote</a>
                    <a href="{{ route('services.index') }}" class="btn btn-outline-light">Our services</a>
                </div>
            </div>

            <div class="mt-12 max-w-2xl rounded border border-white/15 bg-white/5 p-5 backdrop-blur-[2px]">
                <h2 class="font-display text-base font-semibold text-white">Track a shipment</h2>
                <p class="mt-1 text-sm text-ink-300">Enter the tracking number from your booking confirmation.</p>
                <x-track-form class="mt-4" tone="dark" :example="app(\App\Services\TrackingNumberGenerator::class)->example()" />
            </div>
        </div>
    </section>

    {{-- Services --}}
    <section class="mx-auto max-w-6xl px-6 py-16 sm:py-20">
        <div class="max-w-2xl">
            <h2 class="text-2xl font-semibold sm:text-3xl">{{ setting('home.services_heading') }}</h2>
            <p class="mt-3 text-ink-600">{{ setting('home.services_intro') }}</p>
        </div>

        <div class="mt-10 grid gap-px overflow-hidden rounded border border-ink-100 bg-ink-100 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($services as $service)
                <a href="{{ route('services.show', $service) }}" class="group flex flex-col bg-white p-6 transition hover:bg-ink-50">
                    <x-icon :name="$service->icon" class="h-7 w-7 text-accent-600" />
                    <h3 class="mt-4 font-display text-lg font-semibold">{{ $service->title }}</h3>
                    <p class="mt-2 flex-1 text-sm leading-relaxed text-ink-600">{{ $service->summary }}</p>
                    <span class="mt-4 inline-flex items-center gap-1.5 text-sm font-semibold text-accent-700">
                        Read more
                        <x-icon name="arrow-right" class="h-4 w-4 transition group-hover:translate-x-0.5" />
                    </span>
                </a>
            @empty
                <p class="bg-white p-6 text-sm text-ink-600">Services will appear here once they have been added in the admin panel.</p>
            @endforelse
        </div>
    </section>

    {{-- How tracking works --}}
    <section class="border-y border-ink-100 bg-ink-50">
        <div class="mx-auto grid max-w-6xl gap-10 px-6 py-16 lg:grid-cols-[1.1fr_0.9fr] lg:py-20">
            <div>
                <h2 class="text-2xl font-semibold sm:text-3xl">How tracking works</h2>
                <ol class="mt-8 space-y-6">
                    @foreach ($trackingSteps as $index => $step)
                        <li class="flex gap-4">
                            <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-ink-300 font-mono text-sm text-ink-700">
                                {{ $index + 1 }}
                            </span>
                            <div>
                                <h3 class="font-display text-base font-semibold">{{ $step['title'] }}</h3>
                                <p class="mt-1 text-sm leading-relaxed text-ink-600">{{ $step['body'] }}</p>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </div>

            <div class="rounded border border-ink-200 bg-white p-6">
                <h3 class="font-display text-lg font-semibold">Check a shipment now</h3>
                <p class="mt-2 text-sm text-ink-600">Tracking numbers look like {{ app(\App\Services\TrackingNumberGenerator::class)->example() }}.</p>
                <x-track-form class="mt-5" :example="app(\App\Services\TrackingNumberGenerator::class)->example()" />
                <p class="mt-4 text-sm text-ink-600">
                    Lost your number? <a href="{{ route('contact.create') }}" class="font-medium text-accent-700 underline underline-offset-2">Contact the operations desk</a>
                    and we will look it up against your booking.
                </p>
            </div>
        </div>
    </section>

    {{-- Why clients choose us --}}
    @if (filled($whyPoints))
        <section class="mx-auto max-w-6xl px-6 py-16 sm:py-20">
            <h2 class="text-2xl font-semibold sm:text-3xl">{{ setting('home.why_heading') }}</h2>

            <div class="mt-10 grid gap-x-10 gap-y-8 sm:grid-cols-2">
                @foreach ($whyPoints as $point)
                    <div class="border-t border-ink-200 pt-5">
                        <h3 class="font-display text-base font-semibold">{{ $point['title'] ?? '' }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-ink-600">{{ $point['body'] ?? '' }}</p>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Destinations --}}
    @if (filled($destinations))
        <section class="bg-ink-950 text-white">
            <div class="mx-auto max-w-6xl px-6 py-16 sm:py-20">
                <div class="max-w-2xl">
                    <h2 class="text-2xl font-semibold text-white sm:text-3xl">{{ setting('home.destinations_heading') }}</h2>
                    <p class="mt-3 text-ink-300">{{ setting('home.destinations_intro') }}</p>
                </div>

                <dl class="mt-10 grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($destinations as $destination)
                        <div class="border-t border-ink-700 pt-5">
                            <dt class="font-display text-base font-semibold text-white">{{ $destination['title'] ?? '' }}</dt>
                            <dd class="mt-2 text-sm leading-relaxed text-ink-300">{{ $destination['body'] ?? '' }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        </section>
    @endif

    {{-- Reviews --}}
    @if ($reviews->isNotEmpty())
        <section class="mx-auto max-w-6xl px-6 py-16 sm:py-20">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <h2 class="text-2xl font-semibold sm:text-3xl">What clients say</h2>
                <a href="{{ route('reviews') }}" class="text-sm font-semibold text-accent-700 underline underline-offset-2">All reviews</a>
            </div>

            <div class="mt-10 grid gap-6 lg:grid-cols-3">
                @foreach ($reviews as $review)
                    <x-review-card :review="$review" />
                @endforeach
            </div>
        </section>
    @endif

    {{-- FAQ --}}
    @if ($faqs->isNotEmpty())
        <section class="border-t border-ink-100 bg-sand-100">
            <div class="mx-auto grid max-w-6xl gap-10 px-6 py-16 lg:grid-cols-[0.8fr_1.2fr] lg:py-20">
                <div>
                    <h2 class="text-2xl font-semibold sm:text-3xl">Common questions</h2>
                    <p class="mt-3 text-ink-600">More detail on bookings, documents and transit times.</p>
                    <a href="{{ route('faq') }}" class="mt-5 inline-flex items-center gap-1.5 text-sm font-semibold text-accent-700 underline underline-offset-2">
                        Read all questions
                    </a>
                </div>

                <div class="divide-y divide-ink-200 border-y border-ink-200">
                    @foreach ($faqs as $faq)
                        <details class="group py-4" @if ($loop->first) open @endif>
                            <summary class="flex cursor-pointer items-center justify-between gap-4 font-display text-base font-semibold marker:content-none">
                                {{ $faq->question }}
                                <x-icon name="chevron-down" class="h-4 w-4 shrink-0 text-ink-500 transition group-open:rotate-180" />
                            </summary>
                            <div class="copy mt-3 text-sm">{!! \App\Support\ContentFormatter::render($faq->answer) !!}</div>
                        </details>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Closing call to action --}}
    <section class="mx-auto max-w-6xl px-6 py-16 sm:py-20">
        <div class="flex flex-col items-start justify-between gap-6 rounded border border-ink-200 bg-white p-8 sm:flex-row sm:items-center">
            <div class="max-w-xl">
                <h2 class="text-2xl font-semibold">{{ setting('home.cta_heading') }}</h2>
                <p class="mt-2 text-ink-600">{{ setting('home.cta_body') }}</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('quote.create') }}" class="btn btn-primary">Request a quote</a>
                <a href="{{ route('contact.create') }}" class="btn btn-outline">Contact us</a>
            </div>
        </div>
    </section>
</x-layouts.public>
