<x-layouts.admin title="New review">
    <form method="POST" action="{{ route('admin.reviews.store') }}" enctype="multipart/form-data" class="max-w-3xl">
        @csrf
        @include('admin.reviews.form')
        <div class="mt-6 flex gap-3">
            <button type="submit" class="btn btn-primary">Create review</button>
            <a href="{{ route('admin.reviews.index') }}" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</x-layouts.admin>
