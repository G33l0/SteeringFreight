<x-layouts.admin title="My conversations">
    <div class="mb-6">
        <p class="text-sm text-ink-600">
            Signed in as <span class="font-medium text-ink-900">{{ auth()->user()->name }}</span>,
            {{ auth()->user()->role->label() }}. You answer the customers assigned to you and can pick up anyone
            waiting. Shipment records are read only from here.
        </p>
    </div>

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <x-admin.stat label="My open conversations" :value="$counts['assigned_open']" :href="route('admin.messages.index', ['queue' => 'mine', 'status' => 'open'])" />
        <x-admin.stat label="Unread for me" :value="$counts['unread']" :tone="$counts['unread'] > 0 ? 'alert' : 'default'" />
        <x-admin.stat label="Waiting to be picked up" :value="$counts['waiting']" :href="route('admin.messages.index', ['queue' => 'unassigned'])" :tone="$counts['waiting'] > 0 ? 'caution' : 'default'" />
        <x-admin.stat label="Handled in total" :value="$counts['handled']" />
    </div>

    <div class="mt-6 grid items-start gap-6 lg:grid-cols-2">
        <x-admin.panel title="My conversations" compact>
            <x-slot:actions>
                <a href="{{ route('admin.messages.index', ['queue' => 'mine']) }}" class="text-sm font-medium text-accent-700 hover:underline">View all</a>
            </x-slot:actions>

            @if ($mine->isEmpty())
                <x-admin.empty message="Nothing assigned to you yet. Pick up a conversation from the waiting list." />
            @else
                <ul class="divide-y divide-ink-50">
                    @foreach ($mine as $conversation)
                        <x-admin.conversation-row :conversation="$conversation" />
                    @endforeach
                </ul>
            @endif
        </x-admin.panel>

        <x-admin.panel title="Waiting to be picked up" description="Customers who have written in and have nobody handling them yet" compact>
            @if ($unassigned->isEmpty())
                <x-admin.empty message="Nobody is waiting." />
            @else
                <ul class="divide-y divide-ink-50">
                    @foreach ($unassigned as $conversation)
                        <x-admin.conversation-row :conversation="$conversation" :claimable="true" />
                    @endforeach
                </ul>
            @endif
        </x-admin.panel>
    </div>
</x-layouts.admin>
