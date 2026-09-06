@props(['example' => null, 'tone' => 'light', 'autofocus' => false])

<form method="POST" action="{{ route('track.lookup') }}" {{ $attributes->merge(['class' => 'w-full']) }}>
    @csrf

    <div class="flex flex-col gap-2 sm:flex-row">
        <div class="flex-1">
            <label for="tracking_number" class="sr-only">Tracking number</label>
            <input type="text"
                   id="tracking_number"
                   name="tracking_number"
                   value="{{ old('tracking_number') }}"
                   placeholder="{{ $example ? 'e.g. '.$example : 'Enter your tracking number' }}"
                   autocomplete="off"
                   spellcheck="false"
                   @if ($autofocus) autofocus @endif
                   aria-invalid="{{ $errors->has('tracking_number') ? 'true' : 'false' }}"
                   class="input h-12 font-mono tracking-wide uppercase placeholder:normal-case placeholder:font-sans placeholder:tracking-normal">
        </div>

        <button type="submit" class="btn btn-primary h-12 px-6">
            <x-icon name="search" class="h-4 w-4" />
            Track Shipment
        </button>
    </div>

    @error('tracking_number')
        <p class="{{ $tone === 'dark' ? 'mt-2 text-sm text-accent-100' : 'error' }}">{{ $message }}</p>
    @enderror
</form>
