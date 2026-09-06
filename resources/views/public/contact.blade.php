<x-layouts.public :metaTitle="$metaTitle" :metaDescription="$metaDescription">
    <x-page-header eyebrow="Contact" title="Contact us" :intro="setting('contact.response_note')" />

    <div class="mx-auto grid max-w-5xl gap-10 px-6 py-12 lg:grid-cols-[1.2fr_0.8fr] lg:py-16">
        <div>
            @if (session('status'))
                <x-alert class="mb-6">{{ session('status') }}</x-alert>
            @endif

            @if ($errors->any())
                <x-alert type="error" class="mb-6">Please check the highlighted fields and try again.</x-alert>
            @endif

            <form method="POST" action="{{ route('contact.store') }}" class="space-y-5">
                @csrf
                <x-form.honeypot />

                <div class="grid gap-5 sm:grid-cols-2">
                    <x-form.field name="name" label="Full name" :required="true">
                        <input type="text" id="name" name="name" value="{{ old('name') }}" required maxlength="120" class="input">
                    </x-form.field>

                    <x-form.field name="email" label="Email address" :required="true">
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required maxlength="180" class="input">
                    </x-form.field>

                    <x-form.field name="phone" label="Telephone">
                        <input type="tel" id="phone" name="phone" value="{{ old('phone') }}" maxlength="40" class="input">
                    </x-form.field>

                    <x-form.field name="subject" label="Subject" :required="true">
                        <input type="text" id="subject" name="subject" value="{{ old('subject') }}" required maxlength="180" class="input">
                    </x-form.field>
                </div>

                <x-form.field name="message" label="Message" :required="true">
                    <textarea id="message" name="message" rows="6" required minlength="10" maxlength="4000" class="textarea">{{ old('message') }}</textarea>
                </x-form.field>

                <button type="submit" class="btn btn-primary">Send message</button>
            </form>
        </div>

        <aside class="space-y-6">
            @php
                $addressLines = collect([
                    setting('contact.address_line_1'),
                    setting('contact.address_line_2'),
                    collect([setting('contact.city'), setting('contact.region'), setting('contact.postal_code')])->filter()->implode(', '),
                    setting('contact.country'),
                ])->filter();
                $phone = setting('contact.phone');
                $email = setting('contact.email');
                $operationsEmail = setting('contact.operations_email') ?: $email;
            @endphp

            <div class="border border-ink-100 bg-ink-50 p-6">
                <h2 class="font-display text-base font-semibold">Operations desk</h2>

                @if ($addressLines->isNotEmpty())
                    <address class="mt-3 space-y-1 text-sm not-italic text-ink-700">
                        @foreach ($addressLines as $line)
                            <p>{{ $line }}</p>
                        @endforeach
                    </address>
                @endif

                @if ($phone || $email || $operationsEmail)
                    <div class="mt-4 space-y-1.5 text-sm">
                        @if ($phone)
                            <p class="inline-flex items-center gap-2"><x-icon name="phone" class="h-4 w-4 text-ink-400" />
                                <a href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}" class="font-medium text-accent-700 hover:underline">{{ $phone }}</a>
                            </p>
                        @endif
                        @if ($email)
                            <p class="inline-flex items-center gap-2"><x-icon name="mail" class="h-4 w-4 text-ink-400" />
                                <a href="mailto:{{ $email }}" class="font-medium text-accent-700 hover:underline">{{ $email }}</a>
                            </p>
                        @endif
                        @if ($operationsEmail && $operationsEmail !== $email)
                            <p class="inline-flex items-center gap-2"><x-icon name="container" class="h-4 w-4 text-ink-400" />
                                <a href="mailto:{{ $operationsEmail }}" class="font-medium text-accent-700 hover:underline">{{ $operationsEmail }}</a>
                            </p>
                        @endif
                    </div>
                @else
                    <p class="mt-3 text-sm leading-relaxed text-ink-600">
                        Use the form on this page and a coordinator will reply by email. Our published telephone and
                        postal details will appear here once they are confirmed.
                    </p>
                @endif
            </div>

            <div class="border border-ink-100 p-6">
                <h2 class="font-display text-base font-semibold">Business hours</h2>
                <dl class="mt-3 space-y-1.5 text-sm text-ink-700">
                    <div class="flex justify-between gap-4"><dt>Monday to Friday</dt><dd>{{ setting('contact.hours_weekdays') }}</dd></div>
                    <div class="flex justify-between gap-4"><dt>Saturday</dt><dd>{{ setting('contact.hours_saturday') }}</dd></div>
                    <div class="flex justify-between gap-4"><dt>Sunday</dt><dd>{{ setting('contact.hours_sunday') }}</dd></div>
                </dl>
                @if ($note = setting('contact.hours_note'))
                    <p class="mt-3 text-xs text-ink-500">{{ ($tz = setting('contact.timezone')) ? rtrim($note, '.').' ('.$tz.').' : $note }}</p>
                @endif
            </div>

            <div class="border border-ink-100 p-6">
                <h2 class="font-display text-base font-semibold">Existing shipment?</h2>
                <p class="mt-2 text-sm leading-relaxed text-ink-600">
                    The fastest route is the tracking page, where you can message the team handling your file directly.
                </p>
                <a href="{{ route('track.index') }}" class="btn btn-outline btn-sm mt-4">Track a shipment</a>
            </div>
        </aside>
    </div>
</x-layouts.public>
