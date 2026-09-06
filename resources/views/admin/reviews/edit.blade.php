<x-layouts.admin title="Edit review">
    <form method="POST" action="{{ route('admin.reviews.update', $review) }}" enctype="multipart/form-data" class="max-w-3xl">
        @csrf
        @method('PUT')
        @include('admin.reviews.form')
        <div class="mt-6 flex gap-3">
            <button type="submit" class="btn btn-primary">Save review</button>
            <a href="{{ route('admin.reviews.index') }}" class="btn btn-outline">Cancel</a>
        </div>
    </form>

    <form method="POST" action="{{ route('admin.reviews.destroy', $review) }}" class="mt-4" onsubmit="return confirm('Delete this review?');">
        @csrf
        @method('DELETE')
        <button type="submit" class="text-sm text-alert-700 hover:underline">Delete this review</button>
    </form>
</x-layouts.admin>
