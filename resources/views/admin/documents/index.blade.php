<x-layouts.admin title="Documents">
    <x-admin.panel compact>
        <form method="GET" class="flex flex-wrap gap-2 border-b border-ink-100 p-4">
            <input type="search" name="q" value="{{ $filters['q'] }}" class="input max-w-sm" placeholder="Title, filename or tracking number">
            <select name="visibility" class="select max-w-56">
                <option value="">All documents</option>
                @foreach ($visibilities as $value => $label)
                    <option value="{{ $value }}" @selected($filters['visibility'] === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-dark btn-sm">Filter</button>
            <a href="{{ route('admin.documents.index') }}" class="btn btn-outline btn-sm">Reset</a>
        </form>

        @if ($documents->isEmpty())
            <x-admin.empty message="No documents uploaded yet." />
        @else
            <div class="table-scroll">
                <table class="data-table">
                    <thead><tr><th>Title</th><th>Shipment</th><th>Type</th><th>Visibility</th><th>Size</th><th>Uploaded</th></tr></thead>
                    <tbody>
                        @foreach ($documents as $document)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.shipments.documents.download', [$document->shipment, $document]) }}" class="font-medium text-accent-700 hover:underline">
                                        {{ $document->title }}
                                    </a>
                                    <span class="block text-xs text-ink-500">{{ $document->original_name }}</span>
                                </td>
                                <td>
                                    <a href="{{ route('admin.shipments.show', $document->shipment) }}" class="font-mono text-sm hover:underline">
                                        {{ $document->shipment->tracking_number }}
                                    </a>
                                </td>
                                <td>{{ $document->type->label() }}</td>
                                <td>
                                    <span class="badge {{ $document->isVisibleToCustomer() ? 'badge-green' : 'badge-slate' }}">
                                        {{ $document->isVisibleToCustomer() ? 'Customer' : 'Internal' }}
                                    </span>
                                </td>
                                <td>{{ $document->readableSize() }}</td>
                                <td class="whitespace-nowrap text-ink-500">
                                    {{ $document->created_at->format('j M Y') }}
                                    <span class="block text-xs">{{ $document->uploader?->name }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="p-4">{{ $documents->links('components.pagination') }}</div>
        @endif
    </x-admin.panel>
</x-layouts.admin>
