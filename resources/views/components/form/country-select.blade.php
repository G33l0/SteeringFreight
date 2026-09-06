@props([
    'name',
    'value' => null,
    'countries' => [],
    'frequent' => [],
    'required' => false,
    'placeholder' => 'Select a country',
])

<select id="{{ $name }}" name="{{ $name }}" @required($required)
        {{ $attributes->merge(['class' => 'select']) }}
        aria-invalid="{{ $errors->has($name) ? 'true' : 'false' }}">
    <option value="">{{ $placeholder }}</option>

    @if (filled($frequent))
        <optgroup label="Frequently shipped">
            @foreach ($frequent as $country)
                <option value="{{ $country }}" @selected(old($name, $value) === $country)>{{ $country }}</option>
            @endforeach
        </optgroup>
    @endif

    <optgroup label="All countries">
        @foreach ($countries as $country)
            <option value="{{ $country }}" @selected(old($name, $value) === $country)>{{ $country }}</option>
        @endforeach
    </optgroup>
</select>
