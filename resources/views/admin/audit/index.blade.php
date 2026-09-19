<x-layouts.admin title="Audit logs">
    <x-admin.panel compact>
        <form method="GET" class="grid gap-3 border-b border-ink-100 p-4 sm:grid-cols-2 lg:grid-cols-5">
            <div class="lg:col-span-2">
                <label for="q" class="label">Search</label>
                <input type="search" id="q" name="q" value="{{ $filters['q'] }}" class="input" placeholder="Action, description or user">
            </div>
            <div>
                <label for="user" class="label">User</label>
                <select id="user" name="user" class="select">
                    <option value="">Everyone</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}" @selected($filters['user'] === $user->id)>{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="from" class="label">From</label>
                <input type="date" id="from" name="from" value="{{ $filters['from'] }}" class="input">
            </div>
            <div>
                <label for="to" class="label">To</label>
                <input type="date" id="to" name="to" value="{{ $filters['to'] }}" class="input">
            </div>
            <div class="flex items-end gap-2 lg:col-span-5">
                <button type="submit" class="btn btn-dark btn-sm">Apply</button>
                <a href="{{ route('admin.audit-logs.index') }}" class="btn btn-outline btn-sm">Reset</a>
            </div>
        </form>

        @if ($logs->isEmpty())
            <x-admin.empty message="No log entries match these filters." />
        @else
            <div class="table-scroll">
                <table class="data-table">
                    <thead><tr><th>When</th><th>User</th><th>Action</th><th>Details</th><th>Subject</th><th>IP</th></tr></thead>
                    <tbody>
                        @foreach ($logs as $log)
                            <tr>
                                <td class="whitespace-nowrap text-ink-500">{{ $log->created_at->format('j M Y, H:i') }}</td>
                                <td>{{ $log->user_name ?? 'System' }}</td>
                                <td class="font-mono text-xs">{{ $log->action }}</td>
                                <td class="max-w-md">{{ $log->description }}</td>
                                <td class="text-ink-500">{{ $log->subjectLabel() }}</td>
                                <td class="font-mono text-xs text-ink-500">{{ $log->ip_address }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="p-4">{{ $logs->links('components.pagination') }}</div>
        @endif
    </x-admin.panel>
</x-layouts.admin>
