<x-layouts.admin :title="'Edit '.$shipment->tracking_number">
    <form method="POST" action="{{ route('admin.shipments.update', $shipment) }}" class="max-w-4xl">
        @csrf
        @method('PUT')
        @include('admin.shipments.form', ['shipment' => $shipment])

        <div class="mt-6 flex flex-wrap gap-3">
            <button type="submit" class="btn btn-primary">Save changes</button>
            <a href="{{ route('admin.shipments.show', $shipment) }}" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</x-layouts.admin>
