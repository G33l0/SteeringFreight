<x-layouts.admin title="New customer">
    <form method="POST" action="{{ route('admin.customers.store') }}" class="max-w-3xl">
        @csrf
        @include('admin.customers.form')
        <div class="mt-6 flex gap-3">
            <button type="submit" class="btn btn-primary">Create customer</button>
            <a href="{{ route('admin.customers.index') }}" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</x-layouts.admin>
