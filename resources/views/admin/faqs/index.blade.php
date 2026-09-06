<x-layouts.admin title="Frequently asked questions">
    <x-slot:actions>
        @can('faqs.manage')
            <a href="{{ route('admin.faqs.create') }}" class="btn btn-primary btn-sm"><x-icon name="plus" class="h-4 w-4" />New question</a>
        @endcan
    </x-slot:actions>

    <x-admin.panel compact>
        @if ($faqs->isEmpty())
            <x-admin.empty message="No questions yet." />
        @else
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead><tr><th>Question</th><th>Category</th><th>Order</th><th>Published</th><th>Homepage</th><th><span class="sr-only">Actions</span></th></tr></thead>
                    <tbody>
                        @foreach ($faqs as $faq)
                            <tr>
                                <td class="max-w-md font-medium">{{ $faq->question }}</td>
                                <td>{{ $faq->category ?: '—' }}</td>
                                <td>{{ $faq->sort_order }}</td>
                                <td>{{ $faq->is_published ? 'Yes' : 'No' }}</td>
                                <td>{{ $faq->show_on_home ? 'Yes' : 'No' }}</td>
                                <td class="text-right">
                                    @can('faqs.manage')
                                        <a href="{{ route('admin.faqs.edit', $faq) }}" class="text-sm text-ink-600 hover:text-accent-700">Edit</a>
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
