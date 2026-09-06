<x-layouts.public :metaTitle="$metaTitle" :metaDescription="$metaDescription">
    <x-page-header :title="$page->title" :intro="$page->intro" />

    <article class="mx-auto max-w-3xl px-6 py-12 sm:py-16">
        <div class="copy">
            {!! \App\Support\ContentFormatter::render($page->body) !!}
        </div>

        @if ($page->updated_at)
            <p class="mt-10 border-t border-ink-100 pt-5 text-sm text-ink-500">
                Last updated {{ $page->updated_at->format('j F Y') }}.
            </p>
        @endif
    </article>
</x-layouts.public>
