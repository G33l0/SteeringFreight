<x-layouts.admin :title="$shipment->tracking_number">
    <x-slot:actions>
        <a href="{{ route('track.show', $shipment->tracking_number) }}" target="_blank" rel="noopener" class="btn btn-outline btn-sm">
            View tracking page
        </a>
        @can('update', $shipment)
            <a href="#add-update" class="btn btn-primary btn-sm">Add tracking update</a>
            <a href="{{ route('admin.shipments.edit', $shipment) }}" class="btn btn-outline btn-sm">Edit</a>
        @endcan
    </x-slot:actions>

    @if ($shipment->isArchived())
        <x-alert type="info" class="mb-5">
            This shipment is archived and cannot be edited.
            @can('archive', $shipment)
                <form method="POST" action="{{ route('admin.shipments.restore', $shipment) }}" class="mt-2">
                    @csrf
                    <button type="submit" class="btn btn-outline btn-sm">Restore shipment</button>
                </form>
            @endcan
        </x-alert>
    @endif

    <div class="grid gap-6 lg:grid-cols-[1.4fr_0.6fr]">
        <div class="space-y-6">
            <x-admin.panel>
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="font-mono text-xl font-semibold">{{ $shipment->tracking_number }}</p>
                        <p class="mt-1 text-sm text-ink-600">{{ $shipment->routeLabel() ?: 'Route not set' }}</p>
                    </div>
                    <div class="text-right">
                        <x-status-badge :status="$shipment->status" />
                        <p class="mt-1.5 text-xs text-ink-500">
                            Updated {{ ($shipment->status_updated_at ?? $shipment->updated_at)->diffForHumans() }}
                        </p>
                    </div>
                </div>

                <dl class="mt-5 grid gap-4 border-t border-ink-50 pt-4 sm:grid-cols-3">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-ink-500">Current location</dt>
                        <dd class="mt-1 text-sm">{{ $shipment->current_location ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-ink-500">Estimated delivery</dt>
                        <dd class="mt-1 text-sm">{{ $shipment->estimated_delivery?->format('j M Y') ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-ink-500">Progress</dt>
                        <dd class="mt-1 text-sm">{{ $shipment->progressPercent() }}%</dd>
                    </div>
                </dl>
            </x-admin.panel>

            @can('manageEvents', $shipment)
                <x-admin.panel title="Add tracking update" id="add-update">
                    <form method="POST" action="{{ route('admin.shipments.events.store', $shipment) }}" class="space-y-5">
                        @csrf

                        <div class="grid gap-5 sm:grid-cols-2">
                            <x-form.field name="shipment_status_id" label="Status" :required="true">
                                <select id="shipment_status_id" name="shipment_status_id" required class="select">
                                    @foreach ($statuses as $status)
                                        <option value="{{ $status->id }}"
                                            @selected(old('shipment_status_id', $shipment->shipment_status_id) === $status->id)>
                                            {{ $status->name }}{{ $status->isException() ? ' (exception)' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </x-form.field>

                            <x-form.field name="location" label="Location" help="For example: Port of Rotterdam, or Atlantic Ocean.">
                                <input type="text" id="location" name="location" value="{{ old('location', $shipment->current_location) }}" maxlength="200" class="input">
                            </x-form.field>

                            <x-form.field name="occurred_at" label="Date and time" :required="true">
                                <input type="datetime-local" id="occurred_at" name="occurred_at" required
                                       value="{{ old('occurred_at', now()->format('Y-m-d\TH:i')) }}" class="input">
                            </x-form.field>
                        </div>

                        <x-form.field name="description" label="Public description"
                                      help="This text is shown to the customer. Write what happened in plain language.">
                            <textarea id="description" name="description" rows="3" maxlength="2000" class="textarea">{{ old('description') }}</textarea>
                        </x-form.field>

                        <x-form.field name="internal_note" label="Internal note" help="Never shown to the customer.">
                            <textarea id="internal_note" name="internal_note" rows="2" maxlength="2000" class="textarea">{{ old('internal_note') }}</textarea>
                        </x-form.field>

                        <div class="flex flex-wrap gap-5">
                            <label class="inline-flex items-center gap-2 text-sm">
                                <input type="checkbox" name="is_public" value="1" class="h-4 w-4 rounded border-ink-300" @checked(old('is_public', true))>
                                Show on the tracking page
                            </label>
                            <label class="inline-flex items-center gap-2 text-sm">
                                <input type="checkbox" name="update_shipment" value="1" class="h-4 w-4 rounded border-ink-300" @checked(old('update_shipment', true))>
                                Move the shipment to this status
                            </label>
                            <label class="inline-flex items-center gap-2 text-sm">
                                <input type="checkbox" name="notify_customer" value="1" class="h-4 w-4 rounded border-ink-300" @checked(old('notify_customer', true))>
                                Email the customer if the status is set to notify
                            </label>
                        </div>

                        <button type="submit" class="btn btn-primary btn-sm">Add update</button>
                    </form>
                </x-admin.panel>
            @endcan

            <x-admin.panel title="Tracking history" compact>
                @if ($shipment->events->isEmpty())
                    <x-admin.empty message="No tracking updates recorded yet." />
                @else
                    <ul class="divide-y divide-ink-50">
                        @foreach ($shipment->events as $event)
                            <li class="px-4 py-3 sm:px-5">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <x-status-badge :status="$event->status" />
                                            @unless ($event->is_public)
                                                <span class="badge badge-slate">Internal only</span>
                                            @endunless
                                            @if ($event->notified_customer)
                                                <span class="badge badge-blue">Customer emailed</span>
                                            @endif
                                        </div>
                                        <p class="mt-1.5 text-sm text-ink-600">
                                            {{ $event->occurred_at->format('j M Y, H:i') }}
                                            @if ($event->location) · {{ $event->location }} @endif
                                            @if ($event->creator) · {{ $event->creator->name }} @endif
                                        </p>
                                        @if ($event->description)
                                            <p class="mt-2 text-sm">{{ $event->description }}</p>
                                        @endif
                                        @if ($event->internal_note)
                                            <p class="mt-2 border-l-2 border-ink-200 pl-3 text-sm text-ink-500">
                                                Internal: {{ $event->internal_note }}
                                            </p>
                                        @endif
                                    </div>

                                    @can('manageEvents', $shipment)
                                        <div class="flex shrink-0 gap-2">
                                            <a href="{{ route('admin.shipments.events.edit', [$shipment, $event]) }}" class="text-sm text-ink-600 hover:text-accent-700">Edit</a>
                                            <form method="POST" action="{{ route('admin.shipments.events.destroy', [$shipment, $event]) }}"
                                                  onsubmit="return confirm('Delete this tracking update?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-sm text-alert-700 hover:underline">Delete</button>
                                            </form>
                                        </div>
                                    @endcan
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-admin.panel>

            <x-admin.panel title="Documents" compact>
                @can('manageDocuments', $shipment)
                    <form method="POST" action="{{ route('admin.shipments.documents.store', $shipment) }}"
                          enctype="multipart/form-data" class="grid gap-4 border-b border-ink-100 p-4 sm:grid-cols-2 sm:p-5">
                        @csrf

                        <x-form.field name="title" label="Title" :required="true">
                            <input type="text" id="title" name="title" value="{{ old('title') }}" required maxlength="160" class="input">
                        </x-form.field>

                        <x-form.field name="type" label="Document type" :required="true">
                            <select id="type" name="type" required class="select">
                                @foreach (\App\Enums\DocumentType::options() as $typeValue => $typeLabel)
                                    <option value="{{ $typeValue }}" @selected(old('type') === $typeValue)>{{ $typeLabel }}</option>
                                @endforeach
                            </select>
                        </x-form.field>

                        <x-form.field name="visibility" label="Visibility" :required="true"
                                      help="Customer visible documents can be downloaded from the tracking page.">
                            <select id="visibility" name="visibility" required class="select">
                                @foreach (\App\Enums\DocumentVisibility::options() as $visValue => $visLabel)
                                    <option value="{{ $visValue }}" @selected(old('visibility', 'internal') === $visValue)>{{ $visLabel }}</option>
                                @endforeach
                            </select>
                        </x-form.field>

                        <x-form.field name="file" label="File" :required="true"
                                      help="PDF, image, Word, Excel or CSV up to {{ round(upload_max_kb() / 1024) }} MB.">
                            <input type="file" id="file" name="file" required class="input py-2 text-sm">
                        </x-form.field>

                        <x-form.field name="description" label="Description" class="sm:col-span-2">
                            <input type="text" id="description" name="description" value="{{ old('description') }}" maxlength="1000" class="input">
                        </x-form.field>

                        <div class="sm:col-span-2">
                            <button type="submit" class="btn btn-dark btn-sm">Upload document</button>
                        </div>
                    </form>
                @endcan

                @if ($shipment->documents->isEmpty())
                    <x-admin.empty message="No documents attached." />
                @else
                    <ul class="divide-y divide-ink-50">
                        @foreach ($shipment->documents as $document)
                            <li class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 sm:px-5">
                                <div class="min-w-0">
                                    <a href="{{ route('admin.shipments.documents.download', [$shipment, $document]) }}"
                                       class="text-sm font-medium text-accent-700 hover:underline">{{ $document->title }}</a>
                                    <p class="text-xs text-ink-500">
                                        {{ $document->type->label() }} · {{ $document->readableSize() }} ·
                                        {{ $document->uploader?->name ?? 'Unknown' }} · {{ $document->created_at->format('j M Y') }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="badge {{ $document->isVisibleToCustomer() ? 'badge-green' : 'badge-slate' }}">
                                        {{ $document->isVisibleToCustomer() ? 'Customer' : 'Internal' }}
                                    </span>
                                    @can('delete', $document)
                                        <form method="POST" action="{{ route('admin.shipments.documents.destroy', [$shipment, $document]) }}"
                                              onsubmit="return confirm('Delete this document?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-sm text-alert-700 hover:underline">Delete</button>
                                        </form>
                                    @endcan
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-admin.panel>

            <x-admin.panel title="Customer conversations" compact>
                @if ($shipment->conversations->isEmpty())
                    <x-admin.empty message="No conversations for this shipment yet." />
                @else
                    <ul class="divide-y divide-ink-50">
                        @foreach ($shipment->conversations as $conversation)
                            <li class="flex items-center justify-between gap-3 px-4 py-3 sm:px-5">
                                <div class="min-w-0">
                                    <a href="{{ route('admin.messages.show', $conversation) }}" class="text-sm font-medium hover:text-accent-700">
                                        {{ $conversation->contact_name }}
                                    </a>
                                    <p class="truncate text-xs text-ink-500">
                                        {{ $conversation->contact_email }} ·
                                        {{ $conversation->last_message_at?->diffForHumans() ?? 'No messages yet' }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-2">
                                    @if ($conversation->unread_for_staff > 0)
                                        <span class="badge badge-red">{{ $conversation->unread_for_staff }} new</span>
                                    @endif
                                    <span class="badge {{ $conversation->isOpen() ? 'badge-green' : 'badge-slate' }}">{{ $conversation->status->label() }}</span>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif

                @can('create', \App\Models\ChatConversation::class)
                    <div x-data="{ open: false }" class="border-t border-ink-100 p-4 sm:p-5">
                        <button type="button" class="btn btn-outline btn-sm" @click="open = ! open">Message the customer</button>

                        <form x-show="open" x-cloak method="POST" action="{{ route('admin.shipments.conversations.store', $shipment) }}" class="mt-4 space-y-4">
                            @csrf

                            <div class="grid gap-4 sm:grid-cols-2">
                                <x-form.field name="contact_name" label="Customer name" :required="true">
                                    <input type="text" id="contact_name" name="contact_name" required maxlength="120" class="input"
                                           value="{{ old('contact_name', $shipment->customerContactName()) }}">
                                </x-form.field>

                                <x-form.field name="contact_email" label="Customer email" :required="true">
                                    <input type="email" id="contact_email" name="contact_email" required maxlength="180" class="input"
                                           value="{{ old('contact_email', $shipment->customerContactEmail()) }}">
                                </x-form.field>
                            </div>

                            <x-form.field name="subject" label="Subject">
                                <input type="text" id="subject" name="subject" maxlength="180" class="input"
                                       value="{{ old('subject', 'Shipment '.$shipment->tracking_number) }}">
                            </x-form.field>

                            <x-form.field name="body" label="Message" :required="true">
                                <textarea id="body" name="body" rows="4" required class="textarea">{{ old('body') }}</textarea>
                            </x-form.field>

                            <button type="submit" class="btn btn-primary btn-sm">Send message</button>
                        </form>
                    </div>
                @endcan
            </x-admin.panel>
        </div>

        <div class="space-y-6">
            <x-admin.panel title="Details" compact>
                <dl class="divide-y divide-ink-50 px-4 sm:px-5">
                    @foreach ([
                        'Customer' => $shipment->customer?->displayName() ?? $shipment->customer_name,
                        'Email' => $shipment->customer_email ?: $shipment->customer?->email,
                        'Phone' => $shipment->customer_phone ?: $shipment->customer?->phone,
                        'Method' => $shipment->shipping_method?->label(),
                        'Service level' => $shipment->service_level,
                        'Packages' => $shipment->package_count,
                        'Weight' => $shipment->weight_kg ? $shipment->weight_kg.' kg' : null,
                        'Dimensions' => $shipment->dimensions,
                        'Declared value' => $shipment->declared_value ? $shipment->declared_value.' '.$shipment->declared_value_currency : null,
                        'Container' => $shipment->container_number,
                        'Vessel' => $shipment->vessel_name,
                        'Voyage' => $shipment->voyage_number,
                        'Air waybill' => $shipment->air_waybill_number,
                        'Flight' => $shipment->flight_number,
                        'Bill of lading' => $shipment->bill_of_lading_number,
                        'Departure' => $shipment->estimated_departure?->format('j M Y'),
                        'Arrival' => $shipment->estimated_arrival?->format('j M Y'),
                        'Delivered' => $shipment->delivered_at?->format('j M Y'),
                        'Created by' => $shipment->creator?->name,
                        'Last edited by' => $shipment->updater?->name,
                    ] as $label => $detail)
                        @if (filled($detail))
                            <div class="flex justify-between gap-4 py-2 text-sm">
                                <dt class="text-ink-500">{{ $label }}</dt>
                                <dd class="text-right font-medium">{{ $detail }}</dd>
                            </div>
                        @endif
                    @endforeach
                </dl>
            </x-admin.panel>

            @if ($shipment->cargo_description || $shipment->internal_notes || $shipment->exception_note)
                <x-admin.panel title="Notes">
                    @if ($shipment->cargo_description)
                        <p class="text-xs font-semibold uppercase tracking-wide text-ink-500">Cargo</p>
                        <p class="mt-1 whitespace-pre-line text-sm text-ink-700">{{ $shipment->cargo_description }}</p>
                    @endif
                    @if ($shipment->exception_note)
                        <p class="mt-4 text-xs font-semibold uppercase tracking-wide text-ink-500">Exception explanation (public)</p>
                        <p class="mt-1 whitespace-pre-line text-sm text-ink-700">{{ $shipment->exception_note }}</p>
                    @endif
                    @if ($shipment->internal_notes)
                        <p class="mt-4 text-xs font-semibold uppercase tracking-wide text-ink-500">Internal notes</p>
                        <p class="mt-1 whitespace-pre-line text-sm text-ink-700">{{ $shipment->internal_notes }}</p>
                    @endif
                </x-admin.panel>
            @endif

            <x-admin.panel title="Audit history" compact>
                @if ($history->isEmpty())
                    <x-admin.empty message="No recorded changes." />
                @else
                    <ul class="divide-y divide-ink-50">
                        @foreach ($history as $log)
                            <li class="px-4 py-2.5 text-sm sm:px-5">
                                <p>{{ $log->description ?? $log->action }}</p>
                                <p class="text-xs text-ink-500">{{ $log->user_name ?? 'System' }} · {{ $log->created_at->format('j M Y, H:i') }}</p>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-admin.panel>

            @can('archive', $shipment)
                @unless ($shipment->isArchived())
                    <form method="POST" action="{{ route('admin.shipments.archive', $shipment) }}"
                          onsubmit="return confirm('Archive this shipment? It will be hidden from the active list.');">
                        @csrf
                        <button type="submit" class="btn btn-outline btn-sm w-full">Archive shipment</button>
                    </form>
                @endunless
            @endcan
        </div>
    </div>
</x-layouts.admin>
