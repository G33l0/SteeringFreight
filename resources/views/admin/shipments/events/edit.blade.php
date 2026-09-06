<x-layouts.admin title="Edit tracking update">
    <form method="POST" action="{{ route('admin.shipments.events.update', [$shipment, $event]) }}" class="max-w-3xl">
        @csrf
        @method('PUT')

        <x-admin.panel :title="'Update on '.$shipment->tracking_number">
            <div class="grid gap-5 sm:grid-cols-2">
                <x-form.field name="shipment_status_id" label="Status" :required="true">
                    <select id="shipment_status_id" name="shipment_status_id" required class="select">
                        @foreach ($statuses as $status)
                            <option value="{{ $status->id }}" @selected(old('shipment_status_id', $event->shipment_status_id) === $status->id)>
                                {{ $status->name }}
                            </option>
                        @endforeach
                    </select>
                </x-form.field>

                <x-form.field name="location" label="Location">
                    <input type="text" id="location" name="location" value="{{ old('location', $event->location) }}" maxlength="200" class="input">
                </x-form.field>

                <x-form.field name="occurred_at" label="Date and time" :required="true">
                    <input type="datetime-local" id="occurred_at" name="occurred_at" required class="input"
                           value="{{ old('occurred_at', $event->occurred_at->format('Y-m-d\TH:i')) }}">
                </x-form.field>
            </div>

            <x-form.field name="description" label="Public description" class="mt-5">
                <textarea id="description" name="description" rows="3" maxlength="2000" class="textarea">{{ old('description', $event->description) }}</textarea>
            </x-form.field>

            <x-form.field name="internal_note" label="Internal note" class="mt-5">
                <textarea id="internal_note" name="internal_note" rows="2" maxlength="2000" class="textarea">{{ old('internal_note', $event->internal_note) }}</textarea>
            </x-form.field>

            <label class="mt-5 inline-flex items-center gap-2 text-sm">
                <input type="checkbox" name="is_public" value="1" class="h-4 w-4 rounded border-ink-300" @checked(old('is_public', $event->is_public))>
                Show on the tracking page
            </label>
        </x-admin.panel>

        <div class="mt-6 flex flex-wrap gap-3">
            <button type="submit" class="btn btn-primary">Save update</button>
            <a href="{{ route('admin.shipments.show', $shipment) }}" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</x-layouts.admin>
