@php $value = fn (string $field, $default = null) => old($field, $review->{$field} ?? $default); @endphp

<x-admin.panel title="Review">
    <div class="grid gap-5 sm:grid-cols-2">
        <x-form.field name="customer_name" label="Client name" :required="true">
            <input type="text" id="customer_name" name="customer_name" value="{{ $value('customer_name') }}" required maxlength="160" class="input">
        </x-form.field>

        <x-form.field name="company" label="Company">
            <input type="text" id="company" name="company" value="{{ $value('company') }}" maxlength="160" class="input">
        </x-form.field>

        <x-form.field name="location" label="Location">
            <input type="text" id="location" name="location" value="{{ $value('location') }}" maxlength="160" class="input">
        </x-form.field>

        <x-form.field name="service_used" label="Service used">
            <input type="text" id="service_used" name="service_used" value="{{ $value('service_used') }}" maxlength="120" class="input">
        </x-form.field>

        <x-form.field name="rating" label="Rating" :required="true">
            <select id="rating" name="rating" required class="select">
                @for ($i = 5; $i >= 1; $i--)
                    <option value="{{ $i }}" @selected((int) $value('rating', 5) === $i)>{{ $i }} out of 5</option>
                @endfor
            </select>
        </x-form.field>

        <x-form.field name="reviewed_on" label="Date given">
            <input type="date" id="reviewed_on" name="reviewed_on" value="{{ old('reviewed_on', $review->reviewed_on?->format('Y-m-d')) }}" class="input">
        </x-form.field>

        <x-form.field name="body" label="Review" :required="true" class="sm:col-span-2">
            <textarea id="body" name="body" rows="6" required maxlength="3000" class="textarea">{{ $value('body') }}</textarea>
        </x-form.field>

        <x-form.field name="photo" label="Photo" help="Optional portrait or company logo.">
            <input type="file" id="photo" name="photo" class="input py-2 text-sm">
            @if ($review->photo_path ?? false)
                <div class="mt-3 flex items-center gap-3">
                    <img src="{{ \App\Services\MediaService::url($review->photo_path) }}" alt="" class="h-12 w-12 rounded-full object-cover">
                    <label class="inline-flex items-center gap-2 text-sm">
                        <input type="checkbox" name="remove_photo" value="1" class="h-4 w-4 rounded border-ink-300">Remove photo
                    </label>
                </div>
            @endif
        </x-form.field>

        <x-form.field name="sort_order" label="Display order">
            <input type="number" id="sort_order" name="sort_order" value="{{ $value('sort_order', 0) }}" min="0" max="999" class="input">
        </x-form.field>
    </div>

    <div class="mt-5 space-y-2.5">
        <label class="inline-flex items-center gap-2 text-sm">
            <input type="checkbox" name="is_published" value="1" class="h-4 w-4 rounded border-ink-300" @checked(old('is_published', $review->is_published ?? false))>
            Published on the website
        </label>
        <label class="flex items-start gap-2 text-sm">
            <input type="checkbox" name="is_sample" value="1" class="mt-0.5 h-4 w-4 rounded border-ink-300" @checked(old('is_sample', $review->is_sample ?? false))>
            <span>Sample content <span class="block text-xs text-ink-500">Labels the entry as sample content on the website. Use for placeholder text.</span></span>
        </label>
    </div>
</x-admin.panel>
