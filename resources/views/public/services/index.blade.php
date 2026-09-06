<x-layouts.public :metaTitle="$metaTitle" :metaDescription="$metaDescription">
    <x-page-header
        eyebrow="Services"
        title="Freight services"
        intro="Sea and air freight, customs clearance, warehousing and final delivery, coordinated by one team." />

    <div class="mx-auto max-w-6xl space-y-px bg-ink-100 px-6 py-14 sm:py-16">
        @foreach ($services as $service)
            @php $image = \App\Services\MediaService::url($service->image_path); @endphp

            <article @class([
                'grid gap-8 bg-white p-6 sm:p-8',
                'lg:grid-cols-[1fr_1.6fr]' => $image,
            ])>
                @if ($image)
                    <div>
                        <img src="{{ $image }}" alt="{{ $service->image_alt ?: $service->title }}"
                             class="aspect-[4/3] w-full rounded object-cover" loading="lazy">
                    </div>
                @endif

                <div>
                    <h2 class="flex items-center gap-3 font-display text-xl font-semibold">
                        @unless ($image)
                            <x-icon :name="$service->icon" class="h-6 w-6 shrink-0 text-accent-600" />
                        @endunless
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
