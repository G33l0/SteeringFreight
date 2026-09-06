<x-layouts.admin :title="$customer->name">
    <x-slot:actions>
        @can('customers.manage')
            <a href="{{ route('admin.customers.edit', $customer) }}" class="btn btn-outline btn-sm">Edit</a>
        @endcan
        @can('shipments.manage')
            <a href="{{ route('admin.shipments.create') }}" class="btn btn-primary btn-sm">New shipment</a>
        @endcan
    </x-slot:actions>

    <div class="grid gap-6 lg:grid-cols-[0.9fr_1.1fr]">
        <x-admin.panel title="Customer">
            <dl class="divide-y divide-ink-50">
                @foreach ([
                    'Reference' => $customer->reference,
                    'Company' => $customer->company,
                    'Email' => $customer->email,
                    'Phone' => $customer->phone,
                    'Address' => $customer->addressLines(),
                    'Update emails' => $customer->notifications_enabled ? 'Enabled' : 'Disabled',
                    'Added' => $customer->created_at->format('j M Y'),
                ] as $label => $detail)
                    @if (filled($detail))
                        <div class="flex justify-between gap-4 py-2 text-sm">
                            <dt class="text-ink-500">{{ $label }}</dt>
                            <dd class="whitespace-pre-line text-right font-medium">{{ $detail }}</dd>
                        </div>
                    @endif
                @endforeach
            </dl>

            @if ($customer->notes)
                <div class="mt-4 border-t border-ink-50 pt-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-ink-500">Internal notes</p>
                    <p class="mt-1 whitespace-pre-line text-sm text-ink-700">{{ $customer->notes }}</p>
                </div>
            @endif

            @can('customers.manage')
                @if ($customer->shipments->isEmpty())
                    <form method="POST" action="{{ route('admin.customers.destroy', $customer) }}" class="mt-5"
                          onsubmit="return confirm('Delete this customer?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline btn-sm">Delete customer</button>
                    </form>
                @endif
            @endcan
        </x-admin.panel>

        <x-admin.panel title="Shipments" compact>
            @if ($customer->shipments->isEmpty())
                <x-admin.empty message="This customer has no shipments yet." />
            @else
                <div class="overflow-x-auto">
                    <table class="data-table">
                        <thead><tr><th>Tracking number</th><th>Route</th><th>Status</th><th>Created</th></tr></thead>
                        <tbody>
                            @foreach ($customer->shipments as $shipment)
                                <tr>
                                    <td><a href="{{ route('admin.shipments.show', $shipment) }}" class="font-mono font-medium text-accent-700 hover:underline">{{ $shipment->tracking_number }}</a></td>
                                    <td class="text-ink-600">{{ $shipment->routeLabel() ?: '—' }}</td>
                                    <td><x-status-badge :status="$shipment->status" /></td>
                                    <td class="whitespace-nowrap text-ink-500">{{ $shipment->created_at->format('j M Y') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-admin.panel>
    </div>
</x-layouts.admin>
