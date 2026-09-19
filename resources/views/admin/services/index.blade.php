<x-layouts.admin title="Services">
    <x-slot:actions>
        @can('services.manage')
            <a href="{{ route('admin.services.create') }}" class="btn btn-primary btn-sm"><x-icon name="plus" class="h-4 w-4" />New service</a>
        @endcan
    </x-slot:actions>

    <x-admin.panel compact>
        @if ($services->isEmpty())
            <x-admin.empty message="No services yet." />
        @else
            <div class="table-scroll">
                <table class="data-table">
                    <thead><tr><th>Title</th><th>Slug</th><th>Order</th><th>Published</th><th>On homepage</th><th><span class="sr-only">Actions</span></th></tr></thead>
                    <tbody>
                        @foreach ($services as $service)
                            <tr>
                                <td class="font-medium">
                                    <span class="inline-flex items-center gap-2">
                                        <x-icon :name="$service->icon" class="h-4 w-4 text-accent-600" />
                                        {{ $service->title }}
                                    </span>
                                </td>
                                <td class="font-mono text-xs text-ink-500">{{ $service->slug }}</td>
                                <td>{{ $service->sort_order }}</td>
                                <td>{{ $service->is_published ? 'Yes' : 'No' }}</td>
                                <td>{{ $service->show_on_home ? 'Yes' : 'No' }}</td>
                                <td class="space-x-3 text-right">
                                    <a href="{{ route('services.show', $service) }}" target="_blank" rel="noopener" class="text-sm text-ink-600 hover:text-accent-700">View</a>
                                    @can('services.manage')
                                        <a href="{{ route('admin.services.edit', $service) }}" class="text-sm text-ink-600 hover:text-accent-700">Edit</a>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-admin.panel>
</x-layouts.admin>
