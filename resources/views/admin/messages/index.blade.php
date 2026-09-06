<x-layouts.admin title="Customer messages">
    <div class="mb-5 flex flex-wrap gap-2">
        @php $queue = $filters['queue']; @endphp
        <a href="{{ route('admin.messages.index') }}" @class(['btn btn-sm', 'btn-dark' => $queue === '', 'btn-outline' => $queue !== ''])>
            All ({{ $counts['open'] }} open)
        </a>
        <a href="{{ route('admin.messages.index', ['queue' => 'mine']) }}" @class(['btn btn-sm', 'btn-dark' => $queue === 'mine', 'btn-outline' => $queue !== 'mine'])>
            Assigned to me ({{ $counts['mine'] }})
        </a>
        <a href="{{ route('admin.messages.index', ['queue' => 'unassigned']) }}" @class(['btn btn-sm', 'btn-dark' => $queue === 'unassigned', 'btn-outline' => $queue !== 'unassigned'])>
            Waiting to be picked up ({{ $counts['unassigned'] }})
        </a>
    </div>

    <x-admin.panel compact>
        <form method="GET" class="flex flex-wrap gap-2 border-b border-ink-100 p-4">
            <input type="hidden" name="queue" value="{{ $filters['queue'] }}">
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
            <x-admin.empty message="No conversations match this view." />
        @else
            <ul class="divide-y divide-ink-50">
                @foreach ($conversations as $conversation)
                    <li class="flex flex-wrap items-start justify-between gap-3 px-4 py-3 sm:px-5">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <a href="{{ route('admin.messages.show', $conversation) }}" class="font-medium hover:text-accent-700">
                                    {{ $conversation->contact_name }}
                                </a>
                                <span class="font-mono text-xs text-ink-500">{{ $conversation->shipment->tracking_number }}</span>
                                <x-status-badge :status="$conversation->shipment->status" />
                            </div>

                            <p class="text-xs text-ink-500">{{ $conversation->contact_email }}</p>

                            @if ($latest = $conversation->latestMessage->first())
                                <p class="mt-1 max-w-xl truncate text-sm text-ink-600">
                                    {{ $latest->fromStaff() ? $latest->sender_name.': ' : '' }}{{ $latest->body }}
                                </p>
                            @endif
                        </div>

                        <div class="flex shrink-0 flex-col items-end gap-1.5">
                            <div class="flex items-center gap-2">
                                @if ($conversation->unread_for_staff > 0)
                                    <span class="badge badge-red">{{ $conversation->unread_for_staff }} new</span>
                                @endif
                                <span class="badge {{ $conversation->isOpen() ? 'badge-green' : 'badge-slate' }}">{{ $conversation->status->label() }}</span>
                            </div>

                            <p class="text-xs text-ink-500">
                                @if ($conversation->assignee)
                                    {{ $conversation->assignee->is(auth()->user()) ? 'Assigned to you' : 'Assigned to '.$conversation->assignee->name }}
                                @else
                                    <span class="font-medium text-caution-700">Unassigned</span>
                                @endif
                                · {{ $conversation->last_message_at?->diffForHumans() }}
                            </p>

                            @if (! $conversation->isAssigned() && $conversation->isOpen() && auth()->user()->can('reply', $conversation))
                                <form method="POST" action="{{ route('admin.messages.claim', $conversation) }}">
                                    @csrf
                                    <button type="submit" class="text-sm font-medium text-accent-700 hover:underline">Pick up</button>
                                </form>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
            <div class="p-4">{{ $conversations->links('components.pagination') }}</div>
        @endif
    </x-admin.panel>
</x-layouts.admin>
