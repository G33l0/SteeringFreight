@php $value = fn (string $field, $default = null) => old($field, $faq->{$field} ?? $default); @endphp

<x-admin.panel title="Question">
    <div class="grid gap-5 sm:grid-cols-2">
        <x-form.field name="question" label="Question" :required="true" class="sm:col-span-2">
            <input type="text" id="question" name="question" value="{{ $value('question') }}" required maxlength="300" class="input">
        </x-form.field>

        <x-form.field name="answer" label="Answer" :required="true" class="sm:col-span-2"
                      help="Use - for bullet points and a blank line between paragraphs.">
            <textarea id="answer" name="answer" rows="8" required maxlength="5000" class="textarea">{{ $value('answer') }}</textarea>
        </x-form.field>

        <x-form.field name="category" label="Category" help="Groups questions on the FAQ page. Use a service title to show it on that service page.">
            <input type="text" id="category" name="category" value="{{ $value('category') }}" maxlength="80" class="input">
        </x-form.field>

        <x-form.field name="sort_order" label="Display order">
            <input type="number" id="sort_order" name="sort_order" value="{{ $value('sort_order', 0) }}" min="0" max="999" class="input">
        </x-form.field>
    </div>

    <div class="mt-5 space-y-2.5">
        <label class="inline-flex items-center gap-2 text-sm">
            <input type="checkbox" name="is_published" value="1" class="h-4 w-4 rounded border-ink-300" @checked(old('is_published', $faq->is_published ?? true))>
            Published
        </label>
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="show_on_home" value="1" class="h-4 w-4 rounded border-ink-300" @checked(old('show_on_home', $faq->show_on_home ?? false))>
            Show in the homepage question list
        </label>
    </div>
</x-admin.panel>
