<x-layouts.public :metaTitle="$metaTitle" :metaDescription="$metaDescription" :robots="$robots">
    @php
        $status = $shipment->status;
        $progress = $shipment->progressPercent();
        $details = collect([
            'Origin' => $shipment->originLabel(),
            'Destination' => $shipment->destinationLabel(),
            'Shipping method' => $shipment->shipping_method?->label(),
            'Service level' => $shipment->service_level,
            'Cargo' => $shipment->cargo_description,
            'Packages' => $shipment->package_count,
            'Weight' => $shipment->weight_kg ? rtrim(rtrim(number_format((float) $shipment->weight_kg, 2), '0'), '.').' kg' : null,
            'Dimensions' => $shipment->dimensions,
            'Container number' => $shipment->container_number,
            'Vessel' => $shipment->vessel_name,
            'Voyage number' => $shipment->voyage_number,
            'Air waybill' => $shipment->air_waybill_number,
            'Flight' => $shipment->flight_number,
            'Bill of lading' => $shipment->bill_of_lading_number,
            'Estimated departure' => $shipment->estimated_departure?->format('j M Y'),
            'Estimated arrival' => $shipment->estimated_arrival?->format('j M Y'),
        ])->filter(fn ($value) => filled($value));
    @endphp

    <section class="border-b border-ink-100 bg-ink-50">
        <div class="mx-auto max-w-5xl px-6 py-10">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-ink-500">Tracking number</p>
                    <h1 class="mt-1 font-mono text-2xl font-semibold tracking-wide sm:text-3xl">{{ $shipment->tracking_number }}</h1>
                    <p class="mt-2 text-sm text-ink-600">{{ $shipment->routeLabel() ?: 'Route to be confirmed' }}</p>
                </div>

                <div class="text-right">
                    <x-status-badge :status="$status" class="text-sm" />
                    <p class="mt-2 text-xs text-ink-500">
                        Last updated {{ ($shipment->status_updated_at ?? $shipment->updated_at)?->diffForHumans() }}
                    </p>
                </div>
            </div>

            @if ($shipment->is_sample)
                <x-alert type="info" class="mt-5">
                    This is a demonstration shipment created by the sample data seeder. It does not represent a real consignment.
                </x-alert>
            @endif

            @if ($shipment->isException() && $shipment->exception_note)
                <x-alert type="error" class="mt-5">
                    <strong class="font-semibold">{{ $status?->publicName() }}.</strong>
                    {{ $shipment->exception_note }}
                </x-alert>
            @endif

            <div class="mt-8 grid gap-4 sm:grid-cols-3">
                <div class="border border-ink-200 bg-white p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-ink-500">Current location</p>
                    <p class="mt-1.5 font-medium">{{ $shipment->current_location ?: 'Not reported yet' }}</p>
                </div>
                <div class="border border-ink-200 bg-white p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-ink-500">Estimated delivery</p>
                    <p class="mt-1.5 font-medium">{{ $shipment->estimated_delivery?->format('j F Y') ?: 'To be confirmed' }}</p>
                </div>
                <div class="border border-ink-200 bg-white p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-ink-500">Shipping method</p>
                    <p class="mt-1.5 font-medium">{{ $shipment->shipping_method?->label() ?: 'Not specified' }}</p>
                </div>
            </div>

            <div class="mt-8">
                <div class="flex items-center justify-between text-sm">
                    <p class="font-medium text-ink-700">Shipment progress</p>
                    <p class="text-ink-500">{{ $progress }}%</p>
                </div>
                <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-ink-200" role="progressbar"
                     aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100"
                     aria-label="Shipment progress">
                    <div class="h-full rounded-full bg-accent-600 transition-[width] duration-500" style="width: {{ max($progress, 2) }}%"></div>
                </div>
            </div>
        </div>
    </section>

    <div class="mx-auto grid max-w-5xl gap-10 px-6 py-12 lg:grid-cols-[1.25fr_0.75fr]">
        <div class="space-y-10">
            {{-- Milestones --}}
            @if ($timeline->isNotEmpty())
                <section>
                    <h2 class="font-display text-lg font-semibold">Shipment stages</h2>
                    <ol class="mt-5 space-y-0">
                        @foreach ($timeline as $milestone)
                            @php
                                $reached = $shipment->progress_stage >= $milestone->stage;
                                $current = ! $shipment->isException()
                                    && $status?->stage === $milestone->stage;
                            @endphp
                            <li class="relative flex gap-4 pb-5 last:pb-0">
                                @unless ($loop->last)
                                    <span class="absolute left-[11px] top-6 h-[calc(100%-1.25rem)] w-px {{ $reached ? 'bg-accent-600/50' : 'bg-ink-200' }}"></span>
                                @endunless

                                <span @class([
                                    'relative z-10 mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full border',
                                    'border-accent-600 bg-accent-600 text-white' => $reached,
                                    'border-ink-300 bg-white' => ! $reached,
                                ])>
                                    @if ($reached)
                                        <x-icon name="check" class="h-3.5 w-3.5" stroke-width="2.5" />
                                    @endif
                                </span>

                                <div class="min-w-0">
                                    <p @class(['font-medium', 'text-ink-950' => $reached, 'text-ink-400' => ! $reached])>
                                        {{ $milestone->publicName() }}
                                        @if ($current)
                                            <span class="ml-1 text-xs font-semibold uppercase tracking-wide text-accent-700">Current</span>
                                        @endif
                                    </p>
                                    @if ($milestone->description && ($current || $loop->first))
                                        <p class="mt-1 text-sm text-ink-600">{{ $milestone->description }}</p>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </section>
            @endif

            {{-- Tracking history --}}
            <section>
                <h2 class="font-display text-lg font-semibold">Tracking history</h2>

                @if ($events->isEmpty())
                    <p class="mt-4 text-sm text-ink-600">No tracking updates have been recorded for this shipment yet.</p>
                @else
                    <ol class="mt-5 divide-y divide-ink-100 border-y border-ink-100">
                        @foreach ($events as $event)
                            <li class="flex flex-col gap-1.5 py-4 sm:flex-row sm:gap-6">
                                <div class="sm:w-40 sm:shrink-0">
                                    <p class="text-sm font-medium text-ink-900">{{ $event->occurred_at->format('j M Y') }}</p>
                                    <p class="text-xs text-ink-500">{{ $event->occurred_at->format('H:i') }}</p>
                                </div>
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <x-status-badge :status="$event->status" />
                                        @if ($event->location)
                                            <span class="inline-flex items-center gap-1 text-sm text-ink-600">
                                                <x-icon name="pin" class="h-3.5 w-3.5 text-ink-400" />{{ $event->location }}
                                            </span>
                                        @endif
                                    </div>
                                    @if ($event->description)
                                        <p class="mt-2 text-sm leading-relaxed text-ink-700">{{ $event->description }}</p>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </section>

            {{-- Customer chat --}}
            @if ($chatEnabled)
                <section id="conversation" class="scroll-mt-24">
                    <h2 class="font-display text-lg font-semibold">Contact the shipping team</h2>
                    <p class="mt-2 text-sm text-ink-600">{{ setting('tracking.support_note') }}</p>
                    <x-chat-notice class="mt-2 max-w-2xl" />

                    @if (session('status'))
                        <x-alert class="mt-4">{{ session('status') }}</x-alert>
                    @endif

                    @if ($conversation)
                        @include('public.track.partials.conversation', ['conversation' => $conversation, 'messages' => $messages])
                    @else
                        @include('public.track.partials.start-conversation', ['shipment' => $shipment])
                    @endif
                </section>
            @endif
        </div>

        <aside class="space-y-8">
            <div class="border border-ink-100 bg-white">
                <h2 class="border-b border-ink-100 px-5 py-3 font-display text-base font-semibold">Shipment details</h2>
                <dl class="divide-y divide-ink-50 px-5">
                    @foreach ($details as $label => $value)
                        <div class="flex justify-between gap-4 py-2.5 text-sm">
                            <dt class="text-ink-500">{{ $label }}</dt>
                            <dd class="text-right font-medium text-ink-900">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>

            @if ($documents->isNotEmpty())
                <div class="border border-ink-100 bg-white">
                    <h2 class="border-b border-ink-100 px-5 py-3 font-display text-base font-semibold">Documents</h2>
                    <ul class="divide-y divide-ink-50">
                        @foreach ($documents as $document)
                            <li class="px-5 py-3">
                                <a href="{{ route('track.documents.download', ['tracking_number' => $shipment->tracking_number, 'document' => $document]) }}"
                                   class="flex items-start gap-2 text-sm font-medium text-accent-700 hover:underline">
                                    <x-icon name="download" class="mt-0.5 h-4 w-4 shrink-0" />
                                    <span>
                                        {{ $document->title }}
                                        <span class="block text-xs font-normal text-ink-500">{{ $document->type->label() }} · {{ $document->readableSize() }}</span>
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="border border-ink-100 bg-ink-50 p-5">
                <h2 class="font-display text-base font-semibold">Need help?</h2>
                <p class="mt-2 text-sm leading-relaxed text-ink-600">
                    Our operations desk is open {{ setting('contact.hours_weekdays') }} on weekdays.
                </p>
                <div class="mt-3 space-y-1.5 text-sm">
                    @if ($phone = setting('contact.phone'))
                        <p><a href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}" class="font-medium text-accent-700 hover:underline">{{ $phone }}</a></p>
                    @endif
                    @if ($email = setting('contact.email'))
                        <p><a href="mailto:{{ $email }}" class="font-medium text-accent-700 hover:underline">{{ $email }}</a></p>
                    @endif
                </div>

                @if (filled($extraDetails = settings()->list('contact.extra_details')))
                    <dl class="mt-3 space-y-2 border-t border-ink-200 pt-3 text-sm">
                        @foreach ($extraDetails as $detail)
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wide text-ink-500">{{ $detail['title'] ?? '' }}</dt>
                                <dd class="mt-0.5 text-ink-800">{{ $detail['body'] ?? '' }}</dd>
                            </div>
                        @endforeach
                    </dl>
                @endif
            </div>

            <div>
                <a href="{{ route('track.index') }}" class="btn btn-outline btn-sm w-full">Track another shipment</a>
            </div>
        </aside>
    </div>
</x-layouts.public>
