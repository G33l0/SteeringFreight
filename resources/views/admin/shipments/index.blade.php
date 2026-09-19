<x-layouts.admin :title="$archived ? 'Archived shipments' : 'Shipments'">
    <x-slot:actions>
        @can('shipments.manage')
            <a href="{{ route('admin.shipments.create') }}" class="btn btn-primary btn-sm">
                <x-icon name="plus" class="h-4 w-4" />New shipment
            </a>
        @endcan
        <a href="{{ $archived ? route('admin.shipments.index') : route('admin.shipments.archived') }}" class="btn btn-outline btn-sm">
            {{ $archived ? 'Active shipments' : 'Archived' }}
        </a>
    </x-slot:actions>

    <x-admin.panel compact>
        <form method="GET" class="grid gap-3 border-b border-ink-100 p-4 sm:grid-cols-2 lg:grid-cols-6">
            <div class="lg:col-span-2">
                <label for="q" class="label">Search</label>
                <input type="search" id="q" name="q" value="{{ $filters['q'] }}" class="input"
                       placeholder="Tracking number, customer, origin, destination">
            </div>

            <div>
                <label for="status" class="label">Status</label>
                <select id="status" name="status" class="select">
                    <option value="">All</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->id }}" @selected((int) $filters['status'] === $status->id)>{{ $status->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="method" class="label">Method</label>
                <select id="method" name="method" class="select">
                    <option value="">All</option>
                    @foreach ($methods as $value => $label)
                        <option value="{{ $value }}" @selected($filters['method'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="from" class="label">Created from</label>
                <input type="date" id="from" name="from" value="{{ $filters['from'] }}" class="input">
            </div>

            <div>
                <label for="to" class="label">Created to</label>
                <input type="date" id="to" name="to" value="{{ $filters['to'] }}" class="input">
            </div>

            <div class="flex items-end gap-2 sm:col-span-2 lg:col-span-6">
                <div>
                    <label for="sort" class="label">Sort</label>
                    <select id="sort" name="sort" class="select">
                        <option value="newest" @selected($filters['sort'] === 'newest')>Newest first</option>
                        <option value="oldest" @selected($filters['sort'] === 'oldest')>Oldest first</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-dark btn-sm">Apply</button>
                <a href="{{ $archived ? route('admin.shipments.archived') : route('admin.shipments.index') }}" class="btn btn-outline btn-sm">Reset</a>
            </div>
        </form>

        @if ($shipments->isEmpty())
            <x-admin.empty message="No shipments match these filters." />
        @else
            <div class="table-scroll">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Tracking number</th>
                            <th>Customer</th>
                            <th>Route</th>
                            <th>Method</th>
                            <th>Status</th>
                            <th>Updated</th>
                            <th><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($shipments as $shipment)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.shipments.show', $shipment) }}" class="font-mono font-medium text-accent-700 hover:underline">
                                        {{ $shipment->tracking_number }}
                                    </a>
                                    @if ($shipment->is_sample)
                                        <span class="badge badge-slate ml-1">Sample</span>
                                    @endif
                                </td>
                                <td>
                                    {{ $shipment->customer?->name ?? $shipment->customer_name ?? '—' }}
                                    @if ($shipment->customer?->company)
                                        <span class="block text-xs text-ink-500">{{ $shipment->customer->company }}</span>
                                    @endif
                                </td>
                                <td class="text-ink-600">{{ $shipment->routeLabel() ?: '—' }}</td>
                                <td class="text-ink-600">{{ $shipment->shipping_method?->label() ?? '—' }}</td>
                                <td><x-status-badge :status="$shipment->status" /></td>
                                <td class="whitespace-nowrap text-ink-500">{{ $shipment->updated_at->format('j M Y') }}</td>
                                <td class="text-right">
                                    <a href="{{ route('admin.shipments.show', $shipment) }}" class="text-sm font-medium text-ink-600 hover:text-accent-700">Open</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="p-4">
                {{ $shipments->links('components.pagination') }}
            </div>
        @endif
    </x-admin.panel>
</x-layouts.admin>
