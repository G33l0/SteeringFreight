<x-layouts.admin :title="'Edit '.$customer->name">
    <form method="POST" action="{{ route('admin.customers.update', $customer) }}" class="max-w-3xl">
        @csrf
        @method('PUT')
        @include('admin.customers.form')
        <div class="mt-6 flex gap-3">
            <button type="submit" class="btn btn-primary">Save changes</button>
            <a href="{{ route('admin.customers.show', $customer) }}" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</x-layouts.admin>
