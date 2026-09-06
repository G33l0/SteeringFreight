<x-layouts.auth title="Reset your password">
    <p class="mt-3 text-sm leading-relaxed text-ink-600">
        Enter the email address on your staff account and we will send a link to set a new password.
    </p>

    <form method="POST" action="{{ route('admin.password.email') }}" class="mt-6 space-y-5">
        @csrf

        <x-form.field name="email" label="Email address" :required="true">
            <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                   autocomplete="username" class="input">
        </x-form.field>

        <button type="submit" class="btn btn-dark w-full">Send reset link</button>
    </form>

    <p class="mt-5 text-sm text-ink-600">
        <a href="{{ route('admin.login') }}" class="font-medium text-accent-700 hover:underline">Back to sign in</a>
    </p>
</x-layouts.auth>
