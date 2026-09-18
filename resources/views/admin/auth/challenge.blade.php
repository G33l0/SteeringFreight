<x-layouts.auth title="Enter your sign-in code">
    <p class="mt-3 text-sm text-ink-600">
        We have emailed a six digit code to <span class="font-medium text-ink-900">{{ $email }}</span>.
        It is valid for {{ config('portlane.security.code_ttl') }} minutes.
    </p>

    <form method="POST" action="{{ route('admin.login.challenge.store') }}" class="mt-6 space-y-5">
        @csrf

        <x-form.field name="code" label="Sign-in code" :required="true">
            <input type="text" id="code" name="code" required autofocus inputmode="numeric" pattern="[0-9]{6}"
                   maxlength="6" autocomplete="one-time-code" spellcheck="false"
                   class="input text-center font-mono text-lg tracking-[0.4em]"
                   aria-invalid="{{ $errors->has('code') ? 'true' : 'false' }}">
        </x-form.field>

        <button type="submit" class="btn btn-dark w-full">Sign in</button>
    </form>

    <div class="mt-5 flex flex-wrap items-center justify-between gap-3 border-t border-ink-100 pt-5 text-sm">
        <form method="POST" action="{{ route('admin.login.challenge.resend') }}">
            @csrf
            <button type="submit" class="font-medium text-accent-700 hover:underline" @disabled($resendAvailableAt !== null)>
                Send another code
            </button>
            @if ($resendAvailableAt)
                <span class="ml-1 text-ink-500">(available shortly)</span>
            @endif
        </form>

        <a href="{{ route('admin.login') }}" class="text-ink-600 hover:underline">Start again</a>
    </div>

    {{--
        The "were you expecting this?" warning belongs in the email, and is
        there. On this screen it would be addressed to somebody who typed the
        password half a minute ago, which tells them nothing: an unexpected
        code is a surprise in an inbox, never on a page you just asked for.
    --}}
</x-layouts.auth>
