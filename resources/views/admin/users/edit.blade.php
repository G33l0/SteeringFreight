<x-layouts.admin :title="'Edit '.$staff->name">
    <form method="POST" action="{{ route('admin.users.update', $staff) }}" class="max-w-3xl">
        @csrf
        @method('PUT')
        @include('admin.users.form')
        <div class="mt-6 flex gap-3">
            <button type="submit" class="btn btn-primary">Save account</button>
            <a href="{{ route('admin.users.index') }}" class="btn btn-outline">Cancel</a>
        </div>
    </form>

    @unless (auth()->user()->is($staff))
        <form method="POST" action="{{ route('admin.users.destroy', $staff) }}" class="mt-4"
              onsubmit="return confirm('Remove this account?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="text-sm text-alert-700 hover:underline">Remove this account</button>
        </form>
    @endunless
</x-layouts.admin>
