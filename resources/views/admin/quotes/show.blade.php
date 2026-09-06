<x-layouts.admin :title="'Quote request '.$quote->reference">
    <div class="grid gap-6 lg:grid-cols-[1.3fr_0.7fr]">
        <x-admin.panel title="Request">
            <dl class="divide-y divide-ink-50">
                @foreach ([
                    'Name' => $quote->name,
                    'Company' => $quote->company,
                    'Email' => $quote->email,
                    'Phone' => $quote->phone,
                    'Origin' => $quote->origin,
                    'Destination' => $quote->destination,
                    'Method' => $quote->shipping_method?->label(),
                    'Cargo type' => $quote->cargo_type,
                    'Approximate weight' => $quote->approximate_weight,
                    'Packages' => $quote->package_count,
                    'Cargo ready' => $quote->ready_date?->format('j M Y'),
                    'Received' => $quote->created_at->format('j M Y, H:i'),
                ] as $label => $detail)
                    @if (filled($detail))
                        <div class="flex justify-between gap-4 py-2 text-sm">
                            <dt class="text-ink-500">{{ $label }}</dt>
                            <dd class="text-right font-medium">{{ $detail }}</dd>
                        </div>
                    @endif
                @endforeach
            </dl>

            @if ($quote->message)
                <div class="mt-4 border-t border-ink-50 pt-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-ink-500">Message</p>
                    <p class="mt-1 whitespace-pre-line text-sm text-ink-700">{{ $quote->message }}</p>
                </div>
            @endif

            <div class="mt-5 flex flex-wrap gap-2">
                <a href="mailto:{{ $quote->email }}?subject=Quotation%20{{ $quote->reference }}" class="btn btn-outline btn-sm">Reply by email</a>
            </div>
        </x-admin.panel>

        @can('quotes.manage')
            <x-admin.panel title="Handling">
                <form method="POST" action="{{ route('admin.quotes.update', $quote) }}" class="space-y-5">
                    @csrf
                    @method('PUT')

                    <x-form.field name="status" label="Status" :required="true">
                        <select id="status" name="status" required class="select">
                            @foreach ($statuses as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', $quote->status->value) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </x-form.field>

                    <x-form.field name="internal_notes" label="Internal notes" help="Never shown to the customer.">
                        <textarea id="internal_notes" name="internal_notes" rows="6" maxlength="5000" class="textarea">{{ old('internal_notes', $quote->internal_notes) }}</textarea>
                    </x-form.field>

                    <button type="submit" class="btn btn-primary btn-sm">Save</button>
                </form>

                @if ($quote->handler)
                    <p class="mt-4 border-t border-ink-50 pt-3 text-xs text-ink-500">
                        Last handled by {{ $quote->handler->name }} {{ $quote->handled_at?->diffForHumans() }}
                    </p>
                @endif
            </x-admin.panel>
        @endcan
    </div>
</x-layouts.admin>
