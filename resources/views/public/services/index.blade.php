<x-layouts.public :metaTitle="$metaTitle" :metaDescription="$metaDescription">
    <x-page-header
        eyebrow="Services"
        title="Freight services"
        intro="Sea and air freight, customs clearance, warehousing and final delivery, coordinated by one team." />

    <div class="mx-auto max-w-6xl space-y-px bg-ink-100 px-6 py-14 sm:py-16">
        @foreach ($services as $service)
            <article class="grid gap-8 bg-white p-6 sm:p-8 lg:grid-cols-[1fr_1.6fr]">
                <div>
                    @if ($image = \App\Services\MediaService::url($service->image_path))
                        <img src="{{ $image }}" alt="{{ $service->image_alt ?: $service->title }}"
                             class="aspect-[4/3] w-full rounded object-cover" loading="lazy">
                    @else
                        <div class="flex aspect-[4/3] w-full items-center justify-center rounded bg-ink-50">
                            <x-icon :name="$service->icon" class="h-12 w-12 text-ink-300" />
                        </div>
                    @endif
                </div>

                <div>
                    <h2 class="font-display text-xl font-semibold">
                        <a href="{{ route('services.show', $service) }}" class="hover:text-accent-700">{{ $service->title }}</a>
                    </h2>
                    <p class="mt-3 leading-relaxed text-ink-600">{{ $service->summary }}</p>

                    @if (filled($service->highlights))
                        <ul class="mt-4 grid gap-2 sm:grid-cols-2">
                            @foreach ($service->highlights as $highlight)
                                <li class="flex items-start gap-2 text-sm text-ink-700">
                                    <x-icon name="check" class="mt-0.5 h-4 w-4 shrink-0 text-accent-600" />
                                    {{ $highlight }}
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    <a href="{{ route('services.show', $service) }}" class="mt-5 inline-flex items-center gap-1.5 text-sm font-semibold text-accent-700 underline underline-offset-2">
                        {{ $service->title }} in detail
                    </a>
                </div>
            </article>
        @endforeach
    </div>

    <x-cta-panel />
</x-layouts.public>
