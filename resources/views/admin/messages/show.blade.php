<x-layouts.admin :title="'Conversation with '.$conversation->contact_name">
    <x-slot:actions>
        @can('shipments.view')
            <a href="{{ route('admin.shipments.show', $conversation->shipment) }}" class="btn btn-outline btn-sm">Open shipment</a>
        @endcan
        @can('close', $conversation)
            <form method="POST" action="{{ $conversation->isOpen() ? route('admin.messages.close', $conversation) : route('admin.messages.reopen', $conversation) }}">
                @csrf
                <button type="submit" class="btn btn-outline btn-sm">{{ $conversation->isOpen() ? 'Close conversation' : 'Reopen' }}</button>
            </form>
        @endcan
    </x-slot:actions>

    <div class="grid gap-6 lg:grid-cols-[1.35fr_0.65fr]">
        <x-admin.panel compact>
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-ink-100 px-4 py-3 sm:px-5">
                <div>
                    <p class="font-display text-base font-semibold">{{ $conversation->subject ?: 'Shipment '.$conversation->shipment->tracking_number }}</p>
                    <p class="text-xs text-ink-500">
                        {{ $conversation->contact_email }} · started {{ $conversation->created_at->format('j M Y, H:i') }}
                    </p>
                    <p class="mt-1 text-xs text-ink-500">
                        Clears {{ $conversation->expiresAt()->format('j M Y, H:i') }}. Anything that has to be kept
                        belongs on the shipment, not in the chat.
                    </p>
                </div>
                <span class="badge {{ $conversation->isOpen() ? 'badge-green' : 'badge-slate' }}">{{ $conversation->status->label() }}</span>
            </div>

            <div class="max-h-[32rem] space-y-4 overflow-y-auto p-4 sm:p-5">
                @foreach ($conversation->messages as $message)
                    <div @class(['flex', 'justify-end' => $message->fromStaff()])>
                        <div @class([
                            'max-w-[85%] rounded px-3.5 py-2.5 text-sm',
                            'bg-accent-50' => $message->fromStaff(),
                            'bg-ink-50' => ! $message->fromStaff(),
                        ])>
                            <p class="text-xs font-semibold {{ $message->fromStaff() ? 'text-accent-700' : 'text-ink-600' }}">
                                {{ $message->sender_name }}{{ $message->fromStaff() ? ' (staff)' : '' }}
                            </p>
                            <p class="mt-1 whitespace-pre-line leading-relaxed">{{ $message->body }}</p>
                            <p class="mt-1.5 text-[11px] text-ink-400">{{ $message->created_at->format('j M Y, H:i') }}</p>
                        </div>
                    </div>
                @endforeach
            </div>

            @can('reply', $conversation)
                <form method="POST" action="{{ route('admin.messages.reply', $conversation) }}"
                      class="space-y-3 border-t border-ink-100 p-4 sm:p-5">
                    @csrf
                    <div>
                        <label for="body" class="label">Reply</label>
                        <textarea id="body" name="body" rows="4" required maxlength="{{ config('portlane.chat.message_max_length') }}" class="textarea">{{ old('body') }}</textarea>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <button type="submit" class="btn btn-primary btn-sm">Send reply</button>
                        @unless ($conversation->isAssigned())
                            <span class="text-xs text-ink-500">Replying will assign this conversation to you.</span>
                        @endunless
                    </div>
                </form>
            @else
                <div class="border-t border-ink-100 p-4 text-sm text-ink-500 sm:p-5">
                    @if (! $conversation->isOpen())
                        This conversation is closed. Reopen it to reply.
                    @else
                        {{ $conversation->assignee?->name }} is handling this conversation.
                    @endif
                </div>
            @endcan
        </x-admin.panel>

        <div class="space-y-6">
            <x-admin.panel title="Handled by">
                <p class="text-sm">
                    @if ($conversation->assignee)
                        <span class="font-medium">{{ $conversation->assignee->name }}</span>
                        <span class="block text-xs text-ink-500">
                            {{ $conversation->assignee->role->label() }}
                            @if ($conversation->assigned_at) · since {{ $conversation->assigned_at->format('j M Y, H:i') }} @endif
                        </span>
                    @else
                        <span class="badge badge-amber">Unassigned</span>
                        <span class="mt-2 block text-xs text-ink-500">Nobody has picked this conversation up yet.</span>
                    @endif
                </p>

                @can('assign', $conversation)
                    <form method="POST" action="{{ route('admin.messages.assign', $conversation) }}" class="mt-4 space-y-3">
                        @csrf
                        <div>
                            <label for="assigned_to" class="label">Assign to</label>
                            <select id="assigned_to" name="assigned_to" class="select">
                                <option value="">Unassigned queue</option>
                                @foreach ($representatives as $representative)
                                    <option value="{{ $representative->id }}" @selected($conversation->assigned_to === $representative->id)>
                                        {{ $representative->name }} — {{ $representative->role->shortLabel() }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-outline btn-sm">Save assignment</button>
                    </form>
                @elsecan('reply', $conversation)
                    @unless ($conversation->isAssigned())
                        <form method="POST" action="{{ route('admin.messages.claim', $conversation) }}" class="mt-4">
                            @csrf
                            <button type="submit" class="btn btn-primary btn-sm">Pick up this conversation</button>
                        </form>
                    @endunless
                @endcan
            </x-admin.panel>

            <x-admin.panel title="Shipment" description="Read only reference for this conversation">
                <dl class="divide-y divide-ink-50">
                    @foreach ([
                        'Tracking number' => $conversation->shipment->tracking_number,
                        'Status' => $conversation->shipment->status?->name,
                        'Route' => $conversation->shipment->routeLabel(),
                        'Current location' => $conversation->shipment->current_location,
                        'Shipping method' => $conversation->shipment->shipping_method?->label(),
                        'Estimated delivery' => $conversation->shipment->estimated_delivery?->format('j M Y'),
                        'Customer' => $conversation->shipment->customerContactName(),
                    ] as $label => $detail)
                        @if (filled($detail))
                            <div class="flex justify-between gap-4 py-2 text-sm">
                                <dt class="text-ink-500">{{ $label }}</dt>
                                <dd class="text-right font-medium">{{ $detail }}</dd>
                            </div>
                        @endif
                    @endforeach
                </dl>

                <a href="{{ route('track.show', $conversation->shipment->tracking_number) }}" target="_blank" rel="noopener"
                   class="mt-4 inline-flex items-center gap-1.5 text-sm font-medium text-accent-700 hover:underline">
                    Open the customer tracking page
                </a>
            </x-admin.panel>

            @if ($events->isNotEmpty())
                <x-admin.panel title="Latest tracking updates" compact>
                    <ul class="divide-y divide-ink-50">
                        @foreach ($events as $event)
                            <li class="px-4 py-2.5 text-sm sm:px-5">
                                <div class="flex flex-wrap items-center gap-2">
                                    <x-status-badge :status="$event->status" />
                                    @unless ($event->is_public)
                                        <span class="badge badge-slate">Internal</span>
                                    @endunless
                                </div>
                                <p class="mt-1 text-xs text-ink-500">
                                    {{ $event->occurred_at->format('j M Y, H:i') }}{{ $event->location ? ' · '.$event->location : '' }}
                                </p>
                                @if ($event->description)
                                    <p class="mt-1 text-sm text-ink-700">{{ $event->description }}</p>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </x-admin.panel>
            @endif
        </div>
    </div>
</x-layouts.admin>
