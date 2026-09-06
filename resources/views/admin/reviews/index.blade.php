<x-layouts.admin title="Client reviews">
    <x-slot:actions>
        @can('reviews.manage')
            <a href="{{ route('admin.reviews.create') }}" class="btn btn-primary btn-sm"><x-icon name="plus" class="h-4 w-4" />New review</a>
        @endcan
    </x-slot:actions>

    <p class="mb-5 max-w-3xl text-sm text-ink-600">
        Only publish feedback a client has actually given you. The sample entries that come with the demo data are
        created unpublished and are labelled as sample content wherever they appear, so they never read as genuine
        client feedback.
    </p>

    <x-admin.panel compact>
        @if ($reviews->isEmpty())
            <x-admin.empty message="No reviews yet." />
        @else
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead><tr><th>Client</th><th>Rating</th><th>Review</th><th>Published</th><th><span class="sr-only">Actions</span></th></tr></thead>
                    <tbody>
                        @foreach ($reviews as $review)
                            <tr>
                                <td class="font-medium">
                                    {{ $review->customer_name }}
                                    @if ($review->is_sample)<span class="badge badge-slate ml-1">Sample</span>@endif
                                    <span class="block text-xs text-ink-500">{{ collect([$review->company, $review->location])->filter()->implode(', ') }}</span>
                                </td>
                                <td>{{ $review->rating }}/5</td>
                                <td class="max-w-md text-ink-600">{{ \Illuminate\Support\Str::limit($review->body, 90) }}</td>
                                <td>
                                    <span class="badge {{ $review->is_published ? 'badge-green' : 'badge-slate' }}">
                                        {{ $review->is_published ? 'Published' : 'Hidden' }}
                                    </span>
                                </td>
                                <td class="space-x-3 whitespace-nowrap text-right">
                                    @can('reviews.manage')
                                        <form method="POST" action="{{ route('admin.reviews.publish', $review) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="text-sm text-ink-600 hover:text-accent-700">
                                                {{ $review->is_published ? 'Unpublish' : 'Publish' }}
                                            </button>
                                        </form>
                                        <a href="{{ route('admin.reviews.edit', $review) }}" class="text-sm text-ink-600 hover:text-accent-700">Edit</a>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="p-4">{{ $reviews->links('components.pagination') }}</div>
        @endif
    </x-admin.panel>
</x-layouts.admin>
