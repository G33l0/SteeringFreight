@php $value = fn (string $field, $default = null) => old($field, $customer->{$field} ?? $default); @endphp

<x-admin.panel title="Customer details">
    <div class="grid gap-5 sm:grid-cols-2">
        <x-form.field name="name" label="Name" :required="true">
            <input type="text" id="name" name="name" value="{{ $value('name') }}" required maxlength="160" class="input">
        </x-form.field>

        <x-form.field name="company" label="Company">
            <input type="text" id="company" name="company" value="{{ $value('company') }}" maxlength="160" class="input">
        </x-form.field>

        <x-form.field name="email" label="Email">
            <input type="email" id="email" name="email" value="{{ $value('email') }}" maxlength="180" class="input">
        </x-form.field>

        <x-form.field name="phone" label="Phone">
            <input type="tel" id="phone" name="phone" value="{{ $value('phone') }}" maxlength="40" class="input">
        </x-form.field>

        <x-form.field name="address_line_1" label="Address line 1">
            <input type="text" id="address_line_1" name="address_line_1" value="{{ $value('address_line_1') }}" maxlength="200" class="input">
        </x-form.field>

        <x-form.field name="address_line_2" label="Address line 2">
            <input type="text" id="address_line_2" name="address_line_2" value="{{ $value('address_line_2') }}" maxlength="200" class="input">
        </x-form.field>

        <x-form.field name="city" label="City">
            <input type="text" id="city" name="city" value="{{ $value('city') }}" maxlength="120" class="input">
        </x-form.field>

        <x-form.field name="region" label="Region or state">
            <input type="text" id="region" name="region" value="{{ $value('region') }}" maxlength="120" class="input">
        </x-form.field>

        <x-form.field name="postal_code" label="Postal code">
            <input type="text" id="postal_code" name="postal_code" value="{{ $value('postal_code') }}" maxlength="40" class="input">
        </x-form.field>

        <x-form.field name="country" label="Country">
            <x-form.country-select name="country" :value="$value('country')"
                                   :countries="\App\Support\Countries::names()"
                                   :frequent="\App\Support\Countries::frequentlyUsed()" />
        </x-form.field>

        <x-form.field name="notes" label="Internal notes" class="sm:col-span-2" help="Never shown to the customer.">
            <textarea id="notes" name="notes" rows="3" maxlength="5000" class="textarea">{{ $value('notes') }}</textarea>
        </x-form.field>
    </div>

    <label class="mt-5 inline-flex items-center gap-2 text-sm">
        <input type="checkbox" name="notifications_enabled" value="1" class="h-4 w-4 rounded border-ink-300"
               @checked(old('notifications_enabled', $customer->notifications_enabled ?? true))>
        Allow shipment update emails for this customer
    </label>
</x-admin.panel>
