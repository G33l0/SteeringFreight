<x-layouts.public :metaTitle="$metaTitle" :metaDescription="$metaDescription">
    <x-page-header
        eyebrow="About us"
        :title="$page?->title ?? 'About '.company_name()"
        :intro="$page?->intro ?? setting('company.tagline')" />

    <div class="mx-auto max-w-6xl px-6 pt-10">
        <img src="{{ asset('assets/illustrations/port-operations.svg') }}"
             alt="Container terminal with gantry cranes, stacked containers and a berthed vessel"
             class="w-full rounded object-cover" width="1200" height="500">
    </div>

    <div class="mx-auto grid max-w-6xl gap-12 px-6 py-14 lg:grid-cols-[1.2fr_0.8fr] lg:py-20">
        <div class="copy">
            @if ($page?->body)
                {!! \App\Support\ContentFormatter::render($page->body) !!}
            @else
                {!! \App\Support\ContentFormatter::render(setting('company.intro')) !!}
            @endif
        </div>

        <aside class="space-y-8">
            <div class="border border-ink-100 bg-ink-50 p-6">
                <h2 class="font-display text-base font-semibold">What we handle</h2>
                <ul class="mt-4 space-y-2 text-sm">
                    @foreach ($services as $service)
                        <li>
                            <a href="{{ route('services.show', $service) }}" class="inline-flex items-center gap-2 text-ink-700 hover:text-accent-700">
                                <x-icon :name="$service->icon" class="h-4 w-4 text-accent-600" />
                                {{ $service->title }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="border border-ink-100 p-6">
                <h2 class="font-display text-base font-semibold">Talk to us</h2>
                <p class="mt-2 text-sm leading-relaxed text-ink-600">
                    Tell us what you are shipping and where it needs to go. We will tell you what it costs and how long it takes.
                </p>
                <div class="mt-4 flex flex-wrap gap-2">
                    <a href="{{ route('quote.create') }}" class="btn btn-primary btn-sm">Request a quote</a>
                    <a href="{{ route('contact.create') }}" class="btn btn-outline btn-sm">Contact</a>
                </div>
            </div>
        </aside>
    </div>
</x-layouts.public>
