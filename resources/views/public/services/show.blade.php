<x-layouts.public :metaTitle="$metaTitle" :metaDescription="$metaDescription">
    <x-page-header eyebrow="Services" :title="$service->title" :intro="$service->summary" />

    <div class="mx-auto grid max-w-6xl gap-12 px-6 py-14 lg:grid-cols-[1.2fr_0.8fr] lg:py-16">
        <div>
            <img src="{{ $service->imageUrl() }}" alt="{{ $service->imageAlt() }}"
                 class="mb-8 aspect-[4/3] w-full rounded object-cover sm:aspect-[16/9]" width="800" height="600">

            <div class="copy">
                {!! \App\Support\ContentFormatter::render($service->description) !!}
            </div>

            @if ($faqs->isNotEmpty())
                <section class="mt-12">
                    <h2 class="font-display text-xl font-semibold">Questions about {{ strtolower($service->title) }}</h2>
                    <div class="mt-4 divide-y divide-ink-200 border-y border-ink-200">
                        @foreach ($faqs as $faq)
                            <details class="group py-4">
                                <summary class="flex cursor-pointer items-center justify-between gap-4 font-display text-base font-semibold marker:content-none">
                                    {{ $faq->question }}
                                    <x-icon name="chevron-down" class="h-4 w-4 shrink-0 text-ink-500 transition group-open:rotate-180" />
                                </summary>
                                <div class="copy mt-3 text-sm">{!! \App\Support\ContentFormatter::render($faq->answer) !!}</div>
                            </details>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>

        <aside class="space-y-8">
            @if (filled($service->highlights))
                <div class="border border-ink-100 bg-ink-50 p-6">
                    <h2 class="font-display text-base font-semibold">Included</h2>
                    <ul class="mt-4 space-y-2.5">
                        @foreach ($service->highlights as $highlight)
                            <li class="flex items-start gap-2 text-sm text-ink-700">
                                <x-icon name="check" class="mt-0.5 h-4 w-4 shrink-0 text-accent-600" />
                                {{ $highlight }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="border border-ink-100 p-6">
                <h2 class="font-display text-base font-semibold">Get a rate</h2>
                <p class="mt-2 text-sm leading-relaxed text-ink-600">
                    Send the route, cargo description and approximate weight. We reply with a rate and a transit time.
                </p>
                <a href="{{ route('quote.create') }}" class="btn btn-primary btn-sm mt-4 w-full">Request a quote</a>
            </div>

            @if ($related->isNotEmpty())
                <div class="border border-ink-100 p-6">
                    <h2 class="font-display text-base font-semibold">Other services</h2>
                    <ul class="mt-4 space-y-2 text-sm">
                        @foreach ($related as $item)
                            <li>
                                <a href="{{ route('services.show', $item) }}" class="inline-flex items-center gap-2 text-ink-700 hover:text-accent-700">
                                    <x-icon :name="$item->icon" class="h-4 w-4 text-accent-600" />
                                    {{ $item->title }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </aside>
    </div>
</x-layouts.public>
