@php $value = fn (string $field, $default = null) => old($field, $page->{$field} ?? $default); @endphp

<x-admin.panel title="Page content">
    <div class="grid gap-5 sm:grid-cols-2">
        <x-form.field name="title" label="Title" :required="true">
            <input type="text" id="title" name="title" value="{{ $value('title') }}" required maxlength="180" class="input">
        </x-form.field>

        <x-form.field name="slug" label="Web address" help="Leave empty to generate from the title.">
            <input type="text" id="slug" name="slug" value="{{ $value('slug') }}" maxlength="200" class="input font-mono"
                   @if ($page->is_system ?? false) readonly @endif>
        </x-form.field>

        <x-form.field name="intro" label="Introduction" class="sm:col-span-2">
            <textarea id="intro" name="intro" rows="2" maxlength="500" class="textarea">{{ $value('intro') }}</textarea>
        </x-form.field>

        <x-form.field name="body" label="Body" class="sm:col-span-2"
                      help="Use ## for a heading, ### for a sub heading, - for bullets, **bold** and [link text](https://example.com). HTML is not rendered.">
            <textarea id="body" name="body" rows="20" maxlength="60000" class="textarea font-mono text-sm">{{ $value('body') }}</textarea>
        </x-form.field>

        <x-form.field name="meta_title" label="Search engine title">
            <input type="text" id="meta_title" name="meta_title" value="{{ $value('meta_title') }}" maxlength="180" class="input">
        </x-form.field>

        <x-form.field name="meta_description" label="Search engine description">
            <input type="text" id="meta_description" name="meta_description" value="{{ $value('meta_description') }}" maxlength="300" class="input">
        </x-form.field>
    </div>

    <label class="mt-5 inline-flex items-center gap-2 text-sm">
        <input type="checkbox" name="is_published" value="1" class="h-4 w-4 rounded border-ink-300" @checked(old('is_published', $page->is_published ?? true))>
        Published
    </label>
</x-admin.panel>
