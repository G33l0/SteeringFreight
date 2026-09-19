<x-layouts.public :metaTitle="$metaTitle" :metaDescription="$metaDescription">
    <x-page-header
        eyebrow="Help"
        title="Frequently asked questions"
        intro="Bookings, documents, transit times and tracking, answered in plain language." />

    <div class="mx-auto max-w-3xl px-gutter py-section-sm">
        @forelse ($groups as $category => $faqs)
            <section class="mb-12 last:mb-0">
                <h2 class="font-display text-lg font-semibold">{{ $category }}</h2>
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
        @empty
            <p class="text-ink-600">No questions have been published yet.</p>
        @endforelse

        <div class="mt-12 border border-ink-100 bg-ink-50 p-6">
            <h2 class="font-display text-base font-semibold">Still need an answer?</h2>
            <p class="mt-2 text-sm text-ink-600">Send the question to our operations desk and we will reply during business hours.</p>
            <a href="{{ route('contact.create') }}" class="btn btn-outline btn-sm mt-4">Contact us</a>
        </div>
    </div>
</x-layouts.public>
