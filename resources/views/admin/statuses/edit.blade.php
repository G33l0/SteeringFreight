<x-layouts.admin :title="'Edit '.$status->name">
    <form method="POST" action="{{ route('admin.statuses.update', $status) }}" class="max-w-3xl">
        @csrf
        @method('PUT')
        @include('admin.statuses.form')
        <div class="mt-6 flex flex-wrap gap-3">
            <button type="submit" class="btn btn-primary">Save status</button>
            <a href="{{ route('admin.statuses.index') }}" class="btn btn-outline">Cancel</a>
        </div>
    </form>

    <form method="POST" action="{{ route('admin.statuses.destroy', $status) }}" class="mt-4"
          onsubmit="return confirm('Delete this status?');">
        @csrf
        @method('DELETE')
        <button type="submit" class="text-sm text-alert-700 hover:underline">Delete this status</button>
    </form>
</x-layouts.admin>
