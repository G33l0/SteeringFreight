<x-layouts.admin title="Site settings">
    <div class="flex flex-wrap gap-2">
        @foreach ($groups as $key => $label)
            <a href="{{ route('admin.settings.edit', $key) }}"
               @class(['btn btn-sm', 'btn-dark' => $key === $group, 'btn-outline' => $key !== $group])>{{ $label }}</a>
        @endforeach
    </div>

    <form method="POST" action="{{ route('admin.settings.update', $group) }}" enctype="multipart/form-data" class="mt-5 max-w-3xl">
        @csrf
        @method('PUT')

        <x-admin.panel :title="$groups[$group]">
            <div class="space-y-5">
                @foreach ($definitions as $key => $definition)
                    @php
                        $field = str_replace('.', '_', $key);
                        $current = old($field, $values[$key] ?? null);
                    @endphp

                    @if ($definition['type'] === \App\Support\SettingDefinitions::TYPE_BOOLEAN)
                        <label class="flex items-start gap-2 text-sm">
                            <input type="checkbox" name="{{ $field }}" value="1" class="mt-0.5 h-4 w-4 rounded border-ink-300" @checked($current)>
                            <span>
                                {{ $definition['label'] }}
                                @isset($definition['help'])
                                    <span class="block text-xs text-ink-500">{{ $definition['help'] }}</span>
                                @endisset
                            </span>
                        </label>
                    @elseif ($definition['type'] === \App\Support\SettingDefinitions::TYPE_IMAGE)
                        <x-form.field :name="$field" :label="$definition['label']" :help="$definition['help'] ?? null" :optionalHint="false">
                            <input type="file" id="{{ $field }}" name="{{ $field }}" class="input py-2 text-sm">
                            @if ($current)
                                <div class="mt-3 flex items-center gap-3">
                                    <img src="{{ \App\Services\MediaService::url($current) }}" alt="" class="h-14 rounded border border-ink-100 bg-white object-contain p-1">
                                    <label class="inline-flex items-center gap-2 text-sm">
                                        <input type="checkbox" name="remove_{{ $field }}" value="1" class="h-4 w-4 rounded border-ink-300">Remove
                                    </label>
                                </div>
                            @endif
                        </x-form.field>
                    @elseif ($definition['type'] === \App\Support\SettingDefinitions::TYPE_COLOUR)
                        <x-form.field :name="$field" :label="$definition['label']" :help="$definition['help'] ?? null" :optionalHint="false">
                            <div class="flex items-center gap-3">
                                <input type="color" id="{{ $field }}_picker" value="{{ $current ?: '#000000' }}"
                                       class="h-10 w-14 cursor-pointer rounded border border-ink-200 bg-white p-1"
                                       aria-label="{{ $definition['label'] }} colour picker"
                                       oninput="document.getElementById('{{ $field }}').value = this.value">
                                <input type="text" id="{{ $field }}" name="{{ $field }}" value="{{ $current }}"
                                       maxlength="7" pattern="#[0-9a-fA-F]{6}" placeholder="#0c1f2e"
                                       class="input max-w-40 font-mono uppercase"
                                       oninput="document.getElementById('{{ $field }}_picker').value = this.value">
                            </div>
                        </x-form.field>
                    @elseif ($definition['type'] === \App\Support\SettingDefinitions::TYPE_JSON)
                        <x-form.field :name="$field" :label="$definition['label']" :help="$definition['help'] ?? null" :optionalHint="false">
                            <textarea id="{{ $field }}" name="{{ $field }}" rows="6" maxlength="8000" class="textarea font-mono text-sm">{{ is_array($current) ? collect($current)->map(fn ($row) => ($row['title'] ?? '').' | '.($row['body'] ?? ''))->implode("\n") : $current }}</textarea>
                        </x-form.field>
                    @elseif ($definition['type'] === \App\Support\SettingDefinitions::TYPE_TEXT)
                        <x-form.field :name="$field" :label="$definition['label']" :help="$definition['help'] ?? null" :optionalHint="false">
                            <textarea id="{{ $field }}" name="{{ $field }}" rows="{{ $definition['rows'] ?? 3 }}" maxlength="5000" class="textarea">{{ $current }}</textarea>
                        </x-form.field>
                    @elseif ($definition['type'] === \App\Support\SettingDefinitions::TYPE_INTEGER)
                        <x-form.field :name="$field" :label="$definition['label']" :help="$definition['help'] ?? null" :optionalHint="false">
                            <input type="number" id="{{ $field }}" name="{{ $field }}" value="{{ $current }}" min="0" class="input sm:max-w-40">
                        </x-form.field>
                    @else
                        <x-form.field :name="$field" :label="$definition['label']" :help="$definition['help'] ?? null" :optionalHint="false">
                            <input type="text" id="{{ $field }}" name="{{ $field }}" value="{{ $current }}" maxlength="500" class="input">
                        </x-form.field>
                    @endif
                @endforeach
            </div>
        </x-admin.panel>

        <div class="mt-6">
            <button type="submit" class="btn btn-primary">Save settings</button>
        </div>
    </form>
</x-layouts.admin>
