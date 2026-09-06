<x-layouts.admin title="Tracking statuses">
    <x-slot:actions>
        @can('statuses.manage')
            <a href="{{ route('admin.statuses.create') }}" class="btn btn-primary btn-sm"><x-icon name="plus" class="h-4 w-4" />New status</a>
        @endcan
    </x-slot:actions>

    <p class="mb-5 max-w-3xl text-sm text-ink-600">
        Milestones make up the tracking timeline shown to customers, in stage order. Exceptions cover situations such as
        delays or customs holds and are always accompanied by an explanation written by a member of staff.
    </p>

    <div class="grid gap-6 lg:grid-cols-2">
        @foreach ([['Milestones', $milestones], ['Exceptions', $exceptions]] as [$heading, $collection])
            <x-admin.panel :title="$heading" compact>
                @if ($collection->isEmpty())
                    <x-admin.empty message="Nothing configured." />
                @else
                    <div class="overflow-x-auto">
                        <table class="data-table">
                            <thead>
                                <tr><th>Name</th><th>Stage</th><th>In use</th><th>Notify</th><th>Active</th><th><span class="sr-only">Actions</span></th></tr>
                            </thead>
                            <tbody>
                                @foreach ($collection as $status)
                                    <tr>
                                        <td>
                                            <span class="badge badge-{{ $status->colour }}">{{ $status->name }}</span>
                                            @if ($status->customer_label)
                                                <span class="block text-xs text-ink-500">Shown as: {{ $status->customer_label }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $status->stage ?? '—' }}</td>
                                        <td>{{ $status->shipments_count }}</td>
                                        <td>{{ $status->notify_customer ? 'Yes' : 'No' }}</td>
                                        <td>{{ $status->is_active ? 'Yes' : 'No' }}</td>
                                        <td class="text-right">
                                            @can('statuses.manage')
                                                <a href="{{ route('admin.statuses.edit', $status) }}" class="text-sm text-ink-600 hover:text-accent-700">Edit</a>
                                            @endcan
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-admin.panel>
        @endforeach
    </div>
</x-layouts.admin>
