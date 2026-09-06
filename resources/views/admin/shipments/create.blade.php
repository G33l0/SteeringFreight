<x-layouts.admin title="New shipment">
    <form method="POST" action="{{ route('admin.shipments.store') }}" class="max-w-4xl">
        @csrf
        @include('admin.shipments.form', ['shipment' => null])

        <div class="mt-6 flex flex-wrap gap-3">
            <button type="submit" class="btn btn-primary">Create shipment</button>
            <a href="{{ route('admin.shipments.index') }}" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</x-layouts.admin>
