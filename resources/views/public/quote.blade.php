<x-layouts.public :metaTitle="$metaTitle" :metaDescription="$metaDescription">
    <x-page-header
        eyebrow="Quotations"
        title="Request a quote"
        intro="Give us the route and a short description of the cargo. We will come back with a rate and a realistic transit time." />

    <div class="mx-auto grid max-w-5xl gap-10 px-6 py-12 lg:grid-cols-[1.3fr_0.7fr] lg:py-16">
        <div>
            @if (session('status'))
                <x-alert class="mb-6">{{ session('status') }}</x-alert>
            @endif

            @if ($errors->any())
                <x-alert type="error" class="mb-6">Please check the highlighted fields and try again.</x-alert>
            @endif

            <form method="POST" action="{{ route('quote.store') }}" class="space-y-5">
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

                    <x-form.field name="company" label="Company">
                        <input type="text" id="company" name="company" value="{{ old('company') }}" maxlength="160" class="input">
                    </x-form.field>

                    <x-form.field name="origin" label="Origin" :required="true" help="City and country where the cargo is now.">
                        <input type="text" id="origin" name="origin" value="{{ old('origin') }}" required maxlength="180" class="input">
                    </x-form.field>

                    <x-form.field name="destination" label="Destination" :required="true" help="Final delivery city and country.">
                        <input type="text" id="destination" name="destination" value="{{ old('destination') }}" required maxlength="180" class="input">
                    </x-form.field>

                    <x-form.field name="shipping_method" label="Preferred method">
                        <select id="shipping_method" name="shipping_method" class="select">
                            <option value="">No preference</option>
                            @foreach ($methods as $value => $label)
                                <option value="{{ $value }}" @selected(old('shipping_method') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </x-form.field>

                    <x-form.field name="cargo_type" label="Cargo type" help="For example: machinery parts, textiles, foodstuffs.">
                        <input type="text" id="cargo_type" name="cargo_type" value="{{ old('cargo_type') }}" maxlength="160" class="input">
                    </x-form.field>

                    <x-form.field name="approximate_weight" label="Approximate weight" help="For example: 2,400 kg or 12 tonnes.">
                        <input type="text" id="approximate_weight" name="approximate_weight" value="{{ old('approximate_weight') }}" maxlength="60" class="input">
                    </x-form.field>

                    <x-form.field name="package_count" label="Number of packages">
                        <input type="number" id="package_count" name="package_count" value="{{ old('package_count') }}" min="1" max="100000" class="input">
                    </x-form.field>

                    <x-form.field name="ready_date" label="Cargo ready date" class="sm:col-span-2">
                        <input type="date" id="ready_date" name="ready_date" value="{{ old('ready_date') }}" class="input sm:max-w-xs">
                    </x-form.field>
                </div>

                <x-form.field name="message" label="Anything else we should know?">
                    <textarea id="message" name="message" rows="5" maxlength="4000" class="textarea">{{ old('message') }}</textarea>
                </x-form.field>

                <button type="submit" class="btn btn-primary">Send request</button>

                <p class="text-xs leading-relaxed text-ink-500">
                    We use your details to prepare the quotation and to contact you about it. See our
                    <a href="{{ route('privacy') }}" class="underline underline-offset-2">privacy policy</a>.
                </p>
            </form>
        </div>

        <aside class="space-y-6">
            <div class="border border-ink-100 bg-ink-50 p-6">
                <h2 class="font-display text-base font-semibold">What helps us quote quickly</h2>
                <ul class="mt-4 space-y-2.5 text-sm text-ink-700">
                    <li class="flex items-start gap-2"><x-icon name="check" class="mt-0.5 h-4 w-4 shrink-0 text-accent-600" />Pickup and delivery addresses, or at least the cities</li>
                    <li class="flex items-start gap-2"><x-icon name="check" class="mt-0.5 h-4 w-4 shrink-0 text-accent-600" />Gross weight and pallet or carton dimensions</li>
                    <li class="flex items-start gap-2"><x-icon name="check" class="mt-0.5 h-4 w-4 shrink-0 text-accent-600" />Commodity description and HS code if you have it</li>
                    <li class="flex items-start gap-2"><x-icon name="check" class="mt-0.5 h-4 w-4 shrink-0 text-accent-600" />Whether the cargo is stackable or needs temperature control</li>
                </ul>
            </div>

            <div class="border border-ink-100 p-6">
                <h2 class="font-display text-base font-semibold">Prefer to talk?</h2>
                <p class="mt-2 text-sm leading-relaxed text-ink-600">Our desk is open {{ setting('contact.hours_weekdays') }} on weekdays.</p>
                @if ($phone = setting('contact.phone'))
                    <p class="mt-2 text-sm"><a href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}" class="font-medium text-accent-700 hover:underline">{{ $phone }}</a></p>
                @endif
                @if ($email = setting('contact.email'))
                    <p class="text-sm"><a href="mailto:{{ $email }}" class="font-medium text-accent-700 hover:underline">{{ $email }}</a></p>
                @endif
            </div>
        </aside>
    </div>
</x-layouts.public>
