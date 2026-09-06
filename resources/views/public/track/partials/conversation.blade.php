@php
    $endpoint = route('track.chat.messages', [
        'tracking_number' => $shipment->tracking_number,
        'conversation' => $conversation,
    ]);
@endphp

<div class="mt-5 border border-ink-100 bg-white"
     x-data="trackingChat({ endpoint: '{{ $endpoint }}', interval: {{ $pollInterval }}, lastId: {{ $messages->max('id') ?? 0 }} })">
    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-ink-100 px-5 py-3">
        <div>
            <p class="font-display text-sm font-semibold">{{ $conversation->subject ?: 'Shipment '.$shipment->tracking_number }}</p>
            <p class="text-xs text-ink-500">Started {{ $conversation->created_at->format('j M Y') }}</p>
        </div>
        <span class="badge {{ $conversation->isOpen() ? 'badge-green' : 'badge-slate' }}">{{ $conversation->status->label() }}</span>
    </div>

    <div x-ref="thread" class="max-h-96 space-y-4 overflow-y-auto px-5 py-4">
        @foreach ($messages as $message)
            <div @class(['flex', 'justify-end' => ! $message->fromStaff()])>
                <div @class([
                    'max-w-[85%] rounded px-3.5 py-2.5 text-sm',
                    'bg-ink-50 text-ink-800' => $message->fromStaff(),
                    'bg-accent-50 text-ink-900' => ! $message->fromStaff(),
                ])>
                    <p class="text-xs font-semibold {{ $message->fromStaff() ? 'text-ink-600' : 'text-accent-700' }}">
                        {{ $message->fromStaff() ? company_name() : $message->sender_name }}
                    </p>
                    <p class="mt-1 whitespace-pre-line leading-relaxed">{{ $message->body }}</p>
                    @if ($message->hasAttachment())
                        <a href="{{ route('track.chat.attachment', ['tracking_number' => $shipment->tracking_number, 'conversation' => $conversation, 'message' => $message]) }}"
                           class="mt-2 inline-flex items-center gap-1.5 text-xs font-semibold text-accent-700 hover:underline">
                            <x-icon name="download" class="h-3.5 w-3.5" />{{ $message->attachment_name }}
                        </a>
                    @endif
                    <p class="mt-1.5 text-[11px] text-ink-400">{{ $message->created_at->format('j M Y, H:i') }}</p>
                </div>
            </div>
        @endforeach

        {{-- Messages that arrive while the page is open --}}
        <template x-for="message in messages" :key="message.id">
            <div class="flex" :class="{ 'justify-end': ! message.from_staff }">
                <div class="max-w-[85%] rounded px-3.5 py-2.5 text-sm"
                     :class="message.from_staff ? 'bg-ink-50 text-ink-800' : 'bg-accent-50 text-ink-900'">
                    <p class="text-xs font-semibold" :class="message.from_staff ? 'text-ink-600' : 'text-accent-700'" x-text="message.sender"></p>
                    <p class="mt-1 whitespace-pre-line leading-relaxed" x-text="message.body"></p>
                    <template x-if="message.attachment">
                        <a :href="message.attachment.url" class="mt-2 inline-flex text-xs font-semibold text-accent-700 hover:underline" x-text="message.attachment.name"></a>
                    </template>
                    <p class="mt-1.5 text-[11px] text-ink-400" x-text="message.sent_at"></p>
                </div>
            </div>
        </template>
    </div>

    <form method="POST" enctype="multipart/form-data" class="space-y-3 border-t border-ink-100 px-5 py-4"
          action="{{ route('track.chat.reply', ['tracking_number' => $shipment->tracking_number, 'conversation' => $conversation]) }}">
        @csrf

        <div>
            <label for="reply-body" class="sr-only">Message</label>
            <textarea id="reply-body" name="body" rows="3" required maxlength="{{ config('portlane.chat.message_max_length') }}"
                      placeholder="Write a message to the team" class="textarea">{{ old('body') }}</textarea>
            @error('body')<p class="error">{{ $message }}</p>@enderror
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <input type="file" name="attachment" class="input max-w-xs py-1.5 text-xs">
            <button type="submit" class="btn btn-primary btn-sm">Send</button>
        </div>
        @error('attachment')<p class="error">{{ $message }}</p>@enderror
    </form>
</div>
