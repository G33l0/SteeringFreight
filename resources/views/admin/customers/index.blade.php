<x-layouts.admin title="Customers">
    <x-slot:actions>
        @can('customers.manage')
            <a href="{{ route('admin.customers.create') }}" class="btn btn-primary btn-sm"><x-icon name="plus" class="h-4 w-4" />New customer</a>
        @endcan
    </x-slot:actions>

    <x-admin.panel compact>
        <form method="GET" class="flex flex-wrap gap-2 border-b border-ink-100 p-4">
            <input type="search" name="q" value="{{ $search }}" class="input max-w-sm" placeholder="Name, company, email or reference">
            <button type="submit" class="btn btn-dark btn-sm">Search</button>
            <a href="{{ route('admin.customers.index') }}" class="btn btn-outline btn-sm">Reset</a>
        </form>

        @if ($customers->isEmpty())
            <x-admin.empty message="No customers found." />
        @else
            <div class="table-scroll">
                <table class="data-table">
                    <thead>
                        <tr><th>Name</th><th>Company</th><th>Email</th><th>Phone</th><th>Shipments</th><th><span class="sr-only">Actions</span></th></tr>
                    </thead>
                    <tbody>
                        @foreach ($customers as $customer)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.customers.show', $customer) }}" class="font-medium text-accent-700 hover:underline">{{ $customer->name }}</a>
                                    @if ($customer->is_sample)<span class="badge badge-slate ml-1">Sample</span>@endif
                                    <span class="block font-mono text-xs text-ink-500">{{ $customer->reference }}</span>
                                </td>
                                <td>{{ $customer->company ?: '—' }}</td>
                                <td>{{ $customer->email ?: '—' }}</td>
                                <td>{{ $customer->phone ?: '—' }}</td>
                                <td>{{ $customer->shipments_count }}</td>
                                <td class="text-right"><a href="{{ route('admin.customers.show', $customer) }}" class="text-sm text-ink-600 hover:text-accent-700">Open</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="p-4">{{ $customers->links('components.pagination') }}</div>
        @endif
    </x-admin.panel>
</x-layouts.admin>
