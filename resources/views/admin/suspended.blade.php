<x-layouts.auth title="Your access is paused">
    <p class="mt-3 text-sm leading-relaxed text-ink-600">
        {{ $notice }}
    </p>

    <dl class="mt-6 space-y-3 border-t border-ink-100 pt-5 text-sm">
        <div class="flex justify-between gap-4">
            <dt class="text-ink-500">Account</dt>
            <dd class="text-right font-medium text-ink-900">{{ $staff->name }}</dd>
        </div>
        <div class="flex justify-between gap-4">
            <dt class="text-ink-500">Role</dt>
            <dd class="text-right text-ink-700">{{ $staff->role->label() }}</dd>
        </div>
        <div class="flex justify-between gap-4">
            <dt class="text-ink-500">Reason</dt>
            <dd class="text-right text-ink-700">
                @if ($staff->suspensionReason() === 'expired')
                    Access period ended {{ $staff->access_expires_at?->format('j M Y') }}
                @else
                    Paused by the administrator
                @endif
            </dd>
        </div>
        @if ($contactEmail)
            <div class="flex justify-between gap-4">
                <dt class="text-ink-500">Who to contact</dt>
                <dd class="text-right">
                    <a href="mailto:{{ $contactEmail }}" class="font-medium text-accent-700 hover:underline">{{ $contactEmail }}</a>
                </dd>
            </div>
        @endif
    </dl>

    <form method="POST" action="{{ route('admin.logout') }}" class="mt-6">
        @csrf
        <button type="submit" class="btn btn-outline w-full">Sign out</button>
    </form>
</x-layouts.auth>
