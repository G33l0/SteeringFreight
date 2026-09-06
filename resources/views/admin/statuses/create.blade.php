<x-layouts.admin title="New status">
    <form method="POST" action="{{ route('admin.statuses.store') }}" class="max-w-3xl">
        @csrf
        @include('admin.statuses.form')
        <div class="mt-6 flex gap-3">
            <button type="submit" class="btn btn-primary">Create status</button>
            <a href="{{ route('admin.statuses.index') }}" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</x-layouts.admin>
