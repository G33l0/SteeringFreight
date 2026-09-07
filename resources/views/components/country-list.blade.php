@props(['text'])

@php
    $places = collect(explode(',', (string) $text))
        ->map(fn ($place) => trim($place))
        ->filter()
        ->values();

    // Only rendered as a list of places when at least one entry is a country we
    // recognise. Anything else is the operator's own prose and is left as it was
    // written, so existing copy keeps reading the way it did.
    $isCountryList = $places->contains(fn (string $place) => \App\Support\Countries::flag($place) !== null);
@endphp

@if ($isCountryList)
    <ul {{ $attributes->merge(['class' => 'flex flex-wrap gap-x-4 gap-y-1']) }}>
        @foreach ($places as $place)
            <li><x-country-name :name="$place" /></li>
        @endforeach
    </ul>
@else
    <span {{ $attributes }}>{{ trim((string) $text) }}</span>
@endif
