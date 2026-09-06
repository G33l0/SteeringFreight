<x-layouts.admin :title="'Conversation with '.$conversation->contact_name">
    <x-slot:actions>
        <a href="{{ route('admin.shipments.show', $conversation->shipment) }}" class="btn btn-outline btn-sm">Open shipment</a>
        @can('chat.reply')
            <form method="POST" action="{{ $conversation->isOpen() ? route('admin.messages.close', $conversation) : route('admin.messages.reopen', $conversation) }}">
                @csrf
                <button type="submit" class="btn btn-outline btn-sm">{{ $conversation->isOpen() ? 'Close conversation' : 'Reopen' }}</button>
            </form>
        @endcan
    </x-slot:actions>

    <div class="grid gap-6 lg:grid-cols-[1.4fr_0.6fr]">
        <x-admin.panel compact>
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-ink-100 px-4 py-3 sm:px-5">
                <div>
                    <p class="font-display text-base font-semibold">{{ $conversation->subject ?: 'Shipment '.$conversation->shipment->tracking_number }}</p>
                    <p class="text-xs text-ink-500">Started {{ $conversation->created_at->format('j M Y, H:i') }}</p>
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
                            @if ($message->hasAttachment())
                                <a href="{{ route('admin.messages.attachment', [$conversation, $message]) }}"
                                   class="mt-2 inline-flex items-center gap-1.5 text-xs font-semibold text-accent-700 hover:underline">
                                    <x-icon name="download" class="h-3.5 w-3.5" />{{ $message->attachment_name }}
                                </a>
                            @endif
                            <p class="mt-1.5 text-[11px] text-ink-400">{{ $message->created_at->format('j M Y, H:i') }}</p>
                        </div>
                    </div>
                @endforeach
            </div>

            @can('chat.reply')
                <form method="POST" action="{{ route('admin.messages.reply', $conversation) }}" enctype="multipart/form-data"
                      class="space-y-3 border-t border-ink-100 p-4 sm:p-5">
                    @csrf
                    <div>
                        <label for="body" class="label">Reply</label>
                        <textarea id="body" name="body" rows="4" required maxlength="{{ config('portlane.chat.message_max_length') }}" class="textarea">{{ old('body') }}</textarea>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <input type="file" name="attachment" class="input max-w-xs py-1.5 text-xs">
                        <button type="submit" class="btn btn-primary btn-sm">Send reply</button>
                    </div>
                </form>
            @endcan
        </x-admin.panel>

        <x-admin.panel title="Shipment">
            <dl class="divide-y divide-ink-50">
                @foreach ([
                    'Tracking number' => $conversation->shipment->tracking_number,
                    'Status' => $conversation->shipment->status?->name,
                    'Route' => $conversation->shipment->routeLabel(),
                    'Customer email' => $conversation->contact_email,
                    'Messages' => $conversation->messages->count(),
                ] as $label => $detail)
                    @if (filled($detail))
                        <div class="flex justify-between gap-4 py-2 text-sm">
                            <dt class="text-ink-500">{{ $label }}</dt>
                            <dd class="text-right font-medium">{{ $detail }}</dd>
                        </div>
                    @endif
                @endforeach
            </dl>
        </x-admin.panel>
    </div>
</x-layouts.admin>
