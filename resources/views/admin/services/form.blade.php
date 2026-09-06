@php
    $value = fn (string $field, $default = null) => old($field, $service->{$field} ?? $default);
    $highlights = old('highlights_text', collect($service->highlights ?? [])->implode("\n"));
@endphp

<x-admin.panel title="Service">
    <div class="grid gap-5 sm:grid-cols-2">
        <x-form.field name="title" label="Title" :required="true">
            <input type="text" id="title" name="title" value="{{ $value('title') }}" required maxlength="160" class="input">
        </x-form.field>

        <x-form.field name="slug" label="Web address" help="Leave empty to generate from the title.">
            <input type="text" id="slug" name="slug" value="{{ $value('slug') }}" maxlength="180" class="input font-mono">
        </x-form.field>

        <x-form.field name="summary" label="Short description" :required="true" class="sm:col-span-2"
                      help="Shown on the services list and homepage. One or two sentences.">
            <textarea id="summary" name="summary" rows="2" required maxlength="400" class="textarea">{{ $value('summary') }}</textarea>
        </x-form.field>

        <x-form.field name="description" label="Full description" class="sm:col-span-2"
                      help="Use ## for a heading, - for bullet points and a blank line between paragraphs.">
            <textarea id="description" name="description" rows="12" maxlength="20000" class="textarea font-mono text-sm">{{ $value('description') }}</textarea>
        </x-form.field>

        <x-form.field name="highlights_text" label="Highlights" help="One per line. Shown as a checked list.">
            <textarea id="highlights_text" name="highlights_text" rows="5" maxlength="2000" class="textarea">{{ $highlights }}</textarea>
        </x-form.field>

        <div class="space-y-5">
            <x-form.field name="icon" label="Icon" :required="true">
                <select id="icon" name="icon" required class="select">
                    @foreach ($icons as $icon)
                        <option value="{{ $icon }}" @selected($value('icon') === $icon)>{{ ucfirst($icon) }}</option>
                    @endforeach
                </select>
            </x-form.field>

            <x-form.field name="sort_order" label="Display order">
                <input type="number" id="sort_order" name="sort_order" value="{{ $value('sort_order', 0) }}" min="0" max="999" class="input">
            </x-form.field>
        </div>

        <x-form.field name="image" label="Image"
                      help="JPG, PNG or WebP up to {{ round(config('portlane.uploads.image_max_kb') / 1024) }} MB.">
            <input type="file" id="image" name="image" class="input py-2 text-sm">
            @if ($service->image_path ?? false)
                <div class="mt-3 flex items-center gap-3">
                    <img src="{{ \App\Services\MediaService::url($service->image_path) }}" alt="" class="h-16 w-24 rounded object-cover">
                    <label class="inline-flex items-center gap-2 text-sm">
                        <input type="checkbox" name="remove_image" value="1" class="h-4 w-4 rounded border-ink-300">
                        Remove image
                    </label>
                </div>
            @endif
        </x-form.field>

        <x-form.field name="image_alt" label="Image description" help="Describes the image for screen readers.">
            <input type="text" id="image_alt" name="image_alt" value="{{ $value('image_alt') }}" maxlength="200" class="input">
        </x-form.field>

        <x-form.field name="meta_title" label="Search engine title">
            <input type="text" id="meta_title" name="meta_title" value="{{ $value('meta_title') }}" maxlength="180" class="input">
        </x-form.field>

        <x-form.field name="meta_description" label="Search engine description">
            <input type="text" id="meta_description" name="meta_description" value="{{ $value('meta_description') }}" maxlength="300" class="input">
        </x-form.field>
    </div>

    <div class="mt-5 space-y-2.5">
        <label class="inline-flex items-center gap-2 text-sm">
            <input type="checkbox" name="is_published" value="1" class="h-4 w-4 rounded border-ink-300" @checked(old('is_published', $service->is_published ?? true))>
            Published on the website
        </label>
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="show_on_home" value="1" class="h-4 w-4 rounded border-ink-300" @checked(old('show_on_home', $service->show_on_home ?? true))>
            Show in the homepage services grid
        </label>
    </div>
</x-admin.panel>
