<x-layouts.admin title="New admin account">
    <form method="POST" action="{{ route('admin.users.store') }}" class="max-w-3xl">
        @csrf
        @include('admin.users.form')
        <div class="mt-6 flex gap-3">
            <button type="submit" class="btn btn-primary">Create account</button>
            <a href="{{ route('admin.users.index') }}" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</x-layouts.admin>
