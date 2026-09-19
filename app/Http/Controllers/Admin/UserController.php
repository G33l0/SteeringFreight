<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use App\Support\Settings;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly Settings $settings,
    ) {}

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
            'staff' => new User([
                'role' => UserRole::Representative,
                'is_active' => true,
                'access_expires_at' => $this->defaultExpiry(),
            ]),
            'roles' => UserRole::cases(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validate($this->rules());

        $validated['is_active'] = $request->boolean('is_active');
        $validated['access_expires_at'] = $this->expiryFrom($validated);
        $validated['tracking_quota'] = (int) ($validated['tracking_quota'] ?? 5);

        $user = User::create($validated);

        $this->audit->record('user.created', $user, "Created admin user {$user->name}", [
            'role' => $user->role->value,
            'access_expires_at' => $user->access_expires_at?->toDateString(),
        ]);

        return redirect()->route('admin.users.index')->with('status', 'Staff account created.');
    }

    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        return view('admin.users.edit', ['staff' => $user, 'roles' => UserRole::cases()]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $validated = $request->validate($this->rules($user));

        // An administrator cannot lock themselves out of the panel.
        $validated['is_active'] = $request->user()->is($user) ? true : $request->boolean('is_active');

        if ($request->user()->is($user)) {
            $validated['role'] = $user->role->value;
            unset($validated['access_expires_at']);
        } else {
            $validated['access_expires_at'] = $this->expiryFrom($validated);
        }

        if (blank($validated['password'] ?? null)) {
            unset($validated['password']);
        }

        $validated['tracking_quota'] = (int) ($validated['tracking_quota'] ?? $user->tracking_quota);

        $user->update($validated);

        $this->audit->record('user.updated', $user, "Updated admin user {$user->name}", AuditLogger::changes($user));

        return redirect()->route('admin.users.index')->with('status', 'Account updated.');
    }

    /**
     * Pausing an account. The record stays, the audit trail stays, and the
     * conversations already assigned to them stay where they are for a master
     * admin to reassign — but the account itself can do nothing until it is
     * resumed, including touching a shipment or a tracking number.
     */
    public function suspend(Request $request, User $user): RedirectResponse
    {
        $this->authorize('suspend', $user);

        if ($this->wouldStrandThePanel($user)) {
            return back()->withErrors(['user' => 'The last working administrator cannot be paused.']);
        }

        if ($user->suspended_at !== null) {
            return back()->with('status', 'That account is already paused.');
        }

        $user->forceFill(['suspended_at' => now()])->save();

        $this->audit->record('user.suspended', $user, "Paused the account of {$user->name}");

        return back()->with('status', "{$user->name} can no longer use the panel.");
    }

    /**
     * Letting an account back in. An account that was suspended because its
     * access period ran out needs a new period as well, or resuming it would
     * change nothing.
     */
    public function resume(Request $request, User $user): RedirectResponse
    {
        $this->authorize('suspend', $user);

        $attributes = ['suspended_at' => null];

        if ($user->accessHasExpired()) {
            $attributes['access_expires_at'] = $this->defaultExpiry();
        }

        $user->forceFill($attributes)->save();

        $this->audit->record('user.resumed', $user, "Restored access for {$user->name}", [
            'access_expires_at' => $user->access_expires_at?->toDateString(),
        ]);

        return back()->with('status', $user->access_expires_at
            ? "{$user->name} can use the panel again until {$user->access_expires_at->format('j M Y')}."
            : "{$user->name} can use the panel again.");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        if ($this->wouldStrandThePanel($user)) {
            return back()->withErrors(['user' => 'The last working administrator cannot be removed.']);
        }

        $name = $user->name;
        $user->delete();

        $this->audit->record('user.deleted', null, "Deleted admin user {$name}");

        return redirect()->route('admin.users.index')->with('status', 'Account removed.');
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(?User $user = null): array
    {
        return [
            'name' => ['required', 'string', 'max:160'],
            'email' => [
                'required', 'email:filter', 'max:180',
                Rule::unique('users', 'email')->ignore($user?->getKey()),
            ],
            'job_title' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:40'],
            'role' => ['required', Rule::enum(UserRole::class)],
            'password' => [$user ? 'nullable' : 'required', 'confirmed', Password::defaults()],
            'is_active' => ['boolean'],
            'tracking_quota' => ['nullable', 'integer', 'min:0', 'max:10000'],
            // A new account cannot be created already expired. An existing one
            // may be given a date in the past, which is how an administrator
            // ends somebody's access from the edit screen rather than the list.
            'access_expires_at' => array_filter([
                'nullable', 'date', $user ? null : 'after:today',
                'before:'.now()->addYears(10)->toDateString(),
            ]),
        ];
    }

    /**
     * The access period is only meaningful while it is in the future, and an
     * account with no end date simply never expires.
     *
     * @param  array<string, mixed>  $validated
     */
    private function expiryFrom(array $validated): ?string
    {
        return blank($validated['access_expires_at'] ?? null) ? null : $validated['access_expires_at'];
    }

    /**
     * The date a new account is given by default, from the site settings. Zero
     * days means accounts do not expire unless a date is set by hand.
     */
    private function defaultExpiry(): ?string
    {
        $days = $this->settings->int('security.access_days');

        return $days > 0 ? now()->addDays($days)->toDateString() : null;
    }

    /**
     * True when removing or pausing this account would leave the panel with no
     * administrator who can actually sign in and use it.
     *
     * A backstop rather than the main guard: the policy already refuses to let
     * anybody pause or delete their own account, which is what normally keeps
     * the last administrator in place. This catches the cases the policy
     * cannot see — two administrators pausing each other at the same moment,
     * or a role added later that is given users.manage.
     */
    private function wouldStrandThePanel(User $user): bool
    {
        if ($user->role !== UserRole::Administrator) {
            return false;
        }

        return User::query()
            ->where('role', UserRole::Administrator->value)
            ->whereKeyNot($user->getKey())
            ->usable()
            ->doesntExist();
    }
}
