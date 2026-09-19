<x-layouts.admin title="Pages">
    <x-slot:actions>
        @can('pages.manage')
            <a href="{{ route('admin.pages.create') }}" class="btn btn-primary btn-sm"><x-icon name="plus" class="h-4 w-4" />New page</a>
        @endcan
    </x-slot:actions>

    <x-admin.panel compact>
        @if ($pages->isEmpty())
            <x-admin.empty message="No pages yet." />
        @else
            <div class="table-scroll">
                <table class="data-table">
                    <thead><tr><th>Title</th><th>Address</th><th>Published</th><th>Updated</th><th><span class="sr-only">Actions</span></th></tr></thead>
                    <tbody>
                        @foreach ($pages as $page)
                            <tr>
                                <td class="font-medium">
                                    {{ $page->title }}
                                    @if ($page->is_system)<span class="badge badge-slate ml-1">System</span>@endif
                                </td>
                                <td class="font-mono text-xs text-ink-500">/{{ $page->slug }}</td>
                                <td>{{ $page->is_published ? 'Yes' : 'No' }}</td>
                                <td class="whitespace-nowrap text-ink-500">{{ $page->updated_at->format('j M Y') }}</td>
                                <td class="text-right">
                                    @can('pages.manage')
                                        <a href="{{ route('admin.pages.edit', $page) }}" class="text-sm text-ink-600 hover:text-accent-700">Edit</a>
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
