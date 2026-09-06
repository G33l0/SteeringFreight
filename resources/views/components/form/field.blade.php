@props(['name', 'label', 'help' => null, 'required' => false, 'optionalHint' => true])

<div {{ $attributes->only('class') }}>
    <label class="label" for="{{ $name }}">
        {{ $label }}
        @unless ($required)
            @if ($optionalHint)
                <span class="font-normal text-ink-400">(optional)</span>
            @endif
        @endunless
    </label>

    {{ $slot }}

    @if ($help)
        <p class="help">{{ $help }}</p>
    @endif

    @error($name)
        <p class="error">{{ $message }}</p>
    @enderror
</div>
