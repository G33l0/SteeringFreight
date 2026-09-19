<x-layouts.public :metaTitle="$metaTitle" :metaDescription="$metaDescription">
    <x-page-header
        eyebrow="Quotations"
        title="Request a quote"
        :intro="setting('quotes.intro')" />

    <div class="mx-auto grid max-w-5xl gap-10 px-gutter py-section-sm lg:grid-cols-[1.3fr_0.7fr]">
        <div>
            @if (session('status'))
                <x-alert class="mb-6">{{ session('status') }}</x-alert>
            @endif

            @if ($errors->any())
                <x-alert type="error" class="mb-6">Please check the highlighted fields and try again.</x-alert>
            @endif

            <form method="POST" action="{{ route('quote.store') }}" class="space-y-8">
                @csrf
                <x-form.honeypot />

                {{-- 1. Who is asking --}}
                <fieldset class="space-y-5">
                    <legend class="font-display text-base font-semibold text-ink-950">
                        <span class="mr-1.5 font-mono text-sm text-accent-700">1</span> Your details
                    </legend>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-form.field name="name" label="Full name" :required="true">
                            <input type="text" id="name" name="name" value="{{ old('name') }}" required maxlength="120" class="input">
                        </x-form.field>

                        <x-form.field name="company" label="Company">
                            <input type="text" id="company" name="company" value="{{ old('company') }}" maxlength="160" class="input">
                        </x-form.field>

                        <x-form.field name="email" label="Email address" :required="true"
                                      help="We send the quotation to this address.">
                            <input type="email" id="email" name="email" value="{{ old('email') }}" required maxlength="180" class="input">
                        </x-form.field>

                        <x-form.field name="phone" label="Telephone">
                            <input type="tel" id="phone" name="phone" value="{{ old('phone') }}" maxlength="40" class="input">
                        </x-form.field>
                    </div>
                </fieldset>

                {{-- 2. Route --}}
                <fieldset class="space-y-5 border-t border-ink-100 pt-8">
                    <legend class="font-display text-base font-semibold text-ink-950">
                        <span class="mr-1.5 font-mono text-sm text-accent-700">2</span> Route
                    </legend>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-form.field name="origin_country" label="Collection country" :required="true">
                            <x-form.country-select name="origin_country" :countries="$countries" :frequent="$frequentCountries"
                                                   :required="true" placeholder="Where is the cargo now?" />
                        </x-form.field>

                        <x-form.field name="origin_city" label="Collection city or port">
                            <input type="text" id="origin_city" name="origin_city" value="{{ old('origin_city') }}"
                                   maxlength="120" class="input" placeholder="For example Ningbo">
                        </x-form.field>

                        <x-form.field name="destination_country" label="Delivery country" :required="true">
                            <x-form.country-select name="destination_country" :countries="$countries" :frequent="$frequentCountries"
                                                   :required="true" placeholder="Where does it need to go?" />
                        </x-form.field>

                        <x-form.field name="destination_city" label="Delivery city or port">
                            <input type="text" id="destination_city" name="destination_city" value="{{ old('destination_city') }}"
                                   maxlength="120" class="input" placeholder="For example Lagos">
                        </x-form.field>

                        <x-form.field name="shipping_method" label="Preferred method"
                                      help="Not sure? Leave it and we will quote the sensible options.">
                            <select id="shipping_method" name="shipping_method" class="select">
                                <option value="">No preference</option>
                                @foreach ($methods as $value => $label)
                                    <option value="{{ $value }}" @selected(old('shipping_method') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </x-form.field>

                        <x-form.field name="incoterm" label="Incoterm" help="If your supplier has quoted one.">
                            <select id="incoterm" name="incoterm" class="select">
                                <option value="">Not sure</option>
                                @foreach ($incoterms as $incoterm)
                                    <option value="{{ $incoterm }}" @selected(old('incoterm') === $incoterm)>{{ $incoterm }}</option>
                                @endforeach
                            </select>
                        </x-form.field>
                    </div>
                </fieldset>

                {{-- 3. Cargo --}}
                <fieldset class="space-y-5 border-t border-ink-100 pt-8">
                    <legend class="font-display text-base font-semibold text-ink-950">
                        <span class="mr-1.5 font-mono text-sm text-accent-700">3</span> Cargo
                    </legend>

                    <x-form.field name="cargo_type" label="Type of goods" :required="true"
                                  help="For example: packaging machinery spares, cotton textiles, ceramic tiles.">
                        <input type="text" id="cargo_type" name="cargo_type" value="{{ old('cargo_type') }}" required maxlength="160" class="input">
                    </x-form.field>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-form.field name="approximate_weight" label="Gross weight" help="For example 2,400 kg or 12 tonnes.">
                            <input type="text" id="approximate_weight" name="approximate_weight" value="{{ old('approximate_weight') }}" maxlength="60" class="input">
                        </x-form.field>

                        <x-form.field name="package_count" label="Number of packages">
                            <input type="number" id="package_count" name="package_count" value="{{ old('package_count') }}" min="1" max="100000" class="input">
                        </x-form.field>

                        <x-form.field name="dimensions" label="Dimensions" help="Pallet or carton sizes, and whether they stack.">
                            <input type="text" id="dimensions" name="dimensions" value="{{ old('dimensions') }}" maxlength="160" class="input"
                                   placeholder="8 pallets, 120 x 100 x 145 cm">
                        </x-form.field>

                        <x-form.field name="goods_value" label="Commercial value" help="Used for insurance and customs, not for the freight rate.">
                            <input type="text" id="goods_value" name="goods_value" value="{{ old('goods_value') }}" maxlength="60" class="input"
                                   placeholder="USD 18,000">
                        </x-form.field>

                        <x-form.field name="ready_date" label="Cargo ready date" class="sm:col-span-2">
                            <input type="date" id="ready_date" name="ready_date" value="{{ old('ready_date') }}" class="input sm:max-w-xs">
                        </x-form.field>
                    </div>

                    <x-form.field name="message" label="Anything else we should know?"
                                  help="Dangerous goods, temperature control, delivery restrictions, a deadline.">
                        <textarea id="message" name="message" rows="5" maxlength="4000" class="textarea">{{ old('message') }}</textarea>
                    </x-form.field>
                </fieldset>

                <div class="border-t border-ink-100 pt-6">
                    <button type="submit" class="btn btn-primary">Send request</button>

                    <p class="mt-4 text-xs leading-relaxed text-ink-500">
                        We use your details to prepare the quotation and to contact you about it. See our
                        <a href="{{ route('privacy') }}" class="underline underline-offset-2">privacy policy</a>.
                    </p>
                </div>
            </form>
        </div>

        <aside class="space-y-6">
            <div class="border border-ink-100 bg-ink-50 p-6">
                <h2 class="font-display text-base font-semibold">What helps us quote quickly</h2>
                <ul class="mt-4 space-y-2.5 text-sm text-ink-700">
                    <li class="flex items-start gap-2"><x-icon name="check" class="mt-0.5 h-4 w-4 shrink-0 text-accent-600" />Collection and delivery points, or at least the cities</li>
                    <li class="flex items-start gap-2"><x-icon name="check" class="mt-0.5 h-4 w-4 shrink-0 text-accent-600" />Gross weight with pallet or carton dimensions</li>
                    <li class="flex items-start gap-2"><x-icon name="check" class="mt-0.5 h-4 w-4 shrink-0 text-accent-600" />Commodity description, and the HS code if you have it</li>
                    <li class="flex items-start gap-2"><x-icon name="check" class="mt-0.5 h-4 w-4 shrink-0 text-accent-600" />Whether the cargo stacks or needs temperature control</li>
                </ul>
            </div>

            <div class="border border-ink-100 p-6">
                <h2 class="font-display text-base font-semibold">What happens next</h2>
                <ol class="mt-4 space-y-3 text-sm text-ink-600">
                    <li class="flex gap-3">
                        <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full border border-ink-300 font-mono text-xs">1</span>
                        Your request reaches our operations desk straight away and is given a reference.
                    </li>
                    <li class="flex gap-3">
                        <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full border border-ink-300 font-mono text-xs">2</span>
                        A coordinator checks the route and comes back with a rate and transit time by email.
                    </li>
                    <li class="flex gap-3">
                        <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full border border-ink-300 font-mono text-xs">3</span>
                        Accept it and we book the space and open the file, with tracking from day one.
                    </li>
                </ol>
            </div>

            @if (($phone = setting('contact.phone')) || ($email = setting('contact.email')))
                <div class="border border-ink-100 p-6">
                    <h2 class="font-display text-base font-semibold">Prefer to talk?</h2>
                    <p class="mt-2 text-sm leading-relaxed text-ink-600">Our desk is open {{ \App\Support\BusinessHours::sentence() }}.</p>
                    @if ($phone ?? false)
                        <p class="mt-2 text-sm"><a href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}" class="font-medium text-accent-700 hover:underline">{{ $phone }}</a></p>
                    @endif
                    @if ($email ?? false)
                        <p class="text-sm"><a href="mailto:{{ $email }}" class="font-medium text-accent-700 hover:underline">{{ $email }}</a></p>
                    @endif
                </div>
            @endif
        </aside>
    </div>
</x-layouts.public>
