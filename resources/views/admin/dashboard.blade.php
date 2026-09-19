<x-layouts.admin title="Dashboard">
    <x-slot:actions>
        @can('shipments.manage')
            <a href="{{ route('admin.shipments.create') }}" class="btn btn-primary btn-sm">
                <x-icon name="plus" class="h-4 w-4" />New shipment
            </a>
        @endcan
    </x-slot:actions>

    @can('settings.manage')
        <x-admin.launch-checklist class="mb-6" />
    @endcan

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <x-admin.stat label="Total shipments" :value="$counts['total']" :href="route('admin.shipments.index')" />
        <x-admin.stat label="Active" :value="$counts['active']" :href="route('admin.shipments.index')" />
        <x-admin.stat label="In transit" :value="$counts['in_transit']" />
        <x-admin.stat label="Delivered" :value="$counts['delivered']" tone="positive" />
        <x-admin.stat label="Delayed" :value="$counts['delayed']" tone="caution" />
        <x-admin.stat label="Customs related" :value="$counts['customs']" />
        <x-admin.stat label="Out for delivery" :value="$counts['out_for_delivery']" />
        <x-admin.stat label="Unread messages" :value="$counts['unread_messages']"
                      :href="route('admin.messages.index')" :tone="$counts['unread_messages'] > 0 ? 'alert' : 'default'" />
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <x-admin.panel title="Recent shipments" compact>
            <x-slot:actions>
                <a href="{{ route('admin.shipments.index') }}" class="text-sm font-medium text-accent-700 hover:underline">View all</a>
            </x-slot:actions>

            @if ($recentShipments->isEmpty())
                <x-admin.empty message="No shipments have been created yet." />
            @else
                <div class="table-scroll">
                    <table class="data-table">
                        <thead>
                            <tr><th>Tracking number</th><th>Customer</th><th>Status</th><th>Created</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($recentShipments as $shipment)
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.shipments.show', $shipment) }}" class="font-mono font-medium text-accent-700 hover:underline">
                                            {{ $shipment->tracking_number }}
                                        </a>
                                    </td>
                                    <td>{{ $shipment->customer?->name ?? $shipment->customer_name ?? '—' }}</td>
                                    <td><x-status-badge :status="$shipment->status" /></td>
                                    <td class="whitespace-nowrap text-ink-500">{{ $shipment->created_at->format('j M Y') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-admin.panel>

        <x-admin.panel title="Recent tracking updates" compact>
            @if ($recentEvents->isEmpty())
                <x-admin.empty message="No tracking updates recorded yet." />
            @else
                <ul class="divide-y divide-ink-50">
                    @foreach ($recentEvents as $event)
                        <li class="px-4 py-3 sm:px-5">
                            <div class="flex flex-wrap items-center gap-2">
                                <a href="{{ route('admin.shipments.show', $event->shipment) }}" class="font-mono text-sm font-medium text-accent-700 hover:underline">
                                    {{ $event->shipment->tracking_number }}
                                </a>
                                <x-status-badge :status="$event->status" />
                                @unless ($event->is_public)
                                    <span class="badge badge-slate">Internal</span>
                                @endunless
                            </div>
                            <p class="mt-1 text-sm text-ink-600">
                                {{ $event->location ? $event->location.' · ' : '' }}{{ $event->occurred_at->format('j M Y, H:i') }}
                                @if ($event->creator)
                                    · {{ $event->creator->name }}
                                @endif
                            </p>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-admin.panel>

        <x-admin.panel title="Recent conversations" compact>
            <x-slot:actions>
                <a href="{{ route('admin.messages.index') }}" class="text-sm font-medium text-accent-700 hover:underline">View all</a>
            </x-slot:actions>

            @if ($recentConversations->isEmpty())
                <x-admin.empty message="No customer conversations yet." />
            @else
                <ul class="divide-y divide-ink-50">
                    @foreach ($recentConversations as $conversation)
                        <li class="flex items-center justify-between gap-3 px-4 py-3 sm:px-5">
                            <div class="min-w-0">
                                <a href="{{ route('admin.messages.show', $conversation) }}" class="text-sm font-medium hover:text-accent-700">
                                    {{ $conversation->contact_name }}
                                </a>
                                <p class="truncate text-xs text-ink-500">
                                    {{ $conversation->shipment->tracking_number }} ·
                                    {{ $conversation->last_message_at?->diffForHumans() ?? 'No messages' }}
                                </p>
                            </div>
                            @if ($conversation->unread_for_staff > 0)
                                <span class="badge badge-red">{{ $conversation->unread_for_staff }} new</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-admin.panel>

        <x-admin.panel title="Recent quote requests" compact>
            <x-slot:actions>
                <a href="{{ route('admin.quotes.index') }}" class="text-sm font-medium text-accent-700 hover:underline">View all</a>
            </x-slot:actions>

            @if ($recentQuotes->isEmpty())
                <x-admin.empty message="No quote requests yet." />
            @else
                <ul class="divide-y divide-ink-50">
                    @foreach ($recentQuotes as $quote)
                        <li class="flex items-center justify-between gap-3 px-4 py-3 sm:px-5">
                            <div class="min-w-0">
                                <a href="{{ route('admin.quotes.show', $quote) }}" class="text-sm font-medium hover:text-accent-700">
                                    {{ $quote->name }}
                                </a>
                                <p class="truncate text-xs text-ink-500">{{ $quote->origin }} to {{ $quote->destination }}</p>
                            </div>
                            <span class="badge {{ $quote->status->value === 'new' ? 'badge-amber' : 'badge-slate' }}">{{ $quote->status->label() }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-admin.panel>
    </div>

    @can('audit.view')
        <x-admin.panel title="Latest activity" class="mt-6" compact>
            <x-slot:actions>
                <a href="{{ route('admin.audit-logs.index') }}" class="text-sm font-medium text-accent-700 hover:underline">Audit log</a>
            </x-slot:actions>

            @if ($recentActivity->isEmpty())
                <x-admin.empty message="No activity recorded yet." />
            @else
                <ul class="divide-y divide-ink-50">
                    @foreach ($recentActivity as $log)
                        <li class="flex flex-wrap items-center justify-between gap-2 px-4 py-2.5 text-sm sm:px-5">
                            <span>{{ $log->description ?? $log->action }}</span>
                            <span class="text-xs text-ink-500">{{ $log->user_name ?? 'System' }} · {{ $log->created_at->diffForHumans() }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-admin.panel>
    @endcan
</x-layouts.admin>
