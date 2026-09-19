@php
    /** @var \App\Models\Shipment|null $shipment */
    $shipment ??= null;
    $value = fn (string $field, $default = null) => old($field, $shipment?->{$field} ?? $default);
    $dateValue = fn (string $field) => old($field, $shipment?->{$field}?->format('Y-m-d'));
@endphp

<div class="space-y-6">
    <x-admin.panel title="Customer">
        <div class="grid gap-5 sm:grid-cols-2">
            @if ($representatives->isNotEmpty())
                <x-form.field name="assigned_to" label="Handled by"
                              help="Give this shipment to a customer representative so they can update its tracking. Leave empty to keep it with the master admin. Assigning does not use up their allowance — only shipments they raise themselves do.">
                    <select id="assigned_to" name="assigned_to" class="select">
                        <option value="">Nobody — master admin only</option>
                        @foreach ($representatives as $representative)
                            <option value="{{ $representative->id }}"
                                @selected((int) $value('assigned_to') === $representative->id)>
                                {{ $representative->name }}
                            </option>
                        @endforeach
                    </select>
                </x-form.field>
            @endif

            <x-form.field name="customer_id" label="Linked customer" help="Optional. Links the shipment to a saved customer record.">
                <select id="customer_id" name="customer_id" class="select">
                    <option value="">Not linked</option>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}" @selected((int) $value('customer_id') === $customer->id)>
                            {{ $customer->name }}{{ $customer->company ? ' — '.$customer->company : '' }}
                        </option>
                    @endforeach
                </select>
            </x-form.field>

            <x-form.field name="customer_name" label="Contact name">
                <input type="text" id="customer_name" name="customer_name" value="{{ $value('customer_name') }}" maxlength="160" class="input">
            </x-form.field>

            <x-form.field name="customer_email" label="Contact email" help="Shipment update emails go to this address.">
                <input type="email" id="customer_email" name="customer_email" value="{{ $value('customer_email') }}" maxlength="180" class="input">
            </x-form.field>

            <x-form.field name="customer_phone" label="Contact phone">
                <input type="tel" id="customer_phone" name="customer_phone" value="{{ $value('customer_phone') }}" maxlength="40" class="input">
            </x-form.field>
        </div>
    </x-admin.panel>

    <x-admin.panel title="Route">
        <div class="grid gap-5 sm:grid-cols-2">
            <x-form.field name="origin_city" label="Origin city">
                <input type="text" id="origin_city" name="origin_city" value="{{ $value('origin_city') }}" maxlength="120" class="input">
            </x-form.field>

            <x-form.field name="origin_country" label="Origin country">
                <x-form.country-select name="origin_country" :value="$value('origin_country')"
                                       :countries="\App\Support\Countries::names()"
                                       :frequent="\App\Support\Countries::frequentlyUsed()" />
            </x-form.field>

            <x-form.field name="destination_city" label="Destination city">
                <input type="text" id="destination_city" name="destination_city" value="{{ $value('destination_city') }}" maxlength="120" class="input">
            </x-form.field>

            <x-form.field name="destination_country" label="Destination country">
                <x-form.country-select name="destination_country" :value="$value('destination_country')"
                                       :countries="\App\Support\Countries::names()"
                                       :frequent="\App\Support\Countries::frequentlyUsed()" />
            </x-form.field>

            <x-form.field name="current_location" label="Current location" class="sm:col-span-2"
                          help="Shown on the customer tracking page as the latest reported position.">
                <input type="text" id="current_location" name="current_location" value="{{ $value('current_location') }}" maxlength="200" class="input">
            </x-form.field>
        </div>
    </x-admin.panel>

    <x-admin.panel title="Cargo">
        <div class="grid gap-5 sm:grid-cols-2">
            <x-form.field name="shipping_method" label="Shipping method">
                <select id="shipping_method" name="shipping_method" class="select">
                    <option value="">Not specified</option>
                    @foreach ($methods as $methodValue => $methodLabel)
                        <option value="{{ $methodValue }}" @selected($value('shipping_method') instanceof \App\Enums\ShippingMethod
                            ? $value('shipping_method')->value === $methodValue
                            : $value('shipping_method') === $methodValue)>{{ $methodLabel }}</option>
                    @endforeach
                </select>
            </x-form.field>

            <x-form.field name="service_level" label="Service level" help="For example: standard, express, consolidated.">
                <input type="text" id="service_level" name="service_level" value="{{ $value('service_level') }}" maxlength="60" class="input">
            </x-form.field>

            <x-form.field name="cargo_description" label="Cargo description" class="sm:col-span-2">
                <textarea id="cargo_description" name="cargo_description" rows="3" maxlength="2000" class="textarea">{{ $value('cargo_description') }}</textarea>
            </x-form.field>

            <x-form.field name="package_count" label="Number of packages">
                <input type="number" id="package_count" name="package_count" value="{{ $value('package_count') }}" min="0" class="input">
            </x-form.field>

            <x-form.field name="weight_kg" label="Weight (kg)">
                <input type="number" step="0.001" id="weight_kg" name="weight_kg" value="{{ $value('weight_kg') }}" min="0" class="input">
            </x-form.field>

            <x-form.field name="dimensions" label="Dimensions" help="For example: 8 pallets, 120 x 100 x 145 cm.">
                <input type="text" id="dimensions" name="dimensions" value="{{ $value('dimensions') }}" maxlength="120" class="input">
            </x-form.field>

            <div class="grid grid-cols-[1fr_auto] gap-3">
                <x-form.field name="declared_value" label="Declared value">
                    <input type="number" step="0.01" id="declared_value" name="declared_value" value="{{ $value('declared_value') }}" min="0" class="input">
                </x-form.field>
                <x-form.field name="declared_value_currency" label="Currency">
                    <input type="text" id="declared_value_currency" name="declared_value_currency" value="{{ $value('declared_value_currency') }}"
                           maxlength="3" class="input w-20 uppercase" placeholder="USD">
                </x-form.field>
            </div>
        </div>
    </x-admin.panel>

    <x-admin.panel title="Transport references" description="Fill in only what applies to this shipment.">
        <div class="grid gap-5 sm:grid-cols-3">
            <x-form.field name="container_number" label="Container number">
                <input type="text" id="container_number" name="container_number" value="{{ $value('container_number') }}" maxlength="40" class="input font-mono">
            </x-form.field>

            <x-form.field name="vessel_name" label="Vessel name">
                <input type="text" id="vessel_name" name="vessel_name" value="{{ $value('vessel_name') }}" maxlength="120" class="input">
            </x-form.field>

            <x-form.field name="voyage_number" label="Voyage number">
                <input type="text" id="voyage_number" name="voyage_number" value="{{ $value('voyage_number') }}" maxlength="60" class="input font-mono">
            </x-form.field>

            <x-form.field name="air_waybill_number" label="Air waybill number">
                <input type="text" id="air_waybill_number" name="air_waybill_number" value="{{ $value('air_waybill_number') }}" maxlength="60" class="input font-mono">
            </x-form.field>

            <x-form.field name="flight_number" label="Flight number">
                <input type="text" id="flight_number" name="flight_number" value="{{ $value('flight_number') }}" maxlength="40" class="input font-mono">
            </x-form.field>

            <x-form.field name="bill_of_lading_number" label="Bill of lading">
                <input type="text" id="bill_of_lading_number" name="bill_of_lading_number" value="{{ $value('bill_of_lading_number') }}" maxlength="60" class="input font-mono">
            </x-form.field>
        </div>
    </x-admin.panel>

    <x-admin.panel title="Schedule and status">
        <div class="grid gap-5 sm:grid-cols-3">
            <x-form.field name="estimated_departure" label="Estimated departure">
                <input type="date" id="estimated_departure" name="estimated_departure" value="{{ $dateValue('estimated_departure') }}" class="input">
            </x-form.field>

            <x-form.field name="estimated_arrival" label="Estimated arrival">
                <input type="date" id="estimated_arrival" name="estimated_arrival" value="{{ $dateValue('estimated_arrival') }}" class="input">
            </x-form.field>

            <x-form.field name="estimated_delivery" label="Estimated delivery">
                <input type="date" id="estimated_delivery" name="estimated_delivery" value="{{ $dateValue('estimated_delivery') }}" class="input">
            </x-form.field>

            <x-form.field name="shipment_status_id" label="Current status" class="sm:col-span-2">
                <select id="shipment_status_id" name="shipment_status_id" class="select">
                    <option value="">Not set</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->id }}" @selected((int) $value('shipment_status_id') === $status->id)>
                            {{ $status->name }}{{ $status->isException() ? ' (exception)' : '' }}
                        </option>
                    @endforeach
                </select>
            </x-form.field>

            <x-form.field name="tracking_number" label="Tracking number"
                          help="Leave empty to generate one automatically.">
                <input type="text" id="tracking_number" name="tracking_number" value="{{ $value('tracking_number') }}"
                       maxlength="40" class="input font-mono uppercase">
            </x-form.field>

            <x-form.field name="exception_note" label="Exception explanation" class="sm:col-span-3"
                          help="Shown to the customer when the current status is an exception. Write what happened and what you are doing about it.">
                <textarea id="exception_note" name="exception_note" rows="3" maxlength="2000" class="textarea">{{ $value('exception_note') }}</textarea>
            </x-form.field>

            <x-form.field name="internal_notes" label="Internal notes" class="sm:col-span-3"
                          help="Never shown to the customer.">
                <textarea id="internal_notes" name="internal_notes" rows="3" maxlength="5000" class="textarea">{{ $value('internal_notes') }}</textarea>
            </x-form.field>
        </div>

        <label class="mt-5 inline-flex items-start gap-2 text-sm">
            <input type="checkbox" name="notifications_enabled" value="1" class="mt-0.5 h-4 w-4 rounded border-ink-300"
                   @checked(old('notifications_enabled', $shipment?->notifications_enabled ?? true))>
            <span>
                Send status update emails for this shipment
                <span class="block text-xs text-ink-500">Emails are only sent if notifications are switched on in the site settings and the status is set to notify.</span>
            </span>
        </label>
    </x-admin.panel>
</div>
