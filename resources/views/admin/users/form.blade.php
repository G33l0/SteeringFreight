@php $value = fn (string $field, $default = null) => old($field, $staff->{$field} ?? $default); @endphp

<x-admin.panel title="Account">
    <div class="grid gap-5 sm:grid-cols-2">
        <x-form.field name="name" label="Full name" :required="true">
            <input type="text" id="name" name="name" value="{{ $value('name') }}" required maxlength="160" class="input">
        </x-form.field>

        <x-form.field name="email" label="Email address" :required="true">
            <input type="email" id="email" name="email" value="{{ $value('email') }}" required maxlength="180" class="input">
        </x-form.field>

        <x-form.field name="job_title" label="Job title">
            <input type="text" id="job_title" name="job_title" value="{{ $value('job_title') }}" maxlength="120" class="input">
        </x-form.field>

        <x-form.field name="phone" label="Telephone">
            <input type="tel" id="phone" name="phone" value="{{ $value('phone') }}" maxlength="40" class="input">
        </x-form.field>

        <x-form.field name="role" label="Role" :required="true" class="sm:col-span-2">
            <select id="role" name="role" required class="select" @if (auth()->user()->is($staff)) disabled @endif>
                @foreach ($roles as $role)
                    <option value="{{ $role->value }}"
                        @selected(($value('role') instanceof \App\Enums\UserRole ? $value('role')->value : $value('role')) === $role->value)>
                        {{ $role->label() }} — {{ $role->description() }}
                    </option>
                @endforeach
            </select>
            @if (auth()->user()->is($staff))
                <p class="help">You cannot change your own role.</p>
            @endif
        </x-form.field>

        <x-form.field name="tracking_quota" label="Tracking numbers this account may raise"
                      help="How many shipments a customer representative may create in total. Raise it when they ask. A master admin is never limited, whatever this says.">
            <input type="number" id="tracking_quota" name="tracking_quota" min="0" max="10000"
                   value="{{ old('tracking_quota', $staff->tracking_quota ?? 5) }}" class="input">
            @if ($staff->exists && $staff->isRepresentative())
                <p class="help">Used {{ $staff->trackingUsed() }} of {{ $staff->tracking_quota }}.</p>
            @endif
        </x-form.field>

        <x-form.field name="access_expires_at" label="Access ends on"
                      help="How long this account may use the panel. It is suspended automatically once the date passes, and sees a notice asking them to contact you. Leave empty for an account that does not expire.">
            <input type="date" id="access_expires_at" name="access_expires_at"
                   value="{{ old('access_expires_at', $staff->access_expires_at?->toDateString()) }}"
                   class="input" @disabled(auth()->user()->is($staff))>
            @if (auth()->user()->is($staff))
                <p class="help">You cannot put an end date on your own account.</p>
            @endif
        </x-form.field>

        <x-form.field name="password" :label="$staff->exists ? 'New password' : 'Password'" :required="! $staff->exists"
                      help="At least 12 characters. Leave empty to keep the current password.">
            <input type="password" id="password" name="password" autocomplete="new-password" class="input" @required(! $staff->exists)>
        </x-form.field>

        <x-form.field name="password_confirmation" label="Confirm password" :required="! $staff->exists">
            <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" class="input" @required(! $staff->exists)>
        </x-form.field>
    </div>

    <label class="mt-5 inline-flex items-center gap-2 text-sm">
        <input type="checkbox" name="is_active" value="1" class="h-4 w-4 rounded border-ink-300"
               @checked(old('is_active', $staff->is_active ?? true)) @disabled(auth()->user()->is($staff))>
        Account is active
    </label>

    @if ($staff->exists && $staff->isSuspended())
        <p class="mt-4 rounded border border-caution-700/20 bg-caution-100 p-3 text-sm text-caution-700">
            This account is currently
            {{ $staff->suspensionReason() === 'expired'
                ? 'suspended because its access period ended on '.$staff->access_expires_at?->format('j M Y').'.'
                : 'paused.' }}
            Use the Resume button on the staff list to let them back in.
        </p>
    @endif
</x-admin.panel>
