@props(['code', 'title', 'message'])

<x-layouts.public :metaTitle="$code.' — '.company_name()" robots="noindex, nofollow">
    <div class="mx-auto flex min-h-[55vh] max-w-2xl flex-col justify-center px-gutter py-section">
        <p class="font-mono text-sm font-semibold uppercase tracking-[0.2em] text-accent-700">Error {{ $code }}</p>
        <h1 class="mt-3 text-heading font-semibold">{{ $title }}</h1>
        <p class="mt-4 leading-relaxed text-ink-600">{{ $message }}</p>

        <div class="mt-8 flex flex-wrap gap-3">
            <a href="{{ route('home') }}" class="btn btn-primary">Back to the homepage</a>
            <a href="{{ route('track.index') }}" class="btn btn-outline">Track a shipment</a>
            <a href="{{ route('contact.create') }}" class="btn btn-outline">Contact us</a>
        </div>
    </div>
</x-layouts.public>
