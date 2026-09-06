@php
    $footerServices = \App\Models\Service::published()->ordered()->limit(6)->get(['title', 'slug']);
    $address = collect([
        setting('contact.address_line_1'),
        setting('contact.address_line_2'),
        collect([setting('contact.city'), setting('contact.region'), setting('contact.postal_code')])->filter()->implode(', '),
        setting('contact.country'),
    ])->filter();
    $phone = setting('contact.phone');
    $email = setting('contact.email');
@endphp

<footer class="mt-20 border-t border-ink-800 bg-ink-950 text-ink-200">
    <div class="mx-auto grid max-w-6xl gap-10 px-6 py-14 sm:grid-cols-2 lg:grid-cols-4">
        <div>
            <x-logo tone="light" />
            <p class="mt-4 text-sm leading-relaxed text-ink-300">
                {{ \Illuminate\Support\Str::of((string) setting('company.intro'))->stripTags()->explode("\n")->filter()->first() }}
            </p>
            @if (setting('seo.linkedin_url') || setting('seo.facebook_url'))
                <div class="mt-4 flex gap-4 text-sm">
                    @if ($url = setting('seo.linkedin_url'))
                        <a href="{{ $url }}" rel="noopener noreferrer" target="_blank" class="text-ink-300 underline-offset-2 hover:text-white hover:underline">LinkedIn</a>
                    @endif
                    @if ($url = setting('seo.facebook_url'))
                        <a href="{{ $url }}" rel="noopener noreferrer" target="_blank" class="text-ink-300 underline-offset-2 hover:text-white hover:underline">Facebook</a>
                    @endif
                </div>
            @endif
        </div>

        <div>
            <h2 class="font-display text-sm font-semibold uppercase tracking-wide text-white">Services</h2>
            <ul class="mt-4 space-y-2 text-sm">
                @foreach ($footerServices as $footerService)
                    <li><a href="{{ route('services.show', $footerService) }}" class="text-ink-300 hover:text-white">{{ $footerService->title }}</a></li>
                @endforeach
            </ul>
        </div>

        <div>
            <h2 class="font-display text-sm font-semibold uppercase tracking-wide text-white">Quick links</h2>
            <ul class="mt-4 space-y-2 text-sm">
                <li><a href="{{ route('track.index') }}" class="text-ink-300 hover:text-white">Track a shipment</a></li>
                <li><a href="{{ route('quote.create') }}" class="text-ink-300 hover:text-white">Request a quote</a></li>
                <li><a href="{{ route('about') }}" class="text-ink-300 hover:text-white">About us</a></li>
                <li><a href="{{ route('faq') }}" class="text-ink-300 hover:text-white">FAQ</a></li>
                <li><a href="{{ route('reviews') }}" class="text-ink-300 hover:text-white">Client reviews</a></li>
                <li><a href="{{ route('contact.create') }}" class="text-ink-300 hover:text-white">Contact</a></li>
            </ul>
        </div>

        <div>
            <h2 class="font-display text-sm font-semibold uppercase tracking-wide text-white">Get in touch</h2>
            <address class="mt-4 space-y-2 text-sm not-italic text-ink-300">
                @foreach ($address as $line)
                    <p>{{ $line }}</p>
                @endforeach
                @if ($phone)
                    <p><a href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}" class="hover:text-white">{{ $phone }}</a></p>
                @endif
                @if ($email)
                    <p><a href="mailto:{{ $email }}" class="hover:text-white">{{ $email }}</a></p>
                @endif
            </address>

            <h2 class="mt-6 font-display text-sm font-semibold uppercase tracking-wide text-white">Business hours</h2>
            <dl class="mt-3 space-y-1 text-sm text-ink-300">
                <div class="flex justify-between gap-4"><dt>Monday to Friday</dt><dd>{{ setting('contact.hours_weekdays') }}</dd></div>
                <div class="flex justify-between gap-4"><dt>Saturday</dt><dd>{{ setting('contact.hours_saturday') }}</dd></div>
                <div class="flex justify-between gap-4"><dt>Sunday</dt><dd>{{ setting('contact.hours_sunday') }}</dd></div>
            </dl>
            @if ($note = setting('contact.hours_note'))
                <p class="mt-2 text-xs text-ink-400">{{ $note }}</p>
            @endif
        </div>
    </div>

    <div class="border-t border-ink-800">
        <div class="mx-auto flex max-w-6xl flex-col gap-3 px-6 py-5 text-xs text-ink-400 sm:flex-row sm:items-center sm:justify-between">
            <p>&copy; {{ date('Y') }} {{ setting('company.legal_name', company_name()) }}. All rights reserved.</p>
            <div class="flex gap-5">
                <a href="{{ route('privacy') }}" class="hover:text-white">Privacy Policy</a>
                <a href="{{ route('terms') }}" class="hover:text-white">Terms of Service</a>
                <a href="{{ route('admin.login') }}" class="hover:text-white">Staff login</a>
            </div>
        </div>
    </div>
</footer>
