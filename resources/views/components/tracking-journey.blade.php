@props(['shipment'])

@php
    use App\Support\TrackingIcons;

    $status = $shipment->status;
    $exception = $shipment->isException();
    $delivered = $shipment->isDelivered();

    $percent = $shipment->progressPercent();
    // Kept off both ends so the vehicle never sits on top of a marker.
    $position = $delivered ? 100 : min(max($percent, 4), 94);

    $vehicle = TrackingIcons::vehicleFor($shipment);
    $statusIcon = TrackingIcons::forStatus($status);

    $tone = match (true) {
        $exception => ['rail' => 'bg-caution-700', 'chip' => 'border-caution-700/30 bg-caution-100 text-caution-700'],
        $delivered => ['rail' => 'bg-positive-700', 'chip' => 'border-positive-700/30 bg-positive-100 text-positive-700'],
        default    => ['rail' => 'bg-accent-600', 'chip' => 'border-accent-600/30 bg-accent-50 text-accent-700'],
    };
@endphp

<div {{ $attributes->merge(['class' => 'w-full']) }}>
    <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2">
        <p class="inline-flex items-center gap-2 text-sm font-medium text-ink-700">
            <x-icon :name="$statusIcon" class="h-4 w-4 shrink-0" />
            {{ $status?->publicName() ?? 'In progress' }}
        </p>

        @if ($shipment->estimated_delivery && ! $delivered)
            <p class="text-sm text-ink-500">
                Due {{ $shipment->estimated_delivery->format('j M Y') }}
            </p>
        @endif
    </div>

    {{--
        The rail. The vehicle sits on it at the point the shipment has reached,
        so the answer to "where is my cargo" is legible before a single word is
        read. Exceptions recolour the whole rail rather than adding a badge
        somewhere else on the page.
    --}}
    <div class="relative mt-6 pb-1">
        <div class="h-1.5 w-full rounded-full bg-ink-200" role="progressbar"
             aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100"
             aria-label="Shipment progress: {{ $status?->publicName() ?? 'in progress' }}">
            <div class="h-full rounded-full {{ $tone['rail'] }} transition-[width] duration-700 ease-out"
                 style="width: {{ $position }}%"></div>
        </div>

        {{-- Origin --}}
        <span class="absolute -top-1 left-0 flex h-3.5 w-3.5 -translate-x-1/2 items-center justify-center rounded-full border-2 border-white {{ $tone['rail'] }}"
              aria-hidden="true"></span>

        {{-- Destination --}}
        <span @class([
            'absolute -top-1 right-0 flex h-3.5 w-3.5 translate-x-1/2 items-center justify-center rounded-full border-2 border-white',
            $tone['rail'] => $delivered,
            'bg-ink-300' => ! $delivered,
        ]) aria-hidden="true"></span>

        {{-- The vehicle, sitting where the shipment has got to --}}
        <span class="absolute top-1/2 -translate-x-1/2 -translate-y-1/2 transition-[left] duration-700 ease-out"
              style="left: {{ $position }}%" aria-hidden="true">
            <span @class([
                'flex h-9 w-9 items-center justify-center rounded-full border bg-white shadow-sm',
                'border-caution-700/40 text-caution-700' => $exception,
                'border-positive-700/40 text-positive-700' => ! $exception && $delivered,
                'border-accent-600/40 text-accent-700' => ! $exception && ! $delivered,
                'motion-safe:animate-[journey-bob_3s_ease-in-out_infinite]' => ! $exception && ! $delivered,
            ])>
                <x-icon :name="$exception ? $statusIcon : ($delivered ? 'home' : $vehicle)" class="h-4.5 w-4.5" />
            </span>
        </span>
    </div>

    <div class="mt-4 flex items-start justify-between gap-4 text-xs">
        <p class="max-w-[45%] text-ink-500">
            <span class="block font-semibold uppercase tracking-wide text-ink-400">From</span>
            <x-country-name :name="$shipment->origin_country ?? ''" />
            @if ($shipment->origin_city)<span class="block">{{ $shipment->origin_city }}</span>@endif
        </p>
        <p class="max-w-[45%] text-right text-ink-500">
            <span class="block font-semibold uppercase tracking-wide text-ink-400">To</span>
            <x-country-name :name="$shipment->destination_country ?? ''" />
            @if ($shipment->destination_city)<span class="block">{{ $shipment->destination_city }}</span>@endif
        </p>
    </div>

    @if ($exception && $shipment->exception_note)
        <p class="mt-4 rounded border {{ $tone['chip'] }} p-3 text-sm leading-relaxed">
            {{ $shipment->exception_note }}
        </p>
    @endif
</div>
