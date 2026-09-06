<x-layouts.auth title="Choose a new password">
    <form method="POST" action="{{ route('admin.password.update') }}" class="mt-6 space-y-5">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <x-form.field name="email" label="Email address" :required="true">
            <input type="email" id="email" name="email" value="{{ old('email', $email) }}" required
                   autocomplete="username" class="input">
        </x-form.field>

        <x-form.field name="password" label="New password" :required="true"
                      help="At least 8 characters. Use something you do not use anywhere else.">
            <input type="password" id="password" name="password" required autocomplete="new-password" class="input">
        </x-form.field>

        <x-form.field name="password_confirmation" label="Confirm new password" :required="true">
            <input type="password" id="password_confirmation" name="password_confirmation" required
                   autocomplete="new-password" class="input">
        </x-form.field>

        <button type="submit" class="btn btn-dark w-full">Save new password</button>
    </form>
</x-layouts.auth>
