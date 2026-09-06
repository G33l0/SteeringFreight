<x-layouts.admin :title="'Edit '.$page->title">
    <form method="POST" action="{{ route('admin.pages.update', $page) }}" class="max-w-4xl">
        @csrf
        @method('PUT')
        @include('admin.pages.form')
        <div class="mt-6 flex gap-3">
            <button type="submit" class="btn btn-primary">Save page</button>
            <a href="{{ route('admin.pages.index') }}" class="btn btn-outline">Cancel</a>
        </div>
    </form>

    @unless ($page->is_system)
        <form method="POST" action="{{ route('admin.pages.destroy', $page) }}" class="mt-4"
              onsubmit="return confirm('Delete this page?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="text-sm text-alert-700 hover:underline">Delete this page</button>
        </form>
    @endunless
</x-layouts.admin>
