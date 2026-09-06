<x-layouts.auth title="Sign in">
    <form method="POST" action="{{ route('admin.login.store') }}" class="mt-6 space-y-5">
        @csrf

        <x-form.field name="email" label="Email address" :required="true">
            <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                   autocomplete="username" class="input" aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}">
        </x-form.field>

        <x-form.field name="password" label="Password" :required="true">
            <input type="password" id="password" name="password" required autocomplete="current-password"
                   class="input" aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}">
        </x-form.field>

        <div class="flex items-center justify-between gap-4">
            <label class="inline-flex items-center gap-2 text-sm text-ink-700">
                <input type="checkbox" name="remember" value="1" class="h-4 w-4 rounded border-ink-300">
                Stay signed in
            </label>

            <a href="{{ route('admin.password.request') }}" class="text-sm font-medium text-accent-700 hover:underline">
                Forgot password?
            </a>
        </div>

        <button type="submit" class="btn btn-dark w-full">Sign in</button>
    </form>
</x-layouts.auth>
