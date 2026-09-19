<x-layouts.admin title="Staff accounts">
    <x-slot:actions>
        <a href="{{ route('admin.users.create') }}" class="btn btn-primary btn-sm"><x-icon name="plus" class="h-4 w-4" />New account</a>
    </x-slot:actions>

    <x-admin.panel compact>
        <div class="table-scroll">
            <table class="data-table">
                <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Tracking</th><th>Access ends</th><th>Last sign in</th><th><span class="sr-only">Actions</span></th></tr></thead>
                <tbody>
                    @foreach ($users as $user)
                        @php
                            [$badge, $label] = match (true) {
                                ! $user->is_active => ['badge-slate', 'Disabled'],
                                $user->suspensionReason() === 'paused' => ['badge-amber', 'Paused'],
                                $user->suspensionReason() === 'expired' => ['badge-red', 'Expired'],
                                default => ['badge-green', 'Active'],
                            };
                        @endphp
                        <tr>
                            <td class="font-medium">
                                {{ $user->name }}
                                @if ($user->job_title)<span class="block text-xs text-ink-500">{{ $user->job_title }}</span>@endif
                            </td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->role->label() }}</td>
                            <td><span class="badge {{ $badge }}">{{ $label }}</span></td>
                            <td class="whitespace-nowrap text-ink-500">
                                @if ($user->isRepresentative())
                                    {{ $user->trackingUsed() }} / {{ $user->tracking_quota }}
                                @else
                                    <span class="text-ink-400">Unlimited</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap text-ink-500">
                                {{ $user->access_expires_at?->format('j M Y') ?? 'No end date' }}
                            </td>
                            <td class="whitespace-nowrap text-ink-500">{{ $user->last_login_at?->format('j M Y, H:i') ?? 'Never' }}</td>
                            <td>
                                <div class="flex items-center justify-end gap-3 text-sm">
                                    <a href="{{ route('admin.users.edit', $user) }}" class="text-ink-600 hover:text-accent-700">Edit</a>

                                    @can('suspend', $user)
                                        @if ($user->isSuspended())
                                            <form method="POST" action="{{ route('admin.users.resume', $user) }}">
                                                @csrf
                                                <button type="submit" class="text-ink-600 hover:text-accent-700">Resume</button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('admin.users.suspend', $user) }}"
                                                  onsubmit="return confirm('Pause {{ $user->name }}? They will be signed out of everything except the renewal notice.')">
                                                @csrf
                                                <button type="submit" class="text-ink-600 hover:text-accent-700">Pause</button>
                                            </form>
                                        @endif
                                    @endcan

                                    @can('delete', $user)
                                        <form method="POST" action="{{ route('admin.users.destroy', $user) }}"
                                              onsubmit="return confirm('Delete {{ $user->name }}? This cannot be undone.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-alert-700 hover:underline">Delete</button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="p-4">{{ $users->links('components.pagination') }}</div>
    </x-admin.panel>

    <p class="mt-4 text-sm text-ink-500">
        Pausing keeps the account and its history but takes away every permission at once, so a paused
        representative cannot open a conversation or touch a shipment or tracking number. An account with an
        end date is paused automatically once that date passes.
    </p>
</x-layouts.admin>
