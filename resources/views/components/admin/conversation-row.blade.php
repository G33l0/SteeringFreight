@props(['conversation', 'claimable' => false])

<li class="flex flex-wrap items-start justify-between gap-3 px-4 py-3 sm:px-5">
    <div class="min-w-0 flex-1">
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.messages.show', $conversation) }}" class="text-sm font-medium hover:text-accent-700">
                {{ $conversation->contact_name }}
            </a>
            <span class="font-mono text-xs text-ink-500">{{ $conversation->shipment->tracking_number }}</span>
            <x-status-badge :status="$conversation->shipment->status" />
        </div>

        @if ($latest = $conversation->latestMessage->first())
            <p class="mt-1 line-clamp-2 max-w-xl text-sm text-ink-600">
                {{ $latest->fromStaff() ? 'You: ' : '' }}{{ \Illuminate\Support\Str::limit($latest->body, 120) }}
            </p>
        @endif

        <p class="mt-1 text-xs text-ink-500">
            {{ $conversation->shipment->routeLabel() ?: 'Route not set' }}
            @if ($conversation->last_message_at)
                · {{ $conversation->last_message_at->diffForHumans() }}
            @endif
        </p>
    </div>

    <div class="flex shrink-0 items-center gap-2">
        @if ($conversation->unread_for_staff > 0)
            <span class="badge badge-red">{{ $conversation->unread_for_staff }} new</span>
        @endif
        <span class="badge {{ $conversation->isOpen() ? 'badge-green' : 'badge-slate' }}">{{ $conversation->status->label() }}</span>

        @if ($claimable)
            <form method="POST" action="{{ route('admin.messages.claim', $conversation) }}">
                @csrf
                <button type="submit" class="btn btn-outline btn-sm">Pick up</button>
            </form>
        @endif
    </div>
</li>
