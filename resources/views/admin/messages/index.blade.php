<x-layouts.admin title="Customer messages">
    <x-admin.panel compact>
        <form method="GET" class="flex flex-wrap gap-2 border-b border-ink-100 p-4">
            <input type="search" name="q" value="{{ $filters['q'] }}" class="input max-w-sm" placeholder="Name, email or tracking number">
            <select name="status" class="select max-w-40">
                <option value="">All conversations</option>
                <option value="open" @selected($filters['status'] === 'open')>Open</option>
                <option value="closed" @selected($filters['status'] === 'closed')>Closed</option>
            </select>
            <button type="submit" class="btn btn-dark btn-sm">Filter</button>
            <a href="{{ route('admin.messages.index') }}" class="btn btn-outline btn-sm">Reset</a>
        </form>

        @if ($conversations->isEmpty())
            <x-admin.empty message="No conversations yet." />
        @else
            <ul class="divide-y divide-ink-50">
                @foreach ($conversations as $conversation)
                    <li class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 sm:px-5">
                        <div class="min-w-0">
                            <a href="{{ route('admin.messages.show', $conversation) }}" class="font-medium hover:text-accent-700">
                                {{ $conversation->contact_name }}
                            </a>
                            <p class="text-xs text-ink-500">
                                {{ $conversation->contact_email }} ·
                                <a href="{{ route('admin.shipments.show', $conversation->shipment) }}" class="font-mono hover:underline">{{ $conversation->shipment->tracking_number }}</a>
                            </p>
                            @if ($latest = $conversation->latestMessage->first())
                                <p class="mt-1 max-w-xl truncate text-sm text-ink-600">{{ $latest->body }}</p>
                            @endif
                        </div>
                        <div class="flex items-center gap-2 text-right">
                            @if ($conversation->unread_for_staff > 0)
                                <span class="badge badge-red">{{ $conversation->unread_for_staff }} new</span>
                            @endif
                            <span class="badge {{ $conversation->isOpen() ? 'badge-green' : 'badge-slate' }}">{{ $conversation->status->label() }}</span>
                            <span class="text-xs text-ink-500">{{ $conversation->last_message_at?->diffForHumans() }}</span>
                        </div>
                    </li>
                @endforeach
            </ul>
            <div class="p-4">{{ $conversations->links('components.pagination') }}</div>
        @endif
    </x-admin.panel>
</x-layouts.admin>
