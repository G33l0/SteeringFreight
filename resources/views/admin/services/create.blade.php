<x-layouts.admin title="New service">
    <form method="POST" action="{{ route('admin.services.store') }}" enctype="multipart/form-data" class="max-w-4xl">
        @csrf
        @include('admin.services.form')
        <div class="mt-6 flex gap-3">
            <button type="submit" class="btn btn-primary">Create service</button>
            <a href="{{ route('admin.services.index') }}" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</x-layouts.admin>
