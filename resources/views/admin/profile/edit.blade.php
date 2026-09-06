<x-layouts.admin title="Your profile">
    <div class="grid max-w-4xl gap-6 lg:grid-cols-2">
        <x-admin.panel title="Details">
            <form method="POST" action="{{ route('admin.profile.update') }}" class="space-y-5">
                @csrf
                @method('PUT')

                <x-form.field name="name" label="Full name" :required="true">
                    <input type="text" id="name" name="name" value="{{ old('name', $staff->name) }}" required maxlength="160" class="input">
                </x-form.field>

                <x-form.field name="email" label="Email address" :required="true">
                    <input type="email" id="email" name="email" value="{{ old('email', $staff->email) }}" required maxlength="180" class="input">
                </x-form.field>

                <x-form.field name="job_title" label="Job title">
                    <input type="text" id="job_title" name="job_title" value="{{ old('job_title', $staff->job_title) }}" maxlength="120" class="input">
                </x-form.field>

                <x-form.field name="phone" label="Telephone">
                    <input type="tel" id="phone" name="phone" value="{{ old('phone', $staff->phone) }}" maxlength="40" class="input">
                </x-form.field>

                <button type="submit" class="btn btn-primary btn-sm">Save profile</button>
            </form>
        </x-admin.panel>

        <x-admin.panel title="Password">
            <form method="POST" action="{{ route('admin.profile.password') }}" class="space-y-5">
                @csrf
                @method('PUT')

                <x-form.field name="current_password" label="Current password" :required="true">
                    <input type="password" id="current_password" name="current_password" required autocomplete="current-password" class="input">
                </x-form.field>

                <x-form.field name="password" label="New password" :required="true" help="At least 8 characters.">
                    <input type="password" id="password" name="password" required autocomplete="new-password" class="input">
                </x-form.field>

                <x-form.field name="password_confirmation" label="Confirm new password" :required="true">
                    <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password" class="input">
                </x-form.field>

                <button type="submit" class="btn btn-primary btn-sm">Change password</button>
            </form>

            <dl class="mt-6 space-y-1 border-t border-ink-50 pt-4 text-sm text-ink-500">
                <div class="flex justify-between gap-4"><dt>Role</dt><dd>{{ $staff->role->label() }}</dd></div>
                <div class="flex justify-between gap-4"><dt>Last sign in</dt><dd>{{ $staff->last_login_at?->format('j M Y, H:i') ?? 'Never' }}</dd></div>
            </dl>
        </x-admin.panel>
    </div>
</x-layouts.admin>
