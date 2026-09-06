<x-layouts.admin title="Quote requests">
    <div class="mb-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <x-admin.stat label="New" :value="$counts['new']" :tone="$counts['new'] > 0 ? 'caution' : 'default'" />
        <x-admin.stat label="Awaiting a quotation" :value="$counts['awaiting_reply']" :tone="$counts['awaiting_reply'] > 0 ? 'alert' : 'default'" />
    </div>

    <x-admin.panel compact>
        <form method="GET" class="grid gap-3 border-b border-ink-100 p-4 sm:grid-cols-2 lg:grid-cols-6">
            <div class="lg:col-span-2">
                <label for="q" class="label">Search</label>
                <input type="search" id="q" name="q" value="{{ $filters['q'] }}" class="input" placeholder="Name, email, route or reference">
            </div>
            <div>
                <label for="status" class="label">Status</label>
                <select id="status" name="status" class="select">
                    <option value="">All</option>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="country" class="label">Country</label>
                <select id="country" name="country" class="select">
                    <option value="">Any</option>
                    @foreach ($countries as $country)
                        <option value="{{ $country }}" @selected($filters['country'] === $country)>{{ $country }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="from" class="label">From</label>
                <input type="date" id="from" name="from" value="{{ $filters['from'] }}" class="input">
            </div>
            <div>
                <label for="to" class="label">To</label>
                <input type="date" id="to" name="to" value="{{ $filters['to'] }}" class="input">
            </div>
            <div class="flex items-end gap-2 lg:col-span-6">
                <button type="submit" class="btn btn-dark btn-sm">Apply</button>
                <a href="{{ route('admin.quotes.index') }}" class="btn btn-outline btn-sm">Reset</a>
            </div>
        </form>

        @if ($quotes->isEmpty())
            <x-admin.empty message="No quote requests match these filters." />
        @else
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead><tr><th>Reference</th><th>Name</th><th>Route</th><th>Method</th><th>Status</th><th>Quotation</th><th>Received</th></tr></thead>
                    <tbody>
                        @foreach ($quotes as $quote)
                            <tr>
                                <td><a href="{{ route('admin.quotes.show', $quote) }}" class="font-mono font-medium text-accent-700 hover:underline">{{ $quote->reference }}</a></td>
                                <td>{{ $quote->name }}<span class="block text-xs text-ink-500">{{ $quote->email }}</span></td>
                                <td class="text-ink-600">{{ $quote->origin }} to {{ $quote->destination }}</td>
                                <td class="text-ink-600">{{ $quote->shipping_method?->label() ?? '—' }}</td>
                                <td><span class="badge {{ $quote->status->value === 'new' ? 'badge-amber' : 'badge-slate' }}">{{ $quote->status->label() }}</span></td>
                                <td>
                                    @if ($quote->replies_count > 0)
                                        <span class="badge badge-green">Sent</span>
                                    @else
                                        <span class="badge badge-red">Not sent</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap text-ink-500">{{ $quote->created_at->format('j M Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="p-4">{{ $quotes->links('components.pagination') }}</div>
        @endif
    </x-admin.panel>
</x-layouts.admin>
