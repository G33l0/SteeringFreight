@php
    $items = \App\Support\LaunchChecklist::items();
    $outstanding = collect($items)->reject(fn (array $item) => $item['done']);
@endphp

@if ($outstanding->isNotEmpty())
    <x-admin.panel title="Before you go live" :description="$outstanding->count().' of '.count($items).' items still to do'"
                   {{ $attributes }} compact>
        <ul class="divide-y divide-ink-50">
            @foreach ($items as $item)
                <li class="flex items-start gap-3 px-4 py-3 sm:px-5">
                    <span @class([
                        'mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full border',
                        'border-positive-700 bg-positive-700 text-white' => $item['done'],
                        'border-ink-300' => ! $item['done'],
                    ])>
                        @if ($item['done'])
                            <x-icon name="check" class="h-3 w-3" stroke-width="3" />
                        @endif
                    </span>

                    <div class="min-w-0 flex-1">
                        <p @class(['text-sm font-medium', 'text-ink-400 line-through' => $item['done']])>{{ $item['label'] }}</p>
                        @unless ($item['done'])
                            <p class="mt-0.5 text-sm text-ink-500">{{ $item['help'] }}</p>
                        @endunless
                    </div>

                    @if (! $item['done'] && $item['url'])
                        <a href="{{ $item['url'] }}" class="shrink-0 text-sm font-medium text-accent-700 hover:underline">Open</a>
                    @endif
                </li>
            @endforeach
        </ul>
    </x-admin.panel>
@endif
