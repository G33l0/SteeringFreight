<x-layouts.admin :title="'Quote request '.$quote->reference">
    <x-slot:actions>
        <a href="mailto:{{ $quote->email }}?subject={{ rawurlencode('Quotation '.$quote->reference) }}" class="btn btn-outline btn-sm">
            Reply from webmail
        </a>
    </x-slot:actions>

    <div class="grid gap-6 lg:grid-cols-[1.3fr_0.7fr]">
        <div class="space-y-6">
            <x-admin.panel title="The enquiry">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="badge {{ $quote->status->value === 'new' ? 'badge-amber' : 'badge-slate' }}">{{ $quote->status->label() }}</span>
                    @if ($quote->hasBeenReplied())
                        <span class="badge badge-green">Quotation sent {{ $quote->replied_at->diffForHumans() }}</span>
                    @else
                        <span class="badge badge-red">Awaiting reply</span>
                    @endif
                    <span class="text-xs text-ink-500">Received {{ $quote->created_at->format('j M Y, H:i') }}</span>
                </div>

                <dl class="mt-4 divide-y divide-ink-50">
                    @foreach ([
                        'Name' => $quote->name,
                        'Company' => $quote->company,
                        'Email' => $quote->email,
                        'Telephone' => $quote->phone,
                        'Collection' => trim(collect([$quote->origin_city, $quote->origin_country])->filter()->implode(', ')) ?: $quote->origin,
                        'Delivery' => trim(collect([$quote->destination_city, $quote->destination_country])->filter()->implode(', ')) ?: $quote->destination,
                        'Method' => $quote->shipping_method?->label(),
                        'Incoterm' => $quote->incoterm,
                        'Goods' => $quote->cargo_type,
                        'Gross weight' => $quote->approximate_weight,
                        'Packages' => $quote->package_count,
                        'Dimensions' => $quote->dimensions,
                        'Commercial value' => $quote->goods_value,
                        'Cargo ready' => $quote->ready_date?->format('j M Y'),
                    ] as $label => $detail)
                        @if (filled($detail))
                            <div class="flex justify-between gap-4 py-2 text-sm">
                                <dt class="text-ink-500">{{ $label }}</dt>
                                <dd class="text-right font-medium">{{ $detail }}</dd>
                            </div>
                        @endif
                    @endforeach
                </dl>

                @if ($quote->message)
                    <div class="mt-4 border-t border-ink-50 pt-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-ink-500">Notes from the customer</p>
                        <p class="mt-1 whitespace-pre-line text-sm text-ink-700">{{ $quote->message }}</p>
                    </div>
                @endif
            </x-admin.panel>

            @if ($quote->replies->isNotEmpty())
                <x-admin.panel title="Quotations sent" compact>
                    <ul class="divide-y divide-ink-50">
                        @foreach ($quote->replies as $reply)
                            <li class="px-4 py-4 sm:px-5">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <p class="font-medium">{{ $reply->subject }}</p>
                                    <p class="text-xs text-ink-500">
                                        {{ $reply->sender_name }} · {{ $reply->created_at->format('j M Y, H:i') }}
                                    </p>
                                </div>

                                @if ($reply->rateLine() || $reply->transit_time || $reply->valid_until)
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        @if ($rate = $reply->rateLine())
                                            <span class="badge badge-teal">Rate: {{ $rate }}</span>
                                        @endif
                                        @if ($reply->transit_time)
                                            <span class="badge badge-blue">Transit: {{ $reply->transit_time }}</span>
                                        @endif
                                        @if ($reply->valid_until)
                                            <span class="badge badge-slate">Valid until: {{ $reply->valid_until }}</span>
                                        @endif
                                    </div>
                                @endif

                                <p class="mt-2 whitespace-pre-line text-sm text-ink-700">{{ $reply->body }}</p>
                            </li>
                        @endforeach
                    </ul>
                </x-admin.panel>
            @endif

            @can('quotes.manage')
                <x-admin.panel title="Send a quotation" description="Emailed to {{ $quote->email }} and kept on this record">
                    <form method="POST" action="{{ route('admin.quotes.reply', $quote) }}" class="space-y-5">
                        @csrf

                        <x-form.field name="subject" label="Subject" :required="true">
                            <input type="text" id="subject" name="subject" required maxlength="180" class="input"
                                   value="{{ old('subject', 'Quotation '.$quote->reference.': '.$quote->origin.' to '.$quote->destination) }}">
                        </x-form.field>

                        <div class="grid gap-5 sm:grid-cols-4">
                            <x-form.field name="currency" label="Currency">
                                <input type="text" id="currency" name="currency" value="{{ old('currency', 'USD') }}"
                                       maxlength="3" class="input uppercase" placeholder="USD">
                            </x-form.field>

                            <x-form.field name="quoted_amount" label="Rate">
                                <input type="text" id="quoted_amount" name="quoted_amount" value="{{ old('quoted_amount') }}"
                                       maxlength="60" class="input" placeholder="2,850 all in">
                            </x-form.field>

                            <x-form.field name="transit_time" label="Transit time">
                                <input type="text" id="transit_time" name="transit_time" value="{{ old('transit_time') }}"
                                       maxlength="80" class="input" placeholder="28 to 32 days door to door">
                            </x-form.field>

                            <x-form.field name="valid_until" label="Valid until">
                                <input type="text" id="valid_until" name="valid_until" value="{{ old('valid_until') }}"
                                       maxlength="60" class="input" placeholder="End of the month">
                            </x-form.field>
                        </div>

                        <x-form.field name="body" label="Message to the customer" :required="true"
                                      help="What the rate covers, what it excludes, and what you need from them to book.">
                            <textarea id="body" name="body" rows="10" required maxlength="8000" class="textarea">{{ old('body', setting('quotes.reply_signature')) }}</textarea>
                        </x-form.field>

                        <label class="inline-flex items-center gap-2 text-sm">
                            <input type="checkbox" name="mark_quoted" value="1" class="h-4 w-4 rounded border-ink-300" @checked(old('mark_quoted', true))>
                            Mark this request as quoted
                        </label>

                        <div>
                            <button type="submit" class="btn btn-primary btn-sm">Send quotation</button>
                        </div>
                    </form>
                </x-admin.panel>
            @endcan
        </div>

        @can('quotes.manage')
            <div class="space-y-6">
                <x-admin.panel title="Handling">
                    <form method="POST" action="{{ route('admin.quotes.update', $quote) }}" class="space-y-5">
                        @csrf
                        @method('PUT')

                        <x-form.field name="status" label="Status" :required="true">
                            <select id="status" name="status" required class="select">
                                @foreach ($statuses as $value => $label)
                                    <option value="{{ $value }}" @selected(old('status', $quote->status->value) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </x-form.field>

                        <x-form.field name="internal_notes" label="Internal notes" help="Never shown to the customer.">
                            <textarea id="internal_notes" name="internal_notes" rows="6" maxlength="5000" class="textarea">{{ old('internal_notes', $quote->internal_notes) }}</textarea>
                        </x-form.field>

                        <button type="submit" class="btn btn-outline btn-sm">Save</button>
                    </form>

                    @if ($quote->handler)
                        <p class="mt-4 border-t border-ink-50 pt-3 text-xs text-ink-500">
                            Last handled by {{ $quote->handler->name }} {{ $quote->handled_at?->diffForHumans() }}
                        </p>
                    @endif
                </x-admin.panel>

                <x-admin.panel title="Answering from webmail">
                    <p class="text-sm leading-relaxed text-ink-600">
                        The alert sent to your operations mailbox is addressed to reply to
                        <span class="font-medium text-ink-900">{{ $quote->email }}</span>, so pressing Reply in webmail
                        answers the customer directly. Replies sent that way are not recorded here, so mark the request
                        as quoted afterwards.
                    </p>
                </x-admin.panel>
            </div>
        @endcan
    </div>
</x-layouts.admin>
