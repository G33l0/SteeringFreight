<x-layouts.admin :title="$message->subject">
    <div class="grid gap-6 lg:grid-cols-[1.3fr_0.7fr]">
        <x-admin.panel title="Message">
            <dl class="divide-y divide-ink-50">
                @foreach ([
                    'From' => $message->name,
                    'Email' => $message->email,
                    'Phone' => $message->phone,
                    'Received' => $message->created_at->format('j M Y, H:i'),
                ] as $label => $detail)
                    @if (filled($detail))
                        <div class="flex justify-between gap-4 py-2 text-sm">
                            <dt class="text-ink-500">{{ $label }}</dt>
                            <dd class="text-right font-medium">{{ $detail }}</dd>
                        </div>
                    @endif
                @endforeach
            </dl>

            <div class="mt-4 border-t border-ink-50 pt-4">
                <p class="whitespace-pre-line text-sm leading-relaxed text-ink-700">{{ $message->message }}</p>
            </div>

            <a href="mailto:{{ $message->email }}?subject=Re: {{ urlencode($message->subject) }}" class="btn btn-outline btn-sm mt-5">Reply by email</a>
        </x-admin.panel>

        @can('contact.manage')
            <x-admin.panel title="Handling">
                <form method="POST" action="{{ route('admin.contact-messages.update', $message) }}" class="space-y-5">
                    @csrf
                    @method('PUT')

                    <x-form.field name="status" label="Status" :required="true">
                        <select id="status" name="status" required class="select">
                            @foreach ($statuses as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', $message->status->value) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </x-form.field>

                    <x-form.field name="internal_notes" label="Internal notes">
                        <textarea id="internal_notes" name="internal_notes" rows="6" maxlength="5000" class="textarea">{{ old('internal_notes', $message->internal_notes) }}</textarea>
                    </x-form.field>

                    <button type="submit" class="btn btn-primary btn-sm">Save</button>
                </form>
            </x-admin.panel>
        @endcan
    </div>
</x-layouts.admin>
