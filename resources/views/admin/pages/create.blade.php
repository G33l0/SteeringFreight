<x-layouts.admin title="New page">
    <form method="POST" action="{{ route('admin.pages.store') }}" class="max-w-4xl">
        @csrf
        @include('admin.pages.form')
        <div class="mt-6 flex gap-3">
            <button type="submit" class="btn btn-primary">Create page</button>
            <a href="{{ route('admin.pages.index') }}" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</x-layouts.admin>
