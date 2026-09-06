<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function index(): View
    {
        $this->authorize('viewAny', User::class);

        return view('admin.users.index', [
            'users' => User::orderBy('name')->paginate((int) config('portlane.per_page.admin')),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        return view('admin.users.create', [
            'staff' => new User(['role' => UserRole::Agent, 'is_active' => true]),
            'roles' => UserRole::cases(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email:filter', 'max:180', Rule::unique('users', 'email')],
            'job_title' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:40'],
            'role' => ['required', Rule::enum(UserRole::class)],
            'password' => ['required', 'confirmed', Password::defaults()],
            'is_active' => ['boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $user = User::create($validated);

        $this->audit->record('user.created', $user, "Created admin user {$user->name}", ['role' => $user->role->value]);

        return redirect()->route('admin.users.index')->with('status', 'Administrator account created.');
    }

    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        return view('admin.users.edit', ['staff' => $user, 'roles' => UserRole::cases()]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email:filter', 'max:180', Rule::unique('users', 'email')->ignore($user->getKey())],
            'job_title' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:40'],
            'role' => ['required', Rule::enum(UserRole::class)],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'is_active' => ['boolean'],
        ]);

        // An administrator cannot lock themselves out of the panel.
        $validated['is_active'] = $request->user()->is($user) ? true : $request->boolean('is_active');

        if ($request->user()->is($user)) {
            $validated['role'] = $user->role->value;
        }

        if (blank($validated['password'])) {
            unset($validated['password']);
        }

        $user->update($validated);

        $this->audit->record('user.updated', $user, "Updated admin user {$user->name}", AuditLogger::changes($user));

        return redirect()->route('admin.users.index')->with('status', 'Account updated.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        if (User::where('role', UserRole::Administrator->value)->where('is_active', true)->count() <= 1
            && $user->role === UserRole::Administrator) {
            return back()->withErrors(['user' => 'The last active administrator cannot be removed.']);
        }

        $name = $user->name;
        $user->delete();

        $this->audit->record('user.deleted', null, "Deleted admin user {$name}");

        return redirect()->route('admin.users.index')->with('status', 'Account removed.');
    }
}
