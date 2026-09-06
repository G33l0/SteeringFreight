@php $value = fn (string $field, $default = null) => old($field, $status->{$field} ?? $default); @endphp

<x-admin.panel title="Status">
    <div class="grid gap-5 sm:grid-cols-2">
        <x-form.field name="name" label="Name" :required="true">
            <input type="text" id="name" name="name" value="{{ $value('name') }}" required maxlength="120" class="input">
        </x-form.field>

        <x-form.field name="slug" label="Slug" help="Used internally. Leave empty to generate from the name.">
            <input type="text" id="slug" name="slug" value="{{ $value('slug') }}" maxlength="140" class="input font-mono">
        </x-form.field>

        <x-form.field name="category" label="Category" :required="true">
            <select id="category" name="category" required class="select">
                @foreach (\App\Enums\StatusCategory::options() as $catValue => $catLabel)
                    <option value="{{ $catValue }}" @selected(($value('category') instanceof \App\Enums\StatusCategory ? $value('category')->value : $value('category')) === $catValue)>
                        {{ $catLabel }}
                    </option>
                @endforeach
            </select>
        </x-form.field>

        <x-form.field name="stage" label="Timeline position" help="Milestones only. Lower numbers come first on the timeline.">
            <input type="number" id="stage" name="stage" value="{{ $value('stage') }}" min="1" max="999" class="input">
        </x-form.field>

        <x-form.field name="sort_order" label="Sort order" help="Used when listing statuses in the admin panel.">
            <input type="number" id="sort_order" name="sort_order" value="{{ $value('sort_order', 0) }}" min="0" max="999" class="input">
        </x-form.field>

        <x-form.field name="colour" label="Colour" :required="true">
            <select id="colour" name="colour" required class="select">
                @foreach ($colours as $colour)
                    <option value="{{ $colour }}" @selected($value('colour') === $colour)>{{ ucfirst($colour) }}</option>
                @endforeach
            </select>
        </x-form.field>

        <x-form.field name="customer_label" label="Customer facing label" class="sm:col-span-2"
                      help="Optional. Use when the customer should see different wording from the internal name.">
            <input type="text" id="customer_label" name="customer_label" value="{{ $value('customer_label') }}" maxlength="160" class="input">
        </x-form.field>

        <x-form.field name="description" label="Description" class="sm:col-span-2"
                      help="Used as the default wording when this status is applied to a shipment.">
            <textarea id="description" name="description" rows="3" maxlength="1000" class="textarea">{{ $value('description') }}</textarea>
        </x-form.field>
    </div>

    <div class="mt-5 space-y-2.5">
        <label class="flex items-start gap-2 text-sm">
            <input type="checkbox" name="is_active" value="1" class="mt-0.5 h-4 w-4 rounded border-ink-300" @checked(old('is_active', $status->is_active ?? true))>
            <span>Active <span class="block text-xs text-ink-500">Inactive statuses stay on existing shipments but cannot be chosen for new updates.</span></span>
        </label>

        <label class="flex items-start gap-2 text-sm">
            <input type="checkbox" name="is_final" value="1" class="mt-0.5 h-4 w-4 rounded border-ink-300" @checked(old('is_final', $status->is_final ?? false))>
            <span>Final status <span class="block text-xs text-ink-500">Marks the shipment as completed, for example Delivered.</span></span>
        </label>

        <label class="flex items-start gap-2 text-sm">
            <input type="checkbox" name="notify_customer" value="1" class="mt-0.5 h-4 w-4 rounded border-ink-300" @checked(old('notify_customer', $status->notify_customer ?? false))>
            <span>Email the customer <span class="block text-xs text-ink-500">Only sends when notifications are switched on in the site settings.</span></span>
        </label>

        <label class="flex items-start gap-2 text-sm">
            <input type="checkbox" name="requires_explanation" value="1" class="mt-0.5 h-4 w-4 rounded border-ink-300" @checked(old('requires_explanation', $status->requires_explanation ?? false))>
            <span>Requires a written explanation <span class="block text-xs text-ink-500">The tracking update cannot be saved without a description for the customer.</span></span>
        </label>
    </div>
</x-admin.panel>
