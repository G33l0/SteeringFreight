<x-layouts.admin :title="'Edit '.$service->title">
    <form method="POST" action="{{ route('admin.services.update', $service) }}" enctype="multipart/form-data" class="max-w-4xl">
        @csrf
        @method('PUT')
        @include('admin.services.form')
        <div class="mt-6 flex gap-3">
            <button type="submit" class="btn btn-primary">Save service</button>
            <a href="{{ route('admin.services.index') }}" class="btn btn-outline">Cancel</a>
        </div>
    </form>

    <form method="POST" action="{{ route('admin.services.destroy', $service) }}" class="mt-4"
          onsubmit="return confirm('Delete this service?');">
        @csrf
        @method('DELETE')
        <button type="submit" class="text-sm text-alert-700 hover:underline">Delete this service</button>
    </form>
</x-layouts.admin>
