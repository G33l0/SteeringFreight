<x-layouts.admin title="Admin users">
    <x-slot:actions>
        <a href="{{ route('admin.users.create') }}" class="btn btn-primary btn-sm"><x-icon name="plus" class="h-4 w-4" />New account</a>
    </x-slot:actions>

    <x-admin.panel compact>
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Last sign in</th><th><span class="sr-only">Actions</span></th></tr></thead>
                <tbody>
                    @foreach ($users as $user)
                        <tr>
                            <td class="font-medium">
                                {{ $user->name }}
                                @if ($user->job_title)<span class="block text-xs text-ink-500">{{ $user->job_title }}</span>@endif
                            </td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->role->label() }}</td>
                            <td>
                                <span class="badge {{ $user->is_active ? 'badge-green' : 'badge-slate' }}">
                                    {{ $user->is_active ? 'Active' : 'Disabled' }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap text-ink-500">{{ $user->last_login_at?->format('j M Y, H:i') ?? 'Never' }}</td>
                            <td class="text-right"><a href="{{ route('admin.users.edit', $user) }}" class="text-sm text-ink-600 hover:text-accent-700">Edit</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="p-4">{{ $users->links('components.pagination') }}</div>
    </x-admin.panel>
</x-layouts.admin>
