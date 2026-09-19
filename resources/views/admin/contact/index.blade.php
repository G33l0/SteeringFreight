<x-layouts.admin title="Contact messages">
    <x-admin.panel compact>
        <form method="GET" class="flex flex-wrap gap-2 border-b border-ink-100 p-4">
            <input type="search" name="q" value="{{ $filters['q'] }}" class="input max-w-sm" placeholder="Name, email or subject">
            <select name="status" class="select max-w-40">
                <option value="">All</option>
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-dark btn-sm">Filter</button>
            <a href="{{ route('admin.contact-messages.index') }}" class="btn btn-outline btn-sm">Reset</a>
        </form>

        @if ($messages->isEmpty())
            <x-admin.empty message="No messages found." />
        @else
            <div class="table-scroll">
                <table class="data-table">
                    <thead><tr><th>Subject</th><th>From</th><th>Status</th><th>Received</th></tr></thead>
                    <tbody>
                        @foreach ($messages as $message)
                            <tr>
                                <td><a href="{{ route('admin.contact-messages.show', $message) }}" class="font-medium text-accent-700 hover:underline">{{ $message->subject }}</a></td>
                                <td>{{ $message->name }}<span class="block text-xs text-ink-500">{{ $message->email }}</span></td>
                                <td><span class="badge {{ $message->status->value === 'new' ? 'badge-amber' : 'badge-slate' }}">{{ $message->status->label() }}</span></td>
                                <td class="whitespace-nowrap text-ink-500">{{ $message->created_at->format('j M Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="p-4">{{ $messages->links('components.pagination') }}</div>
        @endif
    </x-admin.panel>
</x-layouts.admin>
